<?php

namespace App\Http\Controllers;

use App\Models\Ujian;
use App\Models\PesertaUjian;
use App\Models\JawabanSiswa;
use App\Models\BankSoal;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ExamController extends Controller
{
    /**
     * Show token verification page before starting exam
     */
    public function start(Ujian $ujian)
    {
        $siswa = auth()->user()->siswa;
        if (!$siswa) {
            return redirect()->route('siswa.dashboard')->with('error', 'Profil siswa tidak ditemukan.');
        }

        $peserta = PesertaUjian::where('ujian_id', $ujian->id)
            ->where('siswa_id', $siswa->id)
            ->first();

        if (!$peserta) {
            // Auto register if student's class is assigned to this exam
            $isAssigned = $ujian->kelasList()->where('kelas.id', $siswa->kelas_id)->exists();
            if ($isAssigned) {
                $peserta = PesertaUjian::create([
                    'ujian_id' => $ujian->id,
                    'siswa_id' => $siswa->id,
                    'status' => 'belum',
                ]);
            }
            else {
                return redirect()->route('siswa.dashboard')->with('error', 'Anda tidak terdaftar untuk ujian ini.');
            }
        }

        if ($peserta->status === 'selesai') {
            return redirect()->route('siswa.dashboard')->with('error', 'Anda sudah menyelesaikan ujian ini.');
        }

        if (!$ujian->isActive()) {
            return redirect()->route('siswa.dashboard')->with('error', 'Ujian belum dimulai atau sudah berakhir.');
        }

        // If already started (sedang), skip token and go to exam
        if ($peserta->status === 'sedang') {
            return redirect()->route('exam.mengerjakan', $ujian->id);
        }

        // Show token verification page
        $ujian->load('mapel');
        return view('exam.verify-token', compact('ujian'));
    }

    /**
     * Verify token and start exam
     */
    public function verifyToken(Request $request, Ujian $ujian)
    {
        $request->validate([
            'token' => 'required|string|size:5',
        ]);

        $siswa = auth()->user()->siswa;
        if (!$siswa) {
            return back()->with('error', 'Profil siswa tidak ditemukan.');
        }

        $peserta = PesertaUjian::where('ujian_id', $ujian->id)
            ->where('siswa_id', $siswa->id)
            ->first();

        if (!$peserta) {
            // Auto register if student's class is assigned to this exam
            $isAssigned = $ujian->kelasList()->where('kelas.id', $siswa->kelas_id)->exists();
            if ($isAssigned) {
                $peserta = PesertaUjian::create([
                    'ujian_id' => $ujian->id,
                    'siswa_id' => $siswa->id,
                    'status' => 'belum',
                ]);
            }
            else {
                return back()->with('error', 'Anda tidak terdaftar untuk ujian ini.');
            }
        }

        if ($peserta->status === 'selesai') {
            return redirect()->route('siswa.dashboard')->with('error', 'Anda sudah menyelesaikan ujian ini.');
        }

        if (!$ujian->isActive()) {
            return back()->with('error', 'Ujian belum dimulai atau sudah berakhir.');
        }

        // Verify token
        if (strtoupper($request->token) !== strtoupper($ujian->token)) {
            return back()->with('error', 'Token salah! Silakan periksa kembali token dari pengawas.')->withInput();
        }

        // Token valid — start the exam
        if ($peserta->status === 'belum') {
            $soalIds = $ujian->bankSoals()->pluck('bank_soals.id')->toArray();
            if ($ujian->metode_soal === 'random') {
                shuffle($soalIds);
            }

            $peserta->update([
                'status' => 'sedang',
                'waktu_mulai' => now(),
                'soal_order' => json_encode($soalIds),
            ]);

            foreach ($soalIds as $soalId) {
                JawabanSiswa::firstOrCreate([
                    'peserta_ujian_id' => $peserta->id,
                    'bank_soal_id' => $soalId,
                ]);
            }

            ActivityLog::log('start_exam', 'ujian', "Memulai ujian: {$ujian->nama_ujian}");
        }

        return redirect()->route('exam.mengerjakan', $ujian->id);
    }

    /**
     * Exam working page
     */
    public function mengerjakan(Ujian $ujian)
    {
        $siswa = auth()->user()->siswa;
        $peserta = PesertaUjian::where('ujian_id', $ujian->id)
            ->where('siswa_id', $siswa->id)
            ->where('status', 'sedang')
            ->first();

        if (!$peserta) {
            return redirect()->route('siswa.dashboard')->with('error', 'Sesi ujian tidak ditemukan.');
        }

        // Calculate remaining time
        $waktuBerakhir = $peserta->waktu_mulai->copy()->addMinutes($ujian->durasi_menit);
        $sisaWaktu = now()->diffInSeconds($waktuBerakhir, false);

        if ($sisaWaktu <= 0) {
            return $this->submitExam($ujian, $peserta);
        }

        // Get ordered soal
        $soalOrder = $peserta->getSoalOrderArray();
        $soals = BankSoal::with('opsiJawabans')
            ->whereIn('id', $soalOrder)
            ->get()
            ->sortBy(function ($soal) use ($soalOrder) {
            return array_search($soal->id, $soalOrder);
        })
            ->values();

        // Shuffle options if enabled
        if ($ujian->acak_opsi) {
            $soals->each(function ($soal) {
                $soal->setRelation('opsiJawabans', $soal->opsiJawabans->shuffle());
            });
        }

        // Get existing answers
        $jawabans = JawabanSiswa::where('peserta_ujian_id', $peserta->id)
            ->pluck('jawaban_dipilih', 'bank_soal_id')
            ->toArray();

        $raguRagu = JawabanSiswa::where('peserta_ujian_id', $peserta->id)
            ->where('is_ragu', true)
            ->pluck('bank_soal_id')
            ->toArray();

        return view('exam.mengerjakan', compact(
            'ujian', 'peserta', 'soals', 'jawabans', 'raguRagu', 'sisaWaktu', 'waktuBerakhir'
        ));
    }

    /**
     * Save single answer (AJAX autosave)
     */
    public function saveJawaban(Request $request, Ujian $ujian)
    {
        try {
            $siswa = auth()->user()->siswa;
            $peserta = PesertaUjian::where('ujian_id', $ujian->id)
                ->where('siswa_id', $siswa->id)
                ->where('status', 'sedang')
                ->first();

            if (!$peserta) {
                \Log::warning('Save jawaban: Peserta not found or not active', [
                    'ujian_id' => $ujian->id,
                    'user_id' => auth()->id(),
                ]);
                return response()->json(['error' => 'Sesi tidak valid'], 403);
            }

            // Log request
            \Log::info('Save jawaban request', [
                'peserta_id' => $peserta->id,
                'bank_soal_id' => $request->bank_soal_id,
                'jawaban' => $request->jawaban,
                'is_ragu' => $request->is_ragu,
            ]);

            // Validasi input
            $request->validate([
                'bank_soal_id' => 'required|integer|exists:bank_soals,id',
                'jawaban' => 'nullable|string',
                'is_ragu' => 'nullable|boolean',
            ]);

            // Pastikan jawaban tidak null jika ada nilai
            $jawabanValue = $request->jawaban;
            if ($jawabanValue === null || $jawabanValue === '') {
                \Log::warning('Empty jawaban received', [
                    'peserta_id' => $peserta->id,
                    'bank_soal_id' => $request->bank_soal_id,
                ]);
            }

            $jawaban = JawabanSiswa::updateOrCreate(
                [
                    'peserta_ujian_id' => $peserta->id,
                    'bank_soal_id' => $request->bank_soal_id,
                ],
                [
                    'jawaban_dipilih' => $jawabanValue,
                    'is_ragu' => $request->boolean('is_ragu'),
                ]
            );

            // Verify data was saved
            $jawaban->refresh();
            
            \Log::info('Jawaban saved successfully', [
                'id' => $jawaban->id,
                'peserta_id' => $peserta->id,
                'bank_soal_id' => $jawaban->bank_soal_id,
                'jawaban_dipilih' => $jawaban->jawaban_dipilih,
                'is_null' => $jawaban->jawaban_dipilih === null,
                'is_empty' => $jawaban->jawaban_dipilih === '',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Jawaban berhasil disimpan',
                'data' => [
                    'id' => $jawaban->id,
                    'bank_soal_id' => $jawaban->bank_soal_id,
                    'jawaban_dipilih' => $jawaban->jawaban_dipilih,
                    'is_ragu' => $jawaban->is_ragu,
                    'verified' => true,
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error saving answer', [
                'errors' => $e->errors(),
                'request' => $request->all(),
            ]);
            return response()->json([
                'error' => 'Validasi gagal',
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error saving answer', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);
            return response()->json([
                'error' => 'Gagal menyimpan jawaban',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit exam
     */
    public function submit(Request $request, Ujian $ujian)
    {
        $siswa = auth()->user()->siswa;
        $peserta = PesertaUjian::where('ujian_id', $ujian->id)
            ->where('siswa_id', $siswa->id)
            ->where('status', 'sedang')
            ->first();

        if (!$peserta) {
            return redirect()->route('siswa.dashboard')->with('error', 'Sesi ujian tidak valid.');
        }

        // Log untuk debugging
        \Log::info('Submitting exam', [
            'ujian_id' => $ujian->id,
            'siswa_id' => $siswa->id,
            'peserta_id' => $peserta->id,
            'jawaban_count' => JawabanSiswa::where('peserta_ujian_id', $peserta->id)->count(),
        ]);

        return $this->submitExam($ujian, $peserta);
    }

    /**
     * Process exam submission and grading
     */
    protected function submitExam(Ujian $ujian, PesertaUjian $peserta)
    {
        // Ambil semua jawaban siswa dengan relasi
        $jawabans = JawabanSiswa::with('bankSoal.opsiJawabans')
            ->where('peserta_ujian_id', $peserta->id)
            ->get();
        
        // Log untuk debugging
        \Log::info('Processing exam submission', [
            'peserta_id' => $peserta->id,
            'ujian_id' => $ujian->id,
            'total_jawaban' => $jawabans->count(),
            'jawaban_terisi' => $jawabans->whereNotNull('jawaban_dipilih')->where('jawaban_dipilih', '!=', '')->count(),
            'jawaban_detail' => $jawabans->map(function($j) {
                return [
                    'id' => $j->id,
                    'soal_id' => $j->bank_soal_id,
                    'jawaban' => $j->jawaban_dipilih,
                    'is_empty' => empty($j->jawaban_dipilih),
                ];
            })->toArray(),
        ]);
        
        $totalNilai = 0;
        $totalBobot = 0;
        $benarCount = 0;
        $salahCount = 0;
        $kosongCount = 0;

        foreach ($jawabans as $jawaban) {
            $soal = $jawaban->bankSoal;
            if (!$soal) {
                \Log::warning('Soal not found for jawaban', ['jawaban_id' => $jawaban->id, 'bank_soal_id' => $jawaban->bank_soal_id]);
                continue;
            }

            $totalBobot += $soal->bobot_nilai;

            // Cek apakah jawaban kosong
            if (empty($jawaban->jawaban_dipilih) || $jawaban->jawaban_dipilih === '' || $jawaban->jawaban_dipilih === null) {
                $kosongCount++;
                $jawaban->update([
                    'is_correct' => false,
                    'nilai' => 0,
                ]);
                \Log::info('Empty answer', ['soal_id' => $soal->id, 'jawaban_id' => $jawaban->id]);
                continue;
            }

            if ($soal->tipe_soal === 'pg' || $soal->tipe_soal === 'pg_kompleks') {
                $correctOption = $soal->opsiJawabans()->where('is_correct', true)->first();
                
                if (!$correctOption) {
                    \Log::warning('No correct option found', ['soal_id' => $soal->id]);
                    continue;
                }
                
                \Log::info('Checking answer', [
                    'soal_id' => $soal->id,
                    'jawaban_siswa' => $jawaban->jawaban_dipilih,
                    'jawaban_benar' => $correctOption->opsi_label,
                    'is_match' => $jawaban->jawaban_dipilih === $correctOption->opsi_label,
                ]);
                
                if ($jawaban->jawaban_dipilih === $correctOption->opsi_label) {
                    $jawaban->update([
                        'is_correct' => true,
                        'nilai' => $soal->bobot_nilai,
                    ]);
                    $totalNilai += $soal->bobot_nilai;
                    $benarCount++;
                }
                else {
                    $jawaban->update([
                        'is_correct' => false,
                        'nilai' => 0,
                    ]);
                    $salahCount++;
                }

                // Increment usage count
                $soal->increment('digunakan_count');
            }
            elseif ($soal->tipe_soal === 'essay') {
                // Essay tidak dinilai otomatis
                $jawaban->update([
                    'is_correct' => null,
                    'nilai' => 0,
                ]);
            }
        }

        // Calculate final score (0-100 scale)
        $nilaiAkhir = $totalBobot > 0 ? round(($totalNilai / $totalBobot) * 100, 2) : 0;

        $peserta->update([
            'status' => 'selesai',
            'waktu_selesai' => now(),
            'nilai' => $nilaiAkhir,
        ]);

        // Log hasil
        \Log::info('Exam submitted successfully', [
            'peserta_id' => $peserta->id,
            'ujian_id' => $ujian->id,
            'nilai_akhir' => $nilaiAkhir,
            'total_soal' => $jawabans->count(),
            'benar' => $benarCount,
            'salah' => $salahCount,
            'kosong' => $kosongCount,
            'total_bobot' => $totalBobot,
            'total_nilai' => $totalNilai,
        ]);

        ActivityLog::log('submit_exam', 'ujian', "Menyelesaikan ujian: {$ujian->nama_ujian}, Nilai: {$nilaiAkhir}");

        return redirect()->route('exam.result', $ujian->id);
    }

    /**
     * Show exam result
     */
    public function result(Ujian $ujian)
    {
        $siswa = auth()->user()->siswa;
        $peserta = PesertaUjian::where('ujian_id', $ujian->id)
            ->where('siswa_id', $siswa->id)
            ->where('status', 'selesai')
            ->first();

        if (!$peserta) {
            return redirect()->route('siswa.dashboard')->with('error', 'Hasil ujian tidak ditemukan.');
        }

        $jawabans = [];
        if ($ujian->tampilkan_pembahasan) {
            $jawabans = JawabanSiswa::with('bankSoal.opsiJawabans')
                ->where('peserta_ujian_id', $peserta->id)
                ->get();
        }

        return view('exam.result', compact('ujian', 'peserta', 'jawabans'));
    }

    /**
     * Handle anti-cheat violation
     */
    public function antiCheatViolation(Request $request, Ujian $ujian)
    {
        try {
            $siswa = auth()->user()->siswa ?? null;
            
            if ($siswa) {
                $peserta = PesertaUjian::where('ujian_id', $ujian->id)
                    ->where('siswa_id', $siswa->id)
                    ->where('status', 'sedang')
                    ->first();

                if ($peserta) {
                    // Log violation
                    ActivityLog::log('anti_cheat_violation', 'ujian', 
                        "Pelanggaran anti-cheat: {$request->violation_type} - {$request->detail}");
                    
                    // Submit exam automatically
                    $this->submitExam($ujian, $peserta);
                }
            }

            // Logout user
            if (auth()->check()) {
                auth()->logout();
            }
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Show violation page
            return view('exam.anti-cheat-violation');
            
        } catch (\Exception $e) {
            \Log::error('Anti-cheat violation error: ' . $e->getMessage());
            
            // Fallback to login with message
            return redirect()->route('login')->with('error', 'Anda telah di-logout karena terdeteksi melakukan kecurangan.');
        }
    }
}
