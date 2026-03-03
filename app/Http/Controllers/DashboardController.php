<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Ujian;
use App\Models\Siswa;
use App\Models\Guru;
use App\Models\BankSoal;
use App\Models\PesertaUjian;
use App\Models\Kelas;
use App\Models\Jurusan;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function superadmin()
    {
        $data = [
            'totalSiswa' => Siswa::count(),
            'totalGuru' => Guru::count(),
            'totalUjian' => Ujian::count(),
            'ujianAktif' => Ujian::where('status', 'publish')
                ->where('tanggal_mulai', '<=', now())
                ->where('tanggal_selesai', '>=', now())
                ->count(),
            'totalSoal' => BankSoal::count(),
            'totalKelas' => Kelas::count(),
            'totalJurusan' => Jurusan::count(),
            'totalUser' => User::count(),
            'recentUjians' => Ujian::with('mapel')->latest()->take(5)->get(),
            'ujianBerlangsung' => Ujian::with(['mapel', 'pesertaUjians'])
                ->where('status', 'publish')
                ->where('tanggal_mulai', '<=', now())
                ->where('tanggal_selesai', '>=', now())
                ->get(),
        ];

        return view('dashboard.superadmin', $data);
    }

    public function admin()
    {
        $data = [
            'totalSiswa' => Siswa::count(),
            'totalGuru' => Guru::count(),
            'totalUjian' => Ujian::count(),
            'ujianAktif' => Ujian::where('status', 'publish')
                ->where('tanggal_mulai', '<=', now())
                ->where('tanggal_selesai', '>=', now())
                ->count(),
            'totalSoal' => BankSoal::count(),
            'recentUjians' => Ujian::with('mapel')->latest()->take(5)->get(),
        ];

        return view('dashboard.admin', $data);
    }

    public function guru()
    {
        $guru = auth()->user()->guru;

        $data = [
            'totalSoal' => $guru ? BankSoal::where('guru_id', $guru->id)->count() : 0,
            'totalUjian' => $guru ? Ujian::where('guru_id', $guru->id)->count() : 0,
            'ujianAktif' => $guru ? Ujian::where('guru_id', $guru->id)
                ->where('status', 'publish')
                ->where('tanggal_mulai', '<=', now())
                ->where('tanggal_selesai', '>=', now())
                ->count() : 0,
            'recentUjians' => $guru ? Ujian::with('mapel')
                ->where('guru_id', $guru->id)
                ->latest()->take(5)->get() : collect(),
        ];

        return view('dashboard.guru', $data);
    }

    public function siswa()
    {
        $siswa = auth()->user()->siswa;

        $data = [
            'ujianTersedia' => collect(),
            'riwayatUjian' => collect(),
            'siswa' => $siswa,
        ];

        if ($siswa) {
            $kelasId = $siswa->kelas_id;

            $data['ujianTersedia'] = Ujian::with('mapel')
                ->whereHas('kelasList', function ($q) use ($kelasId) {
                    $q->where('kelas.id', $kelasId);
                })
                ->where('status', 'publish')
                ->where('tanggal_mulai', '<=', now())
                ->where('tanggal_selesai', '>=', now())
                ->whereDoesntHave('pesertaUjians', function ($q) use ($siswa) {
                    $q->where('siswa_id', $siswa->id)->where('status', 'selesai');
                })
                ->get();

            $data['riwayatUjian'] = PesertaUjian::with(['ujian.mapel'])
                ->where('siswa_id', $siswa->id)
                ->where('status', 'selesai')
                ->latest()
                ->take(10)
                ->get();
        }

        return view('dashboard.siswa', $data);
    }
}
