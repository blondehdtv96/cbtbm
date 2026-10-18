<?php

namespace Tests\Feature;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Server produksi yang belum menjalankan `php artisan migrate` sesudah deploy
 * punya tabel tanpa kolom yang diminta kode baru. Dulu kondisi itu membuat
 * seluruh siswa menerima "500 Server Error" saat login — bukan di verifikasi
 * password (yang sudah berhasil), melainkan di pembukuan sesudahnya dan di
 * query alert anti-cheat pada dashboard tujuan redirect.
 *
 * Kolom yang dibutuhkan sengaja di-drop di sini untuk meniru server seperti
 * itu: login dan dashboard harus tetap jalan, dan penyebabnya dilaporkan oleh
 * `php artisan diagnose:login`, bukan oleh halaman 500 di hadapan siswa.
 *
 * @see \App\Http\Controllers\Auth\LoginController::afterLogin()
 * @see \App\Console\Commands\DiagnoseLogin
 */
class LoginSurvivesPendingMigrationTest extends TestCase
{
    /**
     * Skema dibangun ulang dari nol tiap test, bukan lewat RefreshDatabase
     * maupun DatabaseMigrations.
     *
     * RefreshDatabase mengandalkan rollback transaksi, sedangkan DDL `drop
     * column` di MySQL meng-commit transaksi yang sedang jalan — skema rusak
     * bikinan test ini akan bocor ke test berikutnya. DatabaseMigrations juga
     * tidak cocok karena teardown-nya menjalankan `migrate:rollback`, dan
     * rollback migrasi gagal untuk kolom yang sudah sengaja dihapus di sini.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh')->run();
    }

    /**
     * Kembalikan database ke skema utuh dan kosong.
     *
     * Test di kelas ini tidak dibungkus transaksi, jadi baris dan kolom yang
     * dihapusnya akan terbawa ke kelas test lain yang memakai RefreshDatabase
     * — kelas-kelas itu hanya membuka transaksi dan menganggap skemanya sudah
     * benar serta tabelnya kosong.
     */
    protected function tearDown(): void
    {
        $this->artisan('migrate:fresh')->run();

        parent::tearDown();
    }

    private function makeSiswa(string $nis, string $password): Siswa
    {
        $jurusan = Jurusan::create(['nama_jurusan' => 'TKJ', 'kode_jurusan' => 'TKJ', 'is_active' => true]);
        $kelas = Kelas::create(['nama_kelas' => '10 TKJ', 'jurusan_id' => $jurusan->id, 'is_active' => true]);

        $user = User::create([
            'name' => 'Siswa Migrasi Tertunda',
            'email' => $nis . '@siswa.test.local',
            'username' => $nis,
            'password' => bcrypt($password),
            'plain_password' => $password,
            'role' => 'siswa',
            'is_active' => true,
        ]);

        return Siswa::create([
            'nis' => $nis,
            'nisn' => '00' . $nis,
            'nama' => 'Siswa Migrasi Tertunda',
            'kelas_id' => $kelas->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_login_tetap_berhasil_walau_kolom_pembukuan_belum_termigrasi(): void
    {
        $siswa = $this->makeSiswa('20250001', 'rahasia123');

        // Meniru server yang masih memakai skema sebelum
        // 2026_09_13_000000_add_student_rules_settings_and_acknowledgement.
        Schema::table('users', function ($table) {
            $table->dropColumn(['last_login', 'student_rules_ack_version']);
        });

        $response = $this->post(route('login.process'), [
            'nis' => $siswa->nis,
            'password' => 'rahasia123',
        ]);

        $response->assertRedirect(route('siswa.dashboard'));
        $this->assertAuthenticatedAs($siswa->user->fresh());
    }

    public function test_login_tetap_berhasil_walau_tabel_activity_logs_hilang(): void
    {
        $siswa = $this->makeSiswa('20250002', 'rahasia123');

        Schema::drop('activity_logs');

        $response = $this->post(route('login.process'), [
            'nis' => $siswa->nis,
            'password' => 'rahasia123',
        ]);

        $response->assertRedirect(route('siswa.dashboard'));
        $this->assertAuthenticatedAs($siswa->user->fresh());
    }

    public function test_dashboard_siswa_tetap_terbuka_walau_kolom_pelanggaran_belum_termigrasi(): void
    {
        $siswa = $this->makeSiswa('20250003', 'rahasia123');

        // Meniru server yang belum menjalankan
        // 2026_09_18_000313_add_violation_fields_to_peserta_ujians_table.
        Schema::table('peserta_ujians', function ($table) {
            $table->dropColumn(['violation_flag', 'violation_type', 'violation_detail', 'violated_at']);
        });

        $this->post(route('login.process'), [
            'nis' => $siswa->nis,
            'password' => 'rahasia123',
        ])->assertRedirect(route('siswa.dashboard'));

        $this->actingAs($siswa->user->fresh())
            ->get(route('siswa.dashboard'))
            ->assertOk();
    }

    public function test_setuju_peraturan_tidak_balas_500_walau_kolom_persetujuan_belum_termigrasi(): void
    {
        $siswa = $this->makeSiswa('20250007', 'rahasia123');

        // Popup peraturan menghalangi seluruh dashboard: kalau tombol "Setuju"
        // balas 500, siswa tidak punya jalan sama sekali ke daftar ujian.
        Schema::table('users', function ($table) {
            $table->dropColumn(['student_rules_ack_version', 'student_rules_acknowledged_at']);
        });

        $response = $this->actingAs($siswa->user->fresh())
            ->postJson(route('siswa.rules.acknowledge'));

        $response->assertOk();
        $response->assertJson(['success' => true, 'persisted' => false]);
        $this->assertFalse(session()->has('student_rules_pending_version'));
    }

    public function test_password_salah_tetap_balas_pesan_bukan_500_walau_kolom_percobaan_hilang(): void
    {
        $siswa = $this->makeSiswa('20250004', 'rahasia123');

        Schema::table('users', function ($table) {
            $table->dropColumn('login_attempts');
        });

        $response = $this->from(route('login'))->post(route('login.process'), [
            'nis' => $siswa->nis,
            'password' => 'password-yang-salah',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('nis');
        $this->assertGuest();
    }

    public function test_perintah_diagnose_login_melaporkan_kolom_yang_hilang(): void
    {
        $siswa = $this->makeSiswa('20250005', 'rahasia123');

        Schema::table('peserta_ujians', function ($table) {
            $table->dropColumn(['violation_flag', 'violation_type', 'violation_detail', 'violated_at']);
        });

        $this->artisan('diagnose:login', ['nis' => $siswa->nis, '--password' => 'rahasia123'])
            ->expectsOutputToContain('violation_flag')
            ->assertFailed();
    }

    public function test_perintah_diagnose_login_bersih_pada_skema_lengkap(): void
    {
        $siswa = $this->makeSiswa('20250006', 'rahasia123');

        $this->artisan('diagnose:login', ['nis' => $siswa->nis, '--password' => 'rahasia123'])
            ->assertSuccessful();
    }
}
