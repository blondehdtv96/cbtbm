<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tombol hapus pada monitoring Anti-Cheat: per baris, bulk terpilih,
 * dan pembersihan massal (semua / sesuai filter / retensi hari).
 */
class AntiCheatLogDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::create([
            'name' => 'Admin Anti Cheat',
            'email' => 'admin.anticheat@test.local',
            'username' => 'adminanticheat',
            'password' => bcrypt('secret'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    private function violation(User $siswa, string $createdAt = 'now'): ActivityLog
    {
        $log = ActivityLog::create([
            'user_id' => $siswa->id,
            'action' => 'cheat_detected',
            'module' => 'exam',
            'description' => 'Pelanggaran ujian',
            'ip_address' => '127.0.0.1',
            'data' => ['siswa_nama' => $siswa->name, 'violation_type' => 'tab_switch'],
        ]);

        $log->forceFill(['created_at' => Carbon::parse($createdAt)])->save();

        return $log;
    }

    private function siswaUser(string $name, string $email): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('secret'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
    }

    public function test_hapus_satu_log_pelanggaran(): void
    {
        $siswa = $this->siswaUser('Siswa Satu', 'siswa1.anticheat@test.local');
        $log = $this->violation($siswa);
        $lain = $this->violation($this->siswaUser('Siswa Dua', 'siswa2.anticheat@test.local'));

        $this->actingAs($this->adminUser())
            ->delete(route('admin.anti-cheat.destroy', $log))
            ->assertRedirect();

        $this->assertDatabaseMissing('activity_logs', ['id' => $log->id]);
        $this->assertDatabaseHas('activity_logs', ['id' => $lain->id]);
    }

    public function test_log_selain_pelanggaran_tidak_bisa_dihapus_dari_halaman_ini(): void
    {
        $admin = $this->adminUser();
        $log = ActivityLog::create([
            'user_id' => $admin->id,
            'action' => 'login',
            'module' => 'auth',
            'description' => 'Login admin',
            'ip_address' => '127.0.0.1',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.anti-cheat.destroy', $log))
            ->assertRedirect();

        $this->assertDatabaseHas('activity_logs', ['id' => $log->id]);
    }

    public function test_hapus_beberapa_log_terpilih(): void
    {
        $siswa = $this->siswaUser('Siswa Bulk', 'siswa.bulk@test.local');
        $satu = $this->violation($siswa);
        $dua = $this->violation($siswa);
        $sisa = $this->violation($siswa);

        $this->actingAs($this->adminUser())
            ->delete(route('admin.anti-cheat.bulk-destroy'), ['ids' => [$satu->id, $dua->id]])
            ->assertRedirect();

        $this->assertDatabaseMissing('activity_logs', ['id' => $satu->id]);
        $this->assertDatabaseMissing('activity_logs', ['id' => $dua->id]);
        $this->assertDatabaseHas('activity_logs', ['id' => $sisa->id]);
    }

    public function test_bersihkan_semua_log_pelanggaran_tanpa_menyentuh_log_lain(): void
    {
        $siswa = $this->siswaUser('Siswa Clear', 'siswa.clear@test.local');
        $this->violation($siswa);
        $this->violation($siswa, '-40 days');

        $admin = $this->adminUser();
        $logLain = ActivityLog::create([
            'user_id' => $admin->id,
            'action' => 'import',
            'module' => 'siswa',
            'description' => 'Import siswa',
            'ip_address' => '127.0.0.1',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.anti-cheat.clear'))
            ->assertRedirect(route('admin.anti-cheat.index'));

        $this->assertSame(0, ActivityLog::where('action', 'cheat_detected')->count());
        $this->assertDatabaseHas('activity_logs', ['id' => $logLain->id]);
    }

    public function test_bersihkan_hanya_log_lebih_lama_dari_retensi(): void
    {
        $siswa = $this->siswaUser('Siswa Retensi', 'siswa.retensi@test.local');
        $baru = $this->violation($siswa, '-2 days');
        $lama = $this->violation($siswa, '-40 days');

        $this->actingAs($this->adminUser())
            ->delete(route('admin.anti-cheat.clear'), ['older_than' => 30])
            ->assertRedirect(route('admin.anti-cheat.index'));

        $this->assertDatabaseHas('activity_logs', ['id' => $baru->id]);
        $this->assertDatabaseMissing('activity_logs', ['id' => $lama->id]);
    }

    public function test_bersihkan_mengikuti_filter_nama_siswa_yang_aktif(): void
    {
        $target = $this->violation($this->siswaUser('Budi Santoso', 'budi.anticheat@test.local'));
        $aman = $this->violation($this->siswaUser('Citra Dewi', 'citra.anticheat@test.local'));

        $this->actingAs($this->adminUser())
            ->delete(route('admin.anti-cheat.clear'), ['search' => 'Budi'])
            ->assertRedirect(route('admin.anti-cheat.index'));

        $this->assertDatabaseMissing('activity_logs', ['id' => $target->id]);
        $this->assertDatabaseHas('activity_logs', ['id' => $aman->id]);
    }

    public function test_retensi_di_luar_pilihan_ditolak(): void
    {
        $log = $this->violation($this->siswaUser('Siswa Validasi', 'siswa.validasi@test.local'), '-500 days');

        $this->actingAs($this->adminUser())
            ->delete(route('admin.anti-cheat.clear'), ['older_than' => 1])
            ->assertSessionHasErrors('older_than');

        $this->assertDatabaseHas('activity_logs', ['id' => $log->id]);
    }
}
