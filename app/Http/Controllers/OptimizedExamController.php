<?php

namespace App\Http\Controllers;

use App\Models\Ujian;
use App\Models\PesertaUjian;
use App\Models\JawabanSiswa;
use App\Models\BankSoal;
use App\Services\CacheService;
use App\Jobs\SaveJawabanJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OptimizedExamController extends Controller
{
    protected $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Exam working page - OPTIMIZED
     */
    public function mengerjakan(Ujian $ujian)
    {
        $siswa = auth()->user()->siswa;
        
        // Use cache for peserta
        $peserta = $this->cacheService->cachePesertaUjian($ujian->id, $siswa->id);

        if (!$peserta || $peserta->status !== 'sedang') {
            return redirect()->route('siswa.dashboard')->with('error', 'Sesi ujian tidak ditemukan.');
        }

        // Calculate remaining time
        $waktuBerakhir = $peserta->waktu_mulai->copy()->addMinutes($ujian->durasi_menit);
        $sisaWaktu = now()->diffInSeconds($waktuBerakhir, false);

        if ($sisaWaktu <= 0) {
            return $this->submitExam($ujian, $peserta);
        }

        // Get ordered soal from cache
        $soalOrder = $peserta->getSoalOrderArray();
        $soals = $this->cacheService->cacheSoalUjian($ujian->id, $soalOrder);

        // Shuffle options if enabled
        if ($ujian->acak_opsi) {
            $soals->each(function ($soal) {
                $soal->setRelation('opsiJawabans', $soal->opsiJawabans->shuffle());
            });
        }

        // Get existing answers from cache
        $jawabans = $this->cacheService->cacheJawabanSiswa($peserta->id);

        // Get ragu-ragu
        $raguRagu = Cache::remember("ragu:{$peserta->id}", 300, function () use ($peserta) {
            return JawabanSiswa::where('peserta_ujian_id', $peserta->id)
                ->where('is_ragu', true)
                ->pluck('bank_soal_id')
                ->toArray();
        });

        return view('exam.mengerjakan', compact(
            'ujian', 'peserta', 'soals', 'jawabans', 'raguRagu', 'sisaWaktu', 'waktuBerakhir'
        ));
    }

    /**
     * Save single answer (AJAX autosave) - OPTIMIZED with Queue
     */
    public function saveJawaban(Request $request, Ujian $ujian)
    {
        try {
            $siswa = auth()->user()->siswa;
            
            // Use cache for peserta lookup
            $peserta = $this->cacheService->cachePesertaUjian($ujian->id, $siswa->id);

            if (!$peserta || $peserta->status !== 'sedang') {
                return response()->json(['error' => 'Sesi tidak valid'], 403);
            }

            // Validate input
            $validated = $request->validate([
                'bank_soal_id' => 'required|integer|exists:bank_soals,id',
                'jawaban' => 'nullable|string',
                'is_ragu' => 'nullable|boolean',
            ]);

            // For high-load scenarios, use queue
            if (env('QUEUE_CONNECTION') !== 'sync') {
                // Dispatch to queue
                SaveJawabanJob::dispatch(
                    $peserta->id,
                    $validated['bank_soal_id'],
                    $validated['jawaban'] ?? '',
                    $request->boolean('is_ragu')
                )->onQueue('high');

                // Return immediate response
                return response()->json([
                    'success' => true,
                    'message' => 'Jawaban sedang disimpan',
                    'queued' => true,
                ]);
            }

            // Synchronous save for low-load or development
            $jawaban = JawabanSiswa::updateOrCreate(
                [
                    'peserta_ujian_id' => $peserta->id,
                    'bank_soal_id' => $validated['bank_soal_id'],
                ],
                [
                    'jawaban_dipilih' => $validated['jawaban'] ?? '',
                    'is_ragu' => $request->boolean('is_ragu'),
                ]
            );

            // Invalidate cache
            $this->cacheService->invalidateJawaban($peserta->id);

            return response()->json([
                'success' => true,
                'message' => 'Jawaban berhasil disimpan',
                'data' => [
                    'id' => $jawaban->id,
                    'bank_soal_id' => $jawaban->bank_soal_id,
                    'jawaban_dipilih' => $jawaban->jawaban_dipilih,
                    'is_ragu' => $jawaban->is_ragu,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error saving answer', [
                'message' => $e->getMessage(),
                'request' => $request->all(),
            ]);
            
            return response()->json([
                'error' => 'Gagal menyimpan jawaban',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit exam - OPTIMIZED
     */
    public function submit(Request $request, Ujian $ujian)
    {
        $siswa = auth()->user()->siswa;
        $peserta = $this->cacheService->cachePesertaUjian($ujian->id, $siswa->id);

        if (!$peserta || $peserta->status !== 'sedang') {
            return redirect()->route('siswa.dashboard')->with('error', 'Sesi ujian tidak valid.');
        }

        return $this->submitExam($ujian, $peserta);
    }

    /**
     * Process exam submission and grading - OPTIMIZED
     */
    protected function submitExam(Ujian $ujian, PesertaUjian $peserta)
    {
        // Use transaction for data consistency
        DB::beginTransaction();
        
        try {
            // Fetch all answers with eager loading
            $jawabans = JawabanSiswa::with('bankSoal.opsiJawabans')
                ->where('peserta_ujian_id', $peserta->id)
                ->get();
            
            $totalNilai = 0;
            $totalBobot = 0;
            $benarCount = 0;
            $salahCount = 0;
            $kosongCount = 0;

            // Batch update for better performance
            $updates = [];

            foreach ($jawabans as $jawaban) {
                $soal = $jawaban->bankSoal;
                if (!$soal) continue;

                $totalBobot += $soal->bobot_nilai;

                if (empty($jawaban->jawaban_dipilih)) {
                    $kosongCount++;
                    $updates[] = [
                        'id' => $jawaban->id,
                        'is_correct' => false,
                        'nilai' => 0,
                    ];
                    continue;
                }

                if ($soal->tipe_soal === 'pg' || $soal->tipe_soal === 'pg_kompleks') {
                    $correctOption = $soal->opsiJawabans()->where('is_correct', true)->first();
                    
                    if ($correctOption && $jawaban->jawaban_dipilih === $correctOption->opsi_label) {
                        $updates[] = [
                            'id' => $jawaban->id,
                            'is_correct' => true,
                            'nilai' => $soal->bobot_nilai,
                        ];
                        $totalNilai += $soal->bobot_nilai;
                        $benarCount++;
                    } else {
                        $updates[] = [
                            'id' => $jawaban->id,
                            'is_correct' => false,
                            'nilai' => 0,
                        ];
                        $salahCount++;
                    }
                }
            }

            // Batch update jawaban
            foreach ($updates as $update) {
                JawabanSiswa::where('id', $update['id'])->update([
                    'is_correct' => $update['is_correct'],
                    'nilai' => $update['nilai'],
                ]);
            }

            // Calculate final score
            $nilaiAkhir = $totalBobot > 0 ? round(($totalNilai / $totalBobot) * 100, 2) : 0;

            // Update peserta
            $peserta->update([
                'status' => 'selesai',
                'waktu_selesai' => now(),
                'nilai' => $nilaiAkhir,
            ]);

            DB::commit();

            // Invalidate all related cache
            $this->cacheService->invalidatePeserta($ujian->id, $peserta->siswa_id);
            $this->cacheService->invalidateJawaban($peserta->id);

            Log::info('Exam submitted successfully', [
                'peserta_id' => $peserta->id,
                'nilai' => $nilaiAkhir,
                'benar' => $benarCount,
                'salah' => $salahCount,
                'kosong' => $kosongCount,
            ]);

            return redirect()->route('exam.result', $ujian->id);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error submitting exam', [
                'peserta_id' => $peserta->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('siswa.dashboard')
                ->with('error', 'Terjadi kesalahan saat submit ujian. Silakan hubungi pengawas.');
        }
    }

    /**
     * Show exam result - OPTIMIZED
     */
    public function result(Ujian $ujian)
    {
        $siswa = auth()->user()->siswa;
        
        $peserta = Cache::remember("result:{$ujian->id}:{$siswa->id}", 3600, function () use ($ujian, $siswa) {
            return PesertaUjian::where('ujian_id', $ujian->id)
                ->where('siswa_id', $siswa->id)
                ->where('status', 'selesai')
                ->first();
        });

        if (!$peserta) {
            return redirect()->route('siswa.dashboard')->with('error', 'Hasil ujian tidak ditemukan.');
        }

        $jawabans = [];
        if ($ujian->tampilkan_pembahasan) {
            $jawabans = Cache::remember("pembahasan:{$peserta->id}", 3600, function () use ($peserta) {
                return JawabanSiswa::with('bankSoal.opsiJawabans')
                    ->where('peserta_ujian_id', $peserta->id)
                    ->get();
            });
        }

        return view('exam.result', compact('ujian', 'peserta', 'jawabans'));
    }
}
