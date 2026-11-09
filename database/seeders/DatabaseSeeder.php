<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Guru;
use App\Models\Mapel;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // === SUPERADMIN ===
        $superadmins = [
            ['name' => 'Super Admin 1', 'email' => 'superadmin1@cbtsmk.id'],
            ['name' => 'Super Admin 2', 'email' => 'superadmin2@cbtsmk.id'],
            ['name' => 'Super Admin 3', 'email' => 'superadmin3@cbtsmk.id'],
        ];

        foreach ($superadmins as $sa) {
            User::firstOrCreate(['email' => $sa['email']], [
                'name'           => $sa['name'],
                'password'       => Hash::make('password'),
                'plain_password' => 'password',
                'role'           => 'superadmin',
                'is_active'      => true,
            ]);
        }

        // === ADMIN ===
        User::firstOrCreate(['email' => 'admin@cbtsmk.id'], [
            'name'           => 'Administrator',
            'username'       => 'admin',
            'password'       => Hash::make('admin123'),
            'plain_password' => 'admin123',
            'role'           => 'admin',
            'is_active'      => true,
        ]);

        // === GURU + MAPEL ===
        $mapelMtk = Mapel::firstOrCreate(['kode_mapel' => 'MTK'], [
            'nama_mapel' => 'Matematika',
            'is_umum'    => true,
            'is_active'  => true,
        ]);

        $guruUser = User::firstOrCreate(['email' => 'guru@cbtsmk.id'], [
            'name'           => 'Guru Matematika',
            'username'       => 'guru',
            'password'       => Hash::make('guru123'),
            'plain_password' => 'guru123',
            'role'           => 'guru',
            'is_active'      => true,
        ]);
        $guru = Guru::firstOrCreate(['user_id' => $guruUser->id], ['nama' => $guruUser->name]);
        $guru->mapels()->syncWithoutDetaching([$mapelMtk->id]);

        // === JURUSAN & KELAS ===
        $jurusanList = [
            [
                'kode'  => 'TKJ',
                'nama'  => 'Teknik Komputer dan Jaringan',
                'kelas' => ['X TKJ 1', 'X TKJ 2', 'XI TKJ 1', 'XI TKJ 2', 'XII TKJ 1', 'XII TKJ 2'],
            ],
            [
                'kode'  => 'TSM',
                'nama'  => 'Teknik Sepeda Motor',
                'kelas' => ['X TSM 1', 'X TSM 2', 'XI TSM 1', 'XI TSM 2', 'XII TSM 1', 'XII TSM 2'],
            ],
            [
                'kode'  => 'TKR',
                'nama'  => 'Teknik Kendaraan Ringan',
                'kelas' => ['X TKR 1', 'X TKR 2', 'XI TKR 1', 'XI TKR 2', 'XII TKR 1', 'XII TKR 2'],
            ],
        ];

        foreach ($jurusanList as $j) {
            $jurusan = Jurusan::firstOrCreate(['kode_jurusan' => $j['kode']], [
                'nama_jurusan' => $j['nama'],
                'deskripsi'    => $j['nama'],
                'is_active'    => true,
            ]);

            foreach ($j['kelas'] as $namaKelas) {
                // Ambil tingkat dari nama kelas (X=10, XI=11, XII=12)
                preg_match('/^(X{1,3}I{0,3})/', $namaKelas, $m);
                $tingkat = match($m[1] ?? '') {
                    'X'    => '10',
                    'XI'   => '11',
                    'XII'  => '12',
                    default => '10',
                };

                Kelas::firstOrCreate(
                    ['nama_kelas' => $namaKelas, 'jurusan_id' => $jurusan->id],
                    ['tingkat' => $tingkat, 'is_active' => true]
                );
            }
        }

        // === SISWA CONTOH (1 per jurusan) ===
        $tkj1 = Kelas::where('nama_kelas', 'X TKJ 1')->first();
        $tsm1 = Kelas::where('nama_kelas', 'X TSM 1')->first();
        $tkr1 = Kelas::where('nama_kelas', 'X TKR 1')->first();

        $siswaData = [
            [
                'user'  => ['name' => 'Budi Santoso',  'email' => 'budi@cbtsmk.id',  'username' => 'budi.santoso'],
                'siswa' => ['nis' => '2024001', 'nisn' => '0012345678', 'nama' => 'Budi Santoso',  'kelas_id' => $tkj1?->id],
            ],
            [
                'user'  => ['name' => 'Siti Rahayu',   'email' => 'siti@cbtsmk.id',  'username' => 'siti.rahayu'],
                'siswa' => ['nis' => '2024002', 'nisn' => '0012345679', 'nama' => 'Siti Rahayu',   'kelas_id' => $tsm1?->id],
            ],
            [
                'user'  => ['name' => 'Ahmad Fauzi',   'email' => 'ahmad@cbtsmk.id', 'username' => 'ahmad.fauzi'],
                'siswa' => ['nis' => '2024003', 'nisn' => '0012345680', 'nama' => 'Ahmad Fauzi',   'kelas_id' => $tkr1?->id],
            ],
        ];

        foreach ($siswaData as $data) {
            $user = User::firstOrCreate(['email' => $data['user']['email']], [
                'name'           => $data['user']['name'],
                'username'       => $data['user']['username'],
                'password'       => Hash::make('siswa123'),
                'plain_password' => 'siswa123',
                'role'           => 'siswa',
                'is_active'      => true,
            ]);

            Siswa::firstOrCreate(['nis' => $data['siswa']['nis']], [
                'nisn'     => $data['siswa']['nisn'],
                'nama'     => $data['siswa']['nama'],
                'kelas_id' => $data['siswa']['kelas_id'],
                'user_id'  => $user->id,
            ]);
        }
    }
}
