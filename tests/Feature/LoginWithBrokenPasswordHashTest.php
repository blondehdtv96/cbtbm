<?php

namespace Tests\Feature;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regresi "500 Server Error saat siswa login memakai datanya sendiri".
 *
 * config/hashing.php memasang 'verify' => true, jadi Hash::check() MELEMPAR
 * RuntimeException('This password does not use the Bcrypt algorithm.') begitu
 * kolom users.password tidak berisi hash bcrypt yang sah. Akun lama (impor
 * versi awal, suntingan manual lewat phpMyAdmin, migrasi dari sistem lain)
 * bisa menyimpan teks biasa / md5 / argon2 / string kosong, dan satu baris
 * seperti itu membuat login membalas 500 — bukan pesan kesalahan biasa.
 *
 * Semua kasus di sini menulis kolom password lewat query builder supaya cast
 * 'hashed' pada model User tidak ikut membetulkan nilainya, persis seperti
 * data yang sudah ada di server.
 */
class LoginWithBrokenPasswordHashTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  string  $rawPassword  nilai mentah untuk kolom users.password
     */
    private function siswaWithRawPassword(string $rawPassword, ?string $plainPassword = null): Siswa
    {
        $jurusan = Jurusan::create(['nama_jurusan' => 'TKJ', 'kode_jurusan' => 'TKJ', 'is_active' => true]);
        $kelas = Kelas::create(['nama_kelas' => '10 TKJ', 'jurusan_id' => $jurusan->id, 'is_active' => true]);

        $user = User::create([
            'name' => 'Siswa Hash Rusak',
            'email' => 'siswa.0034000001@cbtsmk.local',
            'password' => bcrypt('placeholder'),
            'plain_password' => $plainPassword,
            'role' => 'siswa',
            'is_active' => true,
        ]);

        // Menulis langsung ke tabel: melewati cast 'hashed' yang kalau tidak
        // begitu akan otomatis mem-bcrypt nilainya.
        DB::table('users')->where('id', $user->id)->update(['password' => $rawPassword]);

        return Siswa::create([
            'nis' => '20244000',
            'nisn' => '0034000001',
            'nama' => 'Siswa Hash Rusak',
            'kelas_id' => $kelas->id,
            'user_id' => $user->id,
        ]);
    }

    public static function brokenHashProvider(): array
    {
        return [
            'teks biasa' => ['rahasia123'],
            'md5' => ['e99a18c428cb38d5f260853678922e03'],
            'sha1' => ['5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8'],
            'string kosong' => [''],
            'bcrypt terpotong' => ['$2y$12$abc'],
        ];
    }

    /**
     * @dataProvider brokenHashProvider
     */
    public function test_hash_rusak_tidak_bikin_500_saat_password_salah(string $rawPassword): void
    {
        $siswa = $this->siswaWithRawPassword($rawPassword);

        $response = $this->post(route('login.process'), [
            'nis' => $siswa->nis,
            'password' => 'password-yang-salah',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('nis');
        $this->assertGuest();
    }

    public function test_hash_argon2_tidak_bikin_500(): void
    {
        $siswa = $this->siswaWithRawPassword(password_hash('rahasia123', PASSWORD_ARGON2I));

        $this->post(route('login.process'), [
            'nis' => $siswa->nis,
            'password' => 'password-yang-salah',
        ])->assertStatus(302)->assertSessionHasErrors('nis');

        $this->assertGuest();
    }

    /**
     * Kolom password berisi teks biasa (bug impor lama): siswa yang mengetik
     * password itu harus bisa masuk, dan hash-nya langsung dibetulkan.
     */
    public function test_password_teks_biasa_bisa_login_dan_hash_dibetulkan(): void
    {
        $siswa = $this->siswaWithRawPassword('rahasia123');

        $this->post(route('login.process'), [
            'nis' => $siswa->nis,
            'password' => 'rahasia123',
        ])->assertRedirect(route('siswa.dashboard'));

        $this->assertAuthenticatedAs($siswa->user);

        $stored = DB::table('users')->where('id', $siswa->user_id)->value('password');
        $this->assertSame('bcrypt', password_get_info($stored)['algoName']);
        $this->assertTrue(password_verify('rahasia123', $stored));
    }

    /**
     * Hash rusak tapi plain_password masih ada (kredensial yang tercetak di
     * kartu peserta): password siswa tetap berlaku.
     */
    public function test_pulih_dari_plain_password(): void
    {
        $siswa = $this->siswaWithRawPassword(md5('rahasia123'), 'rahasia123');

        $this->post(route('login.process'), [
            'nis' => $siswa->nis,
            'password' => 'rahasia123',
        ])->assertRedirect(route('siswa.dashboard'));

        $this->assertAuthenticatedAs($siswa->user);

        $stored = DB::table('users')->where('id', $siswa->user_id)->value('password');
        $this->assertSame('bcrypt', password_get_info($stored)['algoName']);
    }

    /**
     * Hash rusak dan password aslinya tidak diketahui: user harus dapat pesan
     * yang mengarahkan ke admin, bukan "password salah" dan bukan 500.
     */
    public function test_hash_rusak_tanpa_pemulihan_memberi_pesan_hubungi_admin(): void
    {
        $siswa = $this->siswaWithRawPassword(md5('password-lama-yang-hilang'));

        $this->post(route('login.process'), [
            'nis' => $siswa->nis,
            'password' => 'coba-coba',
        ])->assertStatus(302);

        $this->assertStringContainsString(
            'Hubungi administrator',
            session('errors')->first('nis')
        );
        $this->assertGuest();
    }

    public function test_staff_dengan_hash_rusak_tidak_bikin_500(): void
    {
        $admin = User::create([
            'name' => 'Admin Hash Rusak',
            'email' => 'admin.hashrusak@test.local',
            'username' => 'adminhashrusak',
            'password' => bcrypt('placeholder'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        DB::table('users')->where('id', $admin->id)->update(['password' => md5('rahasia123')]);

        $this->post(route('staff.login.process'), [
            'email' => $admin->email,
            'password' => 'password-yang-salah',
        ])->assertStatus(302)->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_staff_dengan_password_teks_biasa_bisa_login_dan_hash_dibetulkan(): void
    {
        $admin = User::create([
            'name' => 'Admin Teks Biasa',
            'email' => 'admin.teksbiasa@test.local',
            'username' => 'adminteksbiasa',
            'password' => bcrypt('placeholder'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        DB::table('users')->where('id', $admin->id)->update(['password' => 'rahasia123']);

        $this->post(route('staff.login.process'), [
            'email' => $admin->email,
            'password' => 'rahasia123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin->fresh());

        $stored = DB::table('users')->where('id', $admin->id)->value('password');
        $this->assertSame('bcrypt', password_get_info($stored)['algoName']);
    }

    /**
     * Login yang sehat tidak boleh ikut berubah perilakunya.
     */
    public function test_login_normal_tetap_jalan(): void
    {
        $siswa = $this->siswaWithRawPassword(password_hash('rahasia123', PASSWORD_BCRYPT));

        $this->post(route('login.process'), [
            'nis' => $siswa->nis,
            'password' => 'rahasia123',
        ])->assertRedirect(route('siswa.dashboard'));

        $this->assertAuthenticatedAs($siswa->user);
    }
}
