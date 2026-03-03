<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Guru;
use App\Models\Siswa;
use App\Models\Mapel;
use App\Models\BankSoal;
use App\Models\OpsiJawaban;
use App\Models\SesiUjian;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // === USERS ===
        $superadmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@cbtsmk.id',
            'password' => Hash::make('password'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $admin = User::create([
            'name' => 'Admin Sekolah',
            'email' => 'admin@cbtsmk.id',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        // === JURUSAN ===
        $tkj = Jurusan::create(['nama_jurusan' => 'Teknik Komputer dan Jaringan', 'kode_jurusan' => 'TKJ']);
        $tsm = Jurusan::create(['nama_jurusan' => 'Teknik Sepeda Motor', 'kode_jurusan' => 'TSM']);
        $tkr = Jurusan::create(['nama_jurusan' => 'Teknik Kendaraan Ringan', 'kode_jurusan' => 'TKR']);
        $tjkt = Jurusan::create(['nama_jurusan' => 'Teknik Jaringan Komputer dan Telekomunikasi', 'kode_jurusan' => 'TJKT']);

        // === KELAS ===
        $kelasList = [];
        foreach ([$tkj, $tsm, $tkr, $tjkt] as $jurusan) {
            foreach (['10', '11', '12'] as $tingkat) {
                $kelasList[] = Kelas::create([
                    'nama_kelas' => $tingkat . ' ' . $jurusan->kode_jurusan,
                    'jurusan_id' => $jurusan->id,
                    'tingkat' => $tingkat,
                ]);
            }
        }

        // === GURU ===
        $guruNames = [
            ['name' => 'Budi Santoso', 'nip' => '198501012010011001'],
            ['name' => 'Siti Aminah', 'nip' => '198602022011012002'],
            ['name' => 'Ahmad Fauzi', 'nip' => '198703032012013003'],
        ];

        $gurus = [];
        foreach ($guruNames as $g) {
            $userGuru = User::create([
                'name' => $g['name'],
                'email' => strtolower(str_replace(' ', '.', $g['name'])) . '@cbtsmk.id',
                'password' => Hash::make('password'),
                'role' => 'guru',
                'is_active' => true,
            ]);
            $gurus[] = Guru::create([
                'nip' => $g['nip'],
                'nama' => $g['name'],
                'user_id' => $userGuru->id,
            ]);
        }

        // === MAPEL ===
        $mapels = [
            Mapel::create(['nama_mapel' => 'Matematika', 'kode_mapel' => 'MTK', 'is_umum' => true]),
            Mapel::create(['nama_mapel' => 'Bahasa Indonesia', 'kode_mapel' => 'BIN', 'is_umum' => true]),
            Mapel::create(['nama_mapel' => 'Bahasa Inggris', 'kode_mapel' => 'BIG', 'is_umum' => true]),
            Mapel::create(['nama_mapel' => 'Komputer dan Jaringan Dasar', 'kode_mapel' => 'KJD', 'jurusan_id' => $tkj->id]),
            Mapel::create(['nama_mapel' => 'Administrasi Sistem Jaringan', 'kode_mapel' => 'ASJ', 'jurusan_id' => $tkj->id]),
            Mapel::create(['nama_mapel' => 'Pemeliharaan Mesin Sepeda Motor', 'kode_mapel' => 'PMSM', 'jurusan_id' => $tsm->id]),
            Mapel::create(['nama_mapel' => 'Pemeliharaan Kelistrikan Kendaraan Ringan', 'kode_mapel' => 'PKKR', 'jurusan_id' => $tkr->id]),
        ];

        // === SISWA (20 sample) ===
        for ($i = 1; $i <= 20; $i++) {
            $kelasIdx = ($i - 1) % count($kelasList);
            $userSiswa = User::create([
                'name' => 'Siswa ' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'email' => 'siswa' . str_pad($i, 3, '0', STR_PAD_LEFT) . '@cbtsmk.id',
                'password' => Hash::make('password'),
                'role' => 'siswa',
                'is_active' => true,
            ]);
            Siswa::create([
                'nis' => '2024' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'nisn' => '0030' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'nama' => 'Siswa ' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'kelas_id' => $kelasList[$kelasIdx]->id,
                'user_id' => $userSiswa->id,
            ]);
        }

        // === BANK SOAL (Sample Matematika) ===
        $soalMatematika = [
            ['pertanyaan' => 'Berapakah hasil dari 15 × 8?', 'jawaban' => [['A', '100', false], ['B', '110', false], ['C', '120', true], ['D', '130', false], ['E', '140', false]]],
            ['pertanyaan' => 'Jika x + 5 = 12, maka nilai x adalah...', 'jawaban' => [['A', '5', false], ['B', '6', false], ['C', '7', true], ['D', '8', false], ['E', '9', false]]],
            ['pertanyaan' => 'Luas lingkaran dengan jari-jari 7 cm adalah... (π = 22/7)', 'jawaban' => [['A', '144 cm²', false], ['B', '154 cm²', true], ['C', '164 cm²', false], ['D', '174 cm²', false], ['E', '184 cm²', false]]],
            ['pertanyaan' => 'Nilai dari √144 adalah...', 'jawaban' => [['A', '10', false], ['B', '11', false], ['C', '12', true], ['D', '13', false], ['E', '14', false]]],
            ['pertanyaan' => 'Hasil dari 2³ + 3² adalah...', 'jawaban' => [['A', '15', false], ['B', '16', false], ['C', '17', true], ['D', '18', false], ['E', '19', false]]],
            ['pertanyaan' => 'Jika 3x - 6 = 9, maka x = ...', 'jawaban' => [['A', '3', false], ['B', '4', false], ['C', '5', true], ['D', '6', false], ['E', '7', false]]],
            ['pertanyaan' => 'Keliling persegi panjang dengan panjang 10 cm dan lebar 5 cm adalah...', 'jawaban' => [['A', '25 cm', false], ['B', '30 cm', true], ['C', '35 cm', false], ['D', '40 cm', false], ['E', '50 cm', false]]],
            ['pertanyaan' => 'Nilai dari sin 30° adalah...', 'jawaban' => [['A', '1/4', false], ['B', '1/2', true], ['C', '1/3', false], ['D', '√2/2', false], ['E', '√3/2', false]]],
            ['pertanyaan' => 'Berapa jumlah sudut dalam segitiga?', 'jawaban' => [['A', '90°', false], ['B', '180°', true], ['C', '270°', false], ['D', '360°', false], ['E', '120°', false]]],
            ['pertanyaan' => 'Faktor prima dari 30 adalah...', 'jawaban' => [['A', '2, 3, 5', true], ['B', '2, 3, 7', false], ['C', '2, 5, 7', false], ['D', '3, 5, 7', false], ['E', '2, 3, 10', false]]],
        ];

        $difficulties = ['mudah', 'sedang', 'sulit'];

        foreach ($soalMatematika as $idx => $soal) {
            $bankSoal = BankSoal::create([
                'mapel_id' => $mapels[0]->id,
                'guru_id' => $gurus[0]->id,
                'tipe_soal' => 'pg',
                'tingkat_kesulitan' => $difficulties[$idx % 3],
                'bobot_nilai' => 1,
                'pertanyaan' => $soal['pertanyaan'],
                'pembahasan' => 'Pembahasan soal nomor ' . ($idx + 1),
                'status' => 'aktif',
                'kategori' => 'Umum',
            ]);

            foreach ($soal['jawaban'] as $jawaban) {
                OpsiJawaban::create([
                    'bank_soal_id' => $bankSoal->id,
                    'opsi_label' => $jawaban[0],
                    'isi_opsi' => $jawaban[1],
                    'is_correct' => $jawaban[2],
                ]);
            }
        }

        // === SESI UJIAN ===
        SesiUjian::insert([
            ['nama_sesi' => 'Sesi 1', 'jam_mulai' => '07:30', 'jam_selesai' => '09:30', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nama_sesi' => 'Sesi 2', 'jam_mulai' => '10:00', 'jam_selesai' => '12:00', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nama_sesi' => 'Sesi 3', 'jam_mulai' => '13:00', 'jam_selesai' => '15:00', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // === SETTINGS ===
        \App\Models\Setting::insert([
            ['key' => 'school_name', 'value' => 'SMK Negeri 1 Contoh', 'group' => 'general', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'school_address', 'value' => 'Jl. Pendidikan No. 1', 'group' => 'general', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'max_login_attempts', 'value' => '3', 'group' => 'security', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'lock_duration_minutes', 'value' => '30', 'group' => 'security', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
