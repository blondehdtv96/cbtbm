<?php

namespace App\Http\Controllers;

use App\Models\Ujian;
use App\Models\PesertaUjian;
use App\Models\JawabanSiswa;
use App\Models\BankSoal;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ExamController extends Controller
{
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

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

        // Get ordered soal — soal+opsi content is identical for every peserta of this
        // ujian and doesn't change while the exam is running, so it's cached; per-peserta
        // ordering (random per siswa when metode_soal=random) is applied after the cache read.
        $soalOrder = $peserta->getSoalOrderArray();
        $soalById = $this->cacheService->cacheSoalUjian($ujian->id);
        $soals = collect($soalOrder)
            ->map(fn($id) => $soalById->get($id))
            ->filter()
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

        $jawabanFiles = JawabanSiswa::where('peserta_ujian_id', $peserta->id)
            ->whereNotNull('jawaban_file')
            ->pluck('jawaban_file', 'bank_soal_id')
            ->toArray();

        $raguRagu = JawabanSiswa::where('peserta_ujian_id', $peserta->id)
            ->where('is_ragu', true)
            ->pluck('bank_soal_id')
            ->toArray();

        $antiCheatEnabled = SystemSetting::get('anti_cheat_enabled', '1') == '1';
        $maxTabSwitch = (int) SystemSetting::get('max_tab_switch', 2);

        return view('exam.mengerjakan', compact(
            'ujian', 'peserta', 'soals', 'jawabans', 'jawabanFiles', 'raguRagu', 'sisaWaktu', 'waktuBerakhir',
            'antiCheatEnabled', 'maxTabSwitch'
        ));
    }

    /**
     * Save single answer (AJAX autosave)
     */
    public function saveJawaban(Request $request, Ujian $ujian)
    {
        $request->validate([
            'bank_soal_id' => 'required|integer|exists:bank_soals,id',
            'jawaban' => 'nullable|string',
            'is_ragu' => 'nullable|boolean',
        ]);

        $siswa = auth()->user()->siswa;
        $peserta = PesertaUjian::where('ujian_id', $ujian->id)
            ->where('siswa_id', $siswa->id)
            ->where('status', 'sedang')
            ->first();

        if (!$peserta) {
            return response()->json(['error' => 'Sesi tidak valid'], 403);
        }

        $jawabanValue = $request->jawaban;
        $isRagu = $request->boolean('is_ragu');

        try {
            // Single upsert query instead of updateOrCreate's SELECT+INSERT/UPDATE —
            // this endpoint is called every few seconds by every siswa taking the exam,
            // so it's the hottest write path in the system. Safe because
            // (peserta_ujian_id, bank_soal_id) has a UNIQUE constraint.
            DB::table('jawaban_siswas')->upsert(
                [[
                    'peserta_ujian_id' => $peserta->id,
                    'bank_soal_id' => $request->bank_soal_id,
                    'jawaban_dipilih' => $jawabanValue,
                    'is_ragu' => $isRagu,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]],
                ['peserta_ujian_id', 'bank_soal_id'],
                ['jawaban_dipilih', 'is_ragu', 'updated_at']
            );

            return response()->json([
                'success' => true,
                'message' => 'Jawaban berhasil disimpan',
                'data' => [
                    'bank_soal_id' => (int) $request->bank_soal_id,
                    'jawaban_dipilih' => $jawabanValue,
                    'is_ragu' => $isRagu,
                    'verified' => true,
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error saving answer', [
                'peserta_id' => $peserta->id,
                'bank_soal_id' => $request->bank_soal_id,
                'message' => $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'Gagal menyimpan jawaban',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save an image as the answer for an essay question (e.g. photo of
     * handwritten work), following the same upload convention used for
     * question images in BankSoalController (image|max:2048, 'public' disk).
     * Kept as its own multipart endpoint separate from the JSON autosave in
     * saveJawaban(), which is throttled much tighter for its high call rate.
     */
    public function saveJawabanFile(Request $request, Ujian $ujian)
    {
        $request->validate([
            'bank_soal_id' => 'required|integer|exists:bank_soals,id',
            'jawaban_file' => 'required|image|max:2048',
        ]);

        $siswa = auth()->user()->siswa;
        $peserta = PesertaUjian::where('ujian_id', $ujian->id)
            ->where('siswa_id', $siswa->id)
            ->where('status', 'sedang')
            ->first();

        if (!$peserta) {
            return response()->json(['error' => 'Sesi tidak valid'], 403);
        }

        $soal = BankSoal::find($request->bank_soal_id);
        if (!$soal || $soal->tipe_soal !== 'essay') {
            return response()->json(['error' => 'Soal ini tidak mendukung jawaban gambar'], 422);
        }

        $existing = JawabanSiswa::where('peserta_ujian_id', $peserta->id)
            ->where('bank_soal_id', $request->bank_soal_id)
            ->first();

        $path = $request->file('jawaban_file')->store('jawaban-images', 'public');

        if ($existing && $existing->jawaban_file) {
            Storage::disk('public')->delete($existing->jawaban_file);
        }

        JawabanSiswa::updateOrCreate(
            ['peserta_ujian_id' => $peserta->id, 'bank_soal_id' => $request->bank_soal_id],
            ['jawaban_file' => $path]
        );

        return response()->json([
            'success' => true,
            'message' => 'Gambar jawaban berhasil diunggah',
            'data' => [
                'bank_soal_id' => (int) $request->bank_soal_id,
                'jawaban_file' => $path,
                'url' => asset('storage/' . $path),
            ],
        ]);
    }

    /**
     * Remove an uploaded answer image (student wants to retake the photo).
     */
    public function deleteJawabanFile(Request $request, Ujian $ujian)
    {
        $request->validate([
            'bank_soal_id' => 'required|integer|exists:bank_soals,id',
        ]);

        $siswa = auth()->user()->siswa;
        $peserta = PesertaUjian::where('ujian_id', $ujian->id)
            ->where('siswa_id', $siswa->id)
            ->where('status', 'sedang')
            ->first();

        if (!$peserta) {
            return response()->json(['error' => 'Sesi tidak valid'], 403);
        }

        $jawaban = JawabanSiswa::where('peserta_ujian_id', $peserta->id)
            ->where('bank_soal_id', $request->bank_soal_id)
            ->first();

        if ($jawaban && $jawaban->jawaban_file) {
            Storage::disk('public')->delete($jawaban->jawaban_file);
            $jawaban->update(['jawaban_file' => null]);
        }

        return response()->json(['success' => true]);
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

        return $this->submitExam($ujian, $peserta);
    }

    /**
     * Process exam submission and grading.
     *
     * Wrapped in a transaction and grades are written with a handful of grouped
     * bulk UPDATEs instead of one UPDATE per jawaban — matters when many siswa
     * submit near-simultaneously (e.g. the exam timer running out for everyone
     * who started together).
     */
    protected function submitExam(Ujian $ujian, PesertaUjian $peserta)
    {
        $nilaiAkhir = DB::transaction(function () use ($ujian, $peserta) {
            $jawabans = JawabanSiswa::with('bankSoal.opsiJawabans')
                ->where('peserta_ujian_id', $peserta->id)
                ->get();

            $totalNilai = 0;
            $totalBobot = 0;
            $benarCount = 0;
            $salahCount = 0;
            $kosongCount = 0;

            $emptyIds = [];
            $wrongIds = [];
            $correctIdsByBobot = [];
            $essayIds = [];
            $soalUsageIds = [];

            foreach ($jawabans as $jawaban) {
                $soal = $jawaban->bankSoal;
                if (!$soal) {
                    \Log::warning('Soal not found for jawaban', ['jawaban_id' => $jawaban->id, 'bank_soal_id' => $jawaban->bank_soal_id]);
                    continue;
                }

                $totalBobot += $soal->bobot_nilai;

                if (empty($jawaban->jawaban_dipilih) && empty($jawaban->jawaban_file)) {
                    $kosongCount++;
                    $emptyIds[] = $jawaban->id;
                    continue;
                }

                if ($soal->tipe_soal === 'pg' || $soal->tipe_soal === 'pg_kompleks') {
                    $correctOption = $soal->opsiJawabans->firstWhere('is_correct', true);

                    if (!$correctOption) {
                        \Log::warning('No correct option found', ['soal_id' => $soal->id]);
                        continue;
                    }

                    $soalUsageIds[] = $soal->id;

                    if ($jawaban->jawaban_dipilih === $correctOption->opsi_label) {
                        $correctIdsByBobot[$soal->bobot_nilai][] = $jawaban->id;
                        $totalNilai += $soal->bobot_nilai;
                        $benarCount++;
                    } else {
                        $wrongIds[] = $jawaban->id;
                        $salahCount++;
                    }
                } elseif ($soal->tipe_soal === 'essay') {
                    // Essay tidak dinilai otomatis
                    $essayIds[] = $jawaban->id;
                }
            }

            if (!empty($emptyIds)) {
                JawabanSiswa::whereIn('id', $emptyIds)->update(['is_correct' => false, 'nilai' => 0]);
            }
            if (!empty($wrongIds)) {
                JawabanSiswa::whereIn('id', $wrongIds)->update(['is_correct' => false, 'nilai' => 0]);
            }
            foreach ($correctIdsByBobot as $bobot => $ids) {
                JawabanSiswa::whereIn('id', $ids)->update(['is_correct' => true, 'nilai' => $bobot]);
            }
            if (!empty($essayIds)) {
                JawabanSiswa::whereIn('id', $essayIds)->update(['is_correct' => null, 'nilai' => 0]);
            }
            if (!empty($soalUsageIds)) {
                BankSoal::whereIn('id', array_unique($soalUsageIds))->increment('digunakan_count');
            }

            // Calculate final score (0-100 scale)
            $nilaiAkhir = $totalBobot > 0 ? round(($totalNilai / $totalBobot) * 100, 2) : 0;

            $peserta->update([
                'status' => 'selesai',
                'waktu_selesai' => now(),
                'nilai' => $nilaiAkhir,
            ]);

            \Log::info('Exam submitted successfully', [
                'peserta_id' => $peserta->id,
                'ujian_id' => $ujian->id,
                'nilai_akhir' => $nilaiAkhir,
                'total_soal' => $jawabans->count(),
                'benar' => $benarCount,
                'salah' => $salahCount,
                'kosong' => $kosongCount,
            ]);

            return $nilaiAkhir;
        });

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
                    ActivityLog::log('cheat_detected', 'ujian',
                        "Pelanggaran anti-cheat: {$request->violation_type} - {$request->detail}",
                        [
                            'siswa_nama' => $siswa->nama,
                            'siswa_nisn' => $siswa->nisn,
                            'ujian_nama' => $ujian->nama_ujian,
                            'kelas' => $siswa->kelas->nama_kelas ?? '-',
                            'violation_type' => $request->violation_type,
                            'detail' => $request->detail,
                        ]);

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
