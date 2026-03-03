<?php

namespace App\Http\Controllers;

use App\Models\Ujian;
use App\Models\PesertaUjian;
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
}
