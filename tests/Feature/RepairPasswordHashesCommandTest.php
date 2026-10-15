<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * users:repair-password-hashes membersihkan baris users yang kolom
 * password-nya bukan bcrypt — akun yang sebelumnya memicu 500 saat login.
 */
class RepairPasswordHashesCommandTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRawPassword(string $email, string $rawPassword, ?string $plainPassword = null): User
    {
        $user = User::create([
            'name' => $email,
            'email' => $email,
            'password' => bcrypt('placeholder'),
            'plain_password' => $plainPassword,
            'role' => 'siswa',
            'is_active' => true,
        ]);

        // Melewati cast 'hashed' supaya nilainya tersimpan apa adanya.
        DB::table('users')->where('id', $user->id)->update(['password' => $rawPassword]);

        return $user;
    }

    private function storedPassword(User $user): string
    {
        return (string) DB::table('users')->where('id', $user->id)->value('password');
    }

    public function test_memulihkan_dari_plain_password_tanpa_mengubah_password_siswa(): void
    {
        $user = $this->userWithRawPassword('pulih@test.local', md5('rahasia123'), 'rahasia123');

        $this->artisan('users:repair-password-hashes')->assertSuccessful();

        $stored = $this->storedPassword($user);
        $this->assertSame('bcrypt', password_get_info($stored)['algoName']);
        // Password yang dipegang siswa tetap berlaku.
        $this->assertTrue(password_verify('rahasia123', $stored));
    }

    public function test_akun_tanpa_plain_password_diberi_password_baru(): void
    {
        $user = $this->userWithRawPassword('reset@test.local', md5('hilang'));

        $this->artisan('users:repair-password-hashes')->assertSuccessful();

        $fresh = $user->fresh();
        $stored = $this->storedPassword($user);

        $this->assertSame('bcrypt', password_get_info($stored)['algoName']);
        // plain_password diisi supaya kartu peserta bisa dicetak ulang.
        $this->assertNotEmpty($fresh->plain_password);
        $this->assertTrue(password_verify($fresh->plain_password, $stored));
    }

    public function test_dry_run_tidak_mengubah_data(): void
    {
        $user = $this->userWithRawPassword('dryrun@test.local', 'teks-biasa');

        $this->artisan('users:repair-password-hashes', ['--dry-run' => true])
            ->assertSuccessful();

        $this->assertSame('teks-biasa', $this->storedPassword($user));
    }

    public function test_akun_sehat_tidak_disentuh(): void
    {
        $user = User::create([
            'name' => 'Sehat',
            'email' => 'sehat@test.local',
            'password' => bcrypt('rahasia123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $before = $this->storedPassword($user);

        $this->artisan('users:repair-password-hashes')->assertSuccessful();

        $this->assertSame($before, $this->storedPassword($user));
    }

    public function test_siswa_bisa_login_setelah_command_dijalankan(): void
    {
        $jurusan = \App\Models\Jurusan::create(['nama_jurusan' => 'TKJ', 'kode_jurusan' => 'TKJ', 'is_active' => true]);
        $kelas = \App\Models\Kelas::create(['nama_kelas' => '10 TKJ', 'jurusan_id' => $jurusan->id, 'is_active' => true]);

        $user = $this->userWithRawPassword('siswa.login@cbtsmk.local', sha1('rahasia123'), 'rahasia123');

        \App\Models\Siswa::create([
            'nis' => '20245000',
            'nisn' => '0035000001',
            'nama' => 'Siswa Pulih',
            'kelas_id' => $kelas->id,
            'user_id' => $user->id,
        ]);

        $this->artisan('users:repair-password-hashes')->assertSuccessful();

        $this->post(route('login.process'), [
            'nis' => '20245000',
            'password' => 'rahasia123',
        ])->assertRedirect(route('siswa.dashboard'));

        $this->assertAuthenticatedAs($user->fresh());
    }
}
