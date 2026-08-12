<?php

namespace App\Http\Controllers;

use App\Models\Ujian;
use App\Models\PesertaUjian;
use App\Models\JawabanSiswa;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class StatusPesertaController extends Controller
{
    /**
     * Daftar ujian untuk monitoring status peserta.
     */
    public function index(Request $request)
    {
        $query = Ujian::with(['mapel', 'guru', 'sesiUjian'])
            ->withCount([
            'pesertaUjians',
            'pesertaUjians as belum_count' => fn($q) => $q->where('status', 'belum'),
            'pesertaUjians as sedang_count' => fn($q) => $q->where('status', 'sedang'),
            'pesertaUjians as selesai_count' => fn($q) => $q->where('status', 'selesai'),
        ]);

        // Guru hanya lihat ujian miliknya
        if (auth()->user()->isGuru() && auth()->user()->guru) {
            $query->where('guru_id', auth()->user()->guru->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where('nama_ujian', 'like', "%{$request->search}%");
        }

        $ujians = $query->latest()->paginate(15)->withQueryString();

        return view('status-peserta.index', compact('ujians'));
    }

    /**
     * Detail status peserta per ujian.
     */
    public function show(Request $request, Ujian $ujian)
    {
        $ujian->load(['mapel', 'guru', 'sesiUjian', 'kelasList']);

        $query = PesertaUjian::with(['siswa.kelas.jurusan'])
            ->withCount(['jawabanSiswas as menjawab_count' => function ($q) {
                $q->whereNotNull('jawaban_dipilih')->where('jawaban_dipilih', '!=', '');
            }])
            ->where('ujian_id', $ujian->id)
            ->whereHas('siswa');

        // Filter by status
        if ($request->filled('filter_status')) {
            $query->where('status', $request->filter_status);
        }

        // Search by nama siswa
        if ($request->filled('search')) {
            $query->whereHas('siswa', fn($q) => $q->where('nama', 'like', "%{$request->search}%"));
        }

        $pesertaList = $query->get()
            ->sortBy([
        fn($a, $b) => strcmp($a->siswa->kelas->nama_kelas ?? '', $b->siswa->kelas->nama_kelas ?? ''),
        fn($a, $b) => strcmp($a->siswa->nama, $b->siswa->nama),
        ])->values();

        // Stats
        $stats = [
            'total' => $pesertaList->count(),
            'belum' => $pesertaList->where('status', 'belum')->count(),
            'sedang' => $pesertaList->where('status', 'sedang')->count(),
            'selesai' => $pesertaList->where('status', 'selesai')->count(),
        ];

        // If filtered, recount from all peserta
        if ($request->filled('filter_status') || $request->filled('search')) {
            $allPeserta = PesertaUjian::where('ujian_id', $ujian->id)->whereHas('siswa');
            $stats = [
                'total' => $allPeserta->count(),
                'belum' => (clone $allPeserta)->where('status', 'belum')->count(),
                'sedang' => (clone $allPeserta)->where('status', 'sedang')->count(),
                'selesai' => (clone $allPeserta)->where('status', 'selesai')->count(),
            ];
        }

        return view('status-peserta.show', compact('ujian', 'pesertaList', 'stats'));
    }

    /**
     * Reset seorang peserta yang terkendala saat mengerjakan ujian (device
     * crash, listrik/koneksi putus, salah ke-submit oleh anti-cheat, dll).
     *
     * Peserta dikembalikan ke status 'sedang' dan diberi sisa waktu baru
     * supaya bisa login lagi dan melanjutkan. Jawaban yang sudah tersimpan
     * (jawaban_siswas) TIDAK disentuh sama sekali — hasil pengerjaan siswa
     * tetap sesuai posisi terakhir dia mengerjakan, tidak direset ke nol.
     */
    public function resetPeserta(Request $request, Ujian $ujian, PesertaUjian $peserta)
    {
        if ($peserta->ujian_id !== $ujian->id) {
            abort(404);
        }

        if (!in_array($peserta->status, ['sedang', 'selesai'])) {
            return back()->with('error', 'Peserta ini belum memulai ujian, tidak ada yang perlu direset.');
        }

        $request->validate([
            'menit' => 'required|integer|min:1|max:'.$ujian->durasi_menit,
            'catatan' => 'nullable|string|max:500',
        ], [
            'menit.max' => 'Sisa waktu tidak boleh melebihi durasi ujian ('.$ujian->durasi_menit.' menit).',
        ]);

        $peserta->load('siswa');
        $statusSebelumnya = $peserta->status;
        $menitBaru = (int) $request->menit;

        $peserta->update([
            'status' => 'sedang',
            'waktu_mulai' => now()->subMinutes($ujian->durasi_menit - $menitBaru),
            'waktu_selesai' => null,
            'nilai' => null,
        ]);

        $jumlahTerjawab = JawabanSiswa::where('peserta_ujian_id', $peserta->id)
            ->whereNotNull('jawaban_dipilih')
            ->where('jawaban_dipilih', '!=', '')
            ->count();

        ActivityLog::log('reset_peserta', 'ujian', sprintf(
            'Reset peserta ujian "%s" pada %s (status sebelumnya: %s → sedang, sisa waktu baru: %d menit, %d jawaban tersimpan dipertahankan)%s',
            $peserta->siswa->nama ?? '-',
            $ujian->nama_ujian,
            $statusSebelumnya,
            $menitBaru,
            $jumlahTerjawab,
            $request->filled('catatan') ? '. Catatan: '.$request->catatan : ''
        ), [
            'peserta_ujian_id' => $peserta->id,
            'siswa_id' => $peserta->siswa_id,
            'status_sebelumnya' => $statusSebelumnya,
            'menit_baru' => $menitBaru,
            'jawaban_dipertahankan' => $jumlahTerjawab,
            'catatan' => $request->catatan,
        ]);

        return back()->with('success', "Peserta {$peserta->siswa->nama} berhasil direset. Semua jawaban yang sudah tersimpan tetap dipertahankan — siswa bisa login kembali dan melanjutkan dengan sisa waktu {$menitBaru} menit.");
    }
}
