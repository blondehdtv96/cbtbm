<?php

namespace App\Http\Controllers;

use App\Models\Ujian;
use App\Models\Kelas;
use App\Models\Jurusan;
use App\Models\Siswa;
use App\Models\SesiUjian;
use App\Models\PesertaUjian;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class KartuPesertaController extends Controller
{
    /**
     * Halaman utama cetak kartu (hub page dengan modal + pengaturan).
     */
    public function index(Request $request)
    {
        $jurusans = Jurusan::where('is_active', true)->orderBy('nama_jurusan')->get();
        $kelasList = Kelas::with('jurusan')->where('is_active', true)->orderBy('nama_kelas')->get();
        $sesiList = SesiUjian::where('is_active', true)->orderBy('jam_mulai')->get();

        // Load kartu settings
        $kartuSettings = $this->getKartuSettings();

        return view('kartu-peserta.index', compact('jurusans', 'kelasList', 'sesiList', 'kartuSettings'));
    }

    /**
     * Simpan pengaturan kartu.
     */
    public function saveSettings(Request $request)
    {
        $keys = [
            'kartu_judul', 'kartu_nama_sekolah', 'kartu_tahun_pelajaran',
            'kartu_nama_ttd', 'kartu_jabatan_ttd', 'kartu_ruang',
            'kartu_show_username', 'kartu_show_password', 'kartu_show_sesi',
            'kartu_show_ruang', 'kartu_show_foto', 'kartu_show_ttd',
        ];

        foreach ($keys as $key) {
            $value = $request->input($key, '');
            Setting::setValue($key, $value, 'kartu_peserta');
        }

        // Handle logo upload
        if ($request->hasFile('kartu_logo')) {
            $request->validate(['kartu_logo' => 'image|max:2048']);
            $path = $request->file('kartu_logo')->store('kartu', 'public');
            Setting::setValue('kartu_logo', $path, 'kartu_peserta');
        }

        // Handle logo removal
        if ($request->input('remove_logo') == '1') {
            $oldLogo = Setting::getValue('kartu_logo');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }
            Setting::setValue('kartu_logo', '', 'kartu_peserta');
        }

        return back()->with('success', 'Pengaturan kartu berhasil disimpan!');
    }

    /**
     * Endpoint AJAX: Dapatkan kelas berdasarkan jurusan.
     */
    public function kelasByJurusan(Request $request)
    {
        $kelas = Kelas::where('jurusan_id', $request->jurusan_id)
            ->where('is_active', true)
            ->orderBy('tingkat')
            ->orderBy('nama_kelas')
            ->get(['id', 'nama_kelas', 'tingkat']);

        return response()->json($kelas);
    }

    /**
     * Halaman konfirmasi / preview peserta (by ujian).
     */
    public function preview(Ujian $ujian)
    {
        $ujian->load([
            'mapel',
            'guru',
            'kelasList.jurusan',
            'pesertaUjians.siswa.kelas.jurusan',
        ]);

        $pesertaList = $ujian->pesertaUjians
            ->filter(fn($p) => $p->siswa !== null)
            ->sortBy([
        fn($a, $b) => strcmp($a->siswa->kelas->nama_kelas ?? '', $b->siswa->kelas->nama_kelas ?? ''),
        fn($a, $b) => strcmp($a->siswa->nama, $b->siswa->nama),
        ])
            ->values();

        return view('kartu-peserta.preview', compact('ujian', 'pesertaList'));
    }

    /**
     * Cetak kartu berdasarkan Kelas (alur modal Jurusan → Kelas).
     */
    public function printByKelas(Request $request)
    {
        $request->validate([
            'kelas_id' => 'required|exists:kelas,id',
        ]);

        $kelas = Kelas::with('jurusan')->findOrFail($request->kelas_id);

        // Cari ujian yang aktif/publish untuk kelas ini
        $ujians = Ujian::with(['mapel', 'pesertaUjians', 'sesiUjian'])
            ->where('status', 'publish')
            ->whereHas('kelasList', fn($q) => $q->where('kelas.id', $kelas->id))
            ->orderBy('tanggal_mulai')
            ->get();

        // Ambil semua siswa di kelas ini (with user for username)
        $siswas = Siswa::with(['kelas.jurusan', 'user'])
            ->where('kelas_id', $kelas->id)
            ->orderBy('nama')
            ->get();

        // Load settings
        $kartuSettings = $this->getKartuSettings();

        // Sesi & Ruang from request override
        $sesiRuang = $request->input('sesi_ruang', '');

        return view('kartu-peserta.print-kelas', compact(
            'kelas', 'ujians', 'siswas', 'kartuSettings', 'sesiRuang'
        ));
    }

    /**
     * Cetak kartu berdasarkan ujian (alur via preview).
     */
    public function print(Request $request, Ujian $ujian)
    {
        $ujian->load(['mapel', 'guru', 'kelasList', 'sesiUjian']);

        $selectedIds = $request->input('peserta_ids', []);

        $pesertaQuery = PesertaUjian::with(['siswa.kelas.jurusan', 'siswa.user', 'ujian'])
            ->where('ujian_id', $ujian->id)
            ->whereHas('siswa');

        if (!empty($selectedIds)) {
            $pesertaQuery->whereIn('id', $selectedIds);
        }

        $pesertaList = $pesertaQuery->get()
            ->sortBy([
        fn($a, $b) => strcmp($a->siswa->kelas->nama_kelas ?? '', $b->siswa->kelas->nama_kelas ?? ''),
        fn($a, $b) => strcmp($a->siswa->nama, $b->siswa->nama),
        ])->values();

        $kartuSettings = $this->getKartuSettings();

        return view('kartu-peserta.print', compact('ujian', 'pesertaList', 'kartuSettings'));
    }

    /**
     * Helper: Get all kartu settings with defaults.
     */
    private function getKartuSettings(): array
    {
        return [
            'judul' => Setting::getValue('kartu_judul', 'KARTU PESERTA UBK'),
            'nama_sekolah' => Setting::getValue('kartu_nama_sekolah', 'SMK NEGERI 1'),
            'tahun_pelajaran' => Setting::getValue('kartu_tahun_pelajaran', '2024/2025'),
            'nama_ttd' => Setting::getValue('kartu_nama_ttd', 'Kepala Sekolah'),
            'jabatan_ttd' => Setting::getValue('kartu_jabatan_ttd', ''),
            'ruang' => Setting::getValue('kartu_ruang', ''),
            'logo' => Setting::getValue('kartu_logo', ''),
            'show_username' => Setting::getValue('kartu_show_username', '1'),
            'show_password' => Setting::getValue('kartu_show_password', '1'),
            'show_sesi' => Setting::getValue('kartu_show_sesi', '1'),
            'show_ruang' => Setting::getValue('kartu_show_ruang', '1'),
            'show_foto' => Setting::getValue('kartu_show_foto', '1'),
            'show_ttd' => Setting::getValue('kartu_show_ttd', '1'),
        ];
    }
}
