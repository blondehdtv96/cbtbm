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
        $user = auth()->user();
        $siswa = $user->siswa;
        $rulesEnabled = filter_var(
            \App\Models\SystemSetting::get('student_rules_enabled', '1'),
            FILTER_VALIDATE_BOOLEAN
        );
        $rulesVersion = max(1, (int) \App\Models\SystemSetting::get('student_rules_version', 1));
        $mustAcceptRules = $rulesEnabled && (int) $user->student_rules_ack_version < $rulesVersion;

        if ($mustAcceptRules) {
            session()->put('student_rules_pending_version', $rulesVersion);
        } else {
            session()->forget('student_rules_pending_version');
        }

        $data = [
            'ujianTersedia' => collect(),
            'riwayatUjian' => collect(),
            'pelanggaranAktif' => collect(),
            'siswa' => $siswa,
            'studentRules' => [
                'show' => $mustAcceptRules,
                'title' => (string) \App\Models\SystemSetting::get(
                    'student_rules_title',
                    'Peraturan Penggunaan CBT untuk Siswa'
                ),
                'content' => (string) \App\Models\SystemSetting::get(
                    'student_rules_content',
                    'Baca dan patuhi seluruh peraturan ujian yang ditetapkan sekolah.'
                ),
                'version' => $rulesVersion,
            ],
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

            // Alert pelanggaran anti-cheat: bukan flash session (hilang sekali
            // reload), tapi persisten dari database, supaya tetap tampil setiap
            // dashboard dibuka sampai admin reset peserta ujian yang bersangkutan.
            $data['pelanggaranAktif'] = PesertaUjian::with('ujian')
                ->where('siswa_id', $siswa->id)
                ->where('violation_flag', true)
                ->latest('violated_at')
                ->get();
        }

        return view('dashboard.siswa', $data);
    }

    public function acknowledgeStudentRules(Request $request)
    {
        $rulesEnabled = filter_var(
            \App\Models\SystemSetting::get('student_rules_enabled', '1'),
            FILTER_VALIDATE_BOOLEAN
        );

        if (!$rulesEnabled) {
            $request->session()->forget('student_rules_pending_version');

            return response()->json(['success' => true, 'enabled' => false]);
        }

        $rulesVersion = max(1, (int) \App\Models\SystemSetting::get('student_rules_version', 1));
        $request->user()->forceFill([
            'student_rules_ack_version' => $rulesVersion,
            'student_rules_acknowledged_at' => now(),
        ])->save();

        $request->session()->forget('student_rules_pending_version');

        return response()->json([
            'success' => true,
            'version' => $rulesVersion,
            'acknowledged_at' => now()->toIso8601String(),
        ]);
    }
}
