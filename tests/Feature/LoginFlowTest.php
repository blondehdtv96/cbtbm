<?php

namespace Tests\Feature;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Login siswa (NIS + password) dan login staff (email + password) harus
 * berhasil sampai halaman dashboard — bukan 500 Server Error.
 */
class LoginFlowTest extends TestCase
{
    use RefreshDatabase;

    private function siswaUser(): Siswa
    {
        $jurusan = Jurusan::create(['nama_jurusan' => 'TKJ', 'kode_jurusan' => 'TKJ', 'is_active' => true]);
        $kelas = Kelas::create(['nama_kelas' => '10 TKJ', 'jurusan_id' => $jurusan->id, 'is_active' => true]);

        $user = User::create([
            'name' => 'Siswa Login',
            'email' => 'siswa.0031000001@cbtsmk.local',
            'password' => bcrypt('rahasia123'),
            'plain_password' => 'rahasia123',
            'role' => 'siswa',
            'is_active' => true,
        ]);

        return Siswa::create([
            'nis' => '20241000',
            'nisn' => '0031000001',
            'nama' => 'Siswa Login',
            'kelas_id' => $kelas->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_siswa_bisa_login_dengan_nis_dan_password(): void
    {
        $siswa = $this->siswaUser();

        $response = $this->post(route('login.process'), [
            'nis' => $siswa->nis,
            'password' => 'rahasia123',
        ]);

        $response->assertRedirect(route('siswa.dashboard'));
        $this->assertAuthenticatedAs($siswa->user);

        // Dashboard-nya juga harus terbuka, bukan 500.
        $this->get(route('siswa.dashboard'))->assertOk();
    }

    public function test_staff_bisa_login_dengan_email_dan_password(): void
    {
        $admin = User::create([
            'name' => 'Admin Login',
            'email' => 'admin.login@test.local',
            'username' => 'adminlogin',
            'password' => bcrypt('rahasia123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->post(route('staff.login.process'), [
            'email' => $admin->email,
            'password' => 'rahasia123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);

        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_password_salah_memberi_pesan_bukan_error_server(): void
    {
        $siswa = $this->siswaUser();

        $this->post(route('login.process'), [
            'nis' => $siswa->nis,
            'password' => 'salah-total',
        ])->assertSessionHasErrors('nis');

        $this->assertGuest();
    }

    /**
     * NISN bersifat nullable dan bukan identitas login, jadi mengisinya di
     * form login tidak boleh meloloskan siapa pun.
     */
    public function test_nisn_tidak_bisa_dipakai_untuk_login(): void
    {
        $siswa = $this->siswaUser();
        $this->assertNotSame($siswa->nis, $siswa->nisn);

        $this->post(route('login.process'), [
            'nis' => $siswa->nisn,
            'password' => 'rahasia123',
        ])->assertSessionHasErrors('nis');

        $this->assertGuest();
    }

    /**
     * Siswa tanpa NISN (kolomnya nullable) tetap harus bisa masuk memakai NIS.
     */
    public function test_siswa_tanpa_nisn_tetap_bisa_login(): void
    {
        $siswa = $this->siswaUser();
        $siswa->update(['nisn' => null]);

        $this->post(route('login.process'), [
            'nis' => $siswa->nis,
            'password' => 'rahasia123',
        ])->assertRedirect(route('siswa.dashboard'));

        $this->assertAuthenticatedAs($siswa->user);
        $this->get(route('siswa.dashboard'))->assertOk();
    }

    public function test_form_login_menampilkan_field_nis(): void
    {
        $response = $this->get(route('login'))->assertOk();

        $response->assertSee('name="nis"', false);
        $response->assertDontSee('name="nisn"', false);
    }
}
