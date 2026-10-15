<?php

namespace Tests\Feature;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresi "500 Server Error saat memasukkan username dan password".
 *
 * Middleware throttle membangun kunci rate limit dari field nis/email SEBELUM
 * validasi jalan. Nilai yang bukan string (form dengan nama field array,
 * nis[]=...) membuat strtolower() melempar TypeError, jadi login membalas 500
 * alih-alih pesan kesalahan biasa.
 */
class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    private function siswa(): Siswa
    {
        $jurusan = Jurusan::create(['nama_jurusan' => 'TKJ', 'kode_jurusan' => 'TKJ', 'is_active' => true]);
        $kelas = Kelas::create(['nama_kelas' => '10 TKJ', 'jurusan_id' => $jurusan->id, 'is_active' => true]);

        $user = User::create([
            'name' => 'Siswa Throttle',
            'email' => 'siswa.0033000001@cbtsmk.local',
            'password' => bcrypt('rahasia123'),
            'role' => 'siswa',
            'is_active' => true,
        ]);

        return Siswa::create([
            'nis' => '20243000',
            'nisn' => '0033000001',
            'nama' => 'Siswa Throttle',
            'kelas_id' => $kelas->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_nis_berbentuk_array_tidak_bikin_500(): void
    {
        $response = $this->post(route('login.process'), [
            'nis' => ['20243000'],
            'password' => 'rahasia123',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('nis');
        $this->assertGuest();
    }

    public function test_email_staff_berbentuk_array_tidak_bikin_500(): void
    {
        $response = $this->post(route('staff.login.process'), [
            'email' => ['admin@test.local'],
            'password' => 'rahasia123',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_normal_tetap_jalan_setelah_perbaikan(): void
    {
        $siswa = $this->siswa();

        $this->post(route('login.process'), [
            'nis' => $siswa->nis,
            'password' => 'rahasia123',
        ])->assertRedirect(route('siswa.dashboard'));

        $this->assertAuthenticatedAs($siswa->user);
    }

    public function test_batas_percobaan_form_memberi_pesan_terbaca_bukan_json_mentah(): void
    {
        $siswa = $this->siswa();

        // Batas login: 5 percobaan per menit untuk kombinasi identitas + IP.
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.process'), [
                'nis' => $siswa->nis,
                'password' => 'salah',
            ]);
        }

        $response = $this->post(route('login.process'), [
            'nis' => $siswa->nis,
            'password' => 'rahasia123',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('nis');

        $message = session('errors')->first('nis');
        $this->assertStringContainsString('Terlalu banyak percobaan', $message);
        $this->assertStringNotContainsString('{', $message);
    }

    public function test_endpoint_json_tetap_menerima_429_json(): void
    {
        $siswa = $this->siswa();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('login.process'), [
                'nis' => $siswa->nis,
                'password' => 'salah',
            ]);
        }

        $this->postJson(route('login.process'), [
            'nis' => $siswa->nis,
            'password' => 'salah',
        ])
            ->assertStatus(429)
            ->assertJsonStructure(['error', 'retry_after']);
    }
}
