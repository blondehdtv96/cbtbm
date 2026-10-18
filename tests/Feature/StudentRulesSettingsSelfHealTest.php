<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use App\Services\StudentRulesSettings;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Halaman Pengaturan Sistem merender field dari baris system_settings yang
 * ada. Di server yang belum menjalankan migrasi 2026_09_13_000000, grup
 * "Peraturan Siswa" kosong: field-nya tidak ada dan tidak bisa dibuat dari UI,
 * sementara validasi `required` pada field yang tidak terkirim membuat seluruh
 * pengaturan gagal disimpan.
 *
 * Barisnya kini dipastikan ada setiap kali halaman dibuka atau disimpan, dan
 * `php artisan app:repair-schema` menambalkan kolom yang kurang di server yang
 * file migrasinya sendiri tertinggal.
 *
 * @see \App\Services\StudentRulesSettings
 * @see \App\Console\Commands\RepairSchema
 */
class StudentRulesSettingsSelfHealTest extends TestCase
{
    /**
     * Skema dibangun ulang dari nol tiap test.
     *
     * Sebagian test di kelas ini men-drop kolom, dan DDL `drop column` di MySQL
     * meng-commit transaksi yang sedang jalan — jadi RefreshDatabase yang
     * mengandalkan rollback akan membocorkan skema rusak ke test lain.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh')->run();
    }

    protected function tearDown(): void
    {
        $this->artisan('migrate:fresh')->run();

        parent::tearDown();
    }

    private function superadmin(): User
    {
        return User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin.rules@test.local',
            'username' => 'superadminrules',
            'password' => bcrypt('secret'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);
    }

    public function test_halaman_pengaturan_membuat_baris_peraturan_siswa_yang_hilang(): void
    {
        // Meniru server yang belum menjalankan migrasi 2026_09_13_000000:
        // kolom users-nya belum ada dan barisnya belum dibuat.
        SystemSetting::whereIn('key', StudentRulesSettings::KEYS)->delete();
        SystemSetting::clearCache();

        $this->assertSame(0, SystemSetting::whereIn('key', StudentRulesSettings::KEYS)->count());

        $response = $this->actingAs($this->superadmin())
            ->get(route('superadmin.settings.index'));

        $response->assertOk();

        foreach (StudentRulesSettings::KEYS as $key) {
            $this->assertDatabaseHas('system_settings', ['key' => $key, 'group' => 'student']);
        }

        // Field-nya benar-benar terender, bukan cuma barisnya ada.
        $response->assertSee('student_rules_content', false);
        $response->assertSee('student_rules_version', false);
    }

    public function test_isi_peraturan_default_cukup_panjang_untuk_lolos_validasi_simpan(): void
    {
        // Validasi update() mensyaratkan minimal 100 karakter. Kalau default
        // bikinan ensureExists() lebih pendek, admin tidak bisa menyimpan apa
        // pun sampai mengarang isi peraturan sendiri.
        SystemSetting::whereIn('key', StudentRulesSettings::KEYS)->delete();
        SystemSetting::clearCache();

        app(StudentRulesSettings::class)->ensureExists();

        $content = (string) SystemSetting::where('key', 'student_rules_content')->value('value');

        $this->assertGreaterThanOrEqual(100, mb_strlen($content));
    }

    public function test_simpan_pengaturan_membuat_baris_yang_hilang_dan_menyimpan_nilainya(): void
    {
        SystemSetting::whereIn('key', StudentRulesSettings::KEYS)->delete();
        SystemSetting::clearCache();

        $isi = str_repeat('Siswa wajib mengerjakan ujian dengan jujur. ', 5);

        $response = $this->actingAs($this->superadmin())
            ->from(route('superadmin.settings.index'))
            ->put(route('superadmin.settings.update'), [
                'student_rules_title' => 'Peraturan Ujian Sekolah',
                'student_rules_content' => $isi,
                'student_rules_version' => 1,
            ]);

        $response->assertRedirect(route('superadmin.settings.index'));
        $response->assertSessionHas('success');

        SystemSetting::clearCache();
        $this->assertSame('Peraturan Ujian Sekolah', SystemSetting::get('student_rules_title'));
        $this->assertSame(trim($isi), trim((string) SystemSetting::get('student_rules_content')));
    }

    public function test_simpan_pengaturan_lain_tidak_diblokir_saat_field_peraturan_tidak_terkirim(): void
    {
        SystemSetting::whereIn('key', StudentRulesSettings::KEYS)->delete();
        SystemSetting::clearCache();

        // Form dari halaman lama yang masih ter-cache di browser admin: tidak
        // memuat field peraturan siswa sama sekali.
        $response = $this->actingAs($this->superadmin())
            ->from(route('superadmin.settings.index'))
            ->put(route('superadmin.settings.update'), [
                'app_name' => 'CBT SMK Bina Mandiri',
            ]);

        $response->assertRedirect(route('superadmin.settings.index'));
        $response->assertSessionHasNoErrors();
    }

    public function test_halaman_pengaturan_memperingatkan_saat_kolom_persetujuan_hilang(): void
    {
        Schema::table('users', function ($table) {
            $table->dropColumn(StudentRulesSettings::USER_COLUMNS);
        });

        $this->actingAs($this->superadmin())
            ->get(route('superadmin.settings.index'))
            ->assertOk()
            ->assertSee('Persetujuan siswa belum bisa disimpan')
            ->assertSee('app:repair-schema');
    }

    public function test_repair_schema_menambahkan_kolom_dan_baris_yang_hilang(): void
    {
        Schema::table('users', function ($table) {
            $table->dropColumn(StudentRulesSettings::USER_COLUMNS);
        });
        Schema::table('peserta_ujians', function ($table) {
            $table->dropColumn(['violation_flag', 'violation_type', 'violation_detail', 'violated_at']);
        });
        SystemSetting::whereIn('key', StudentRulesSettings::KEYS)->delete();
        SystemSetting::clearCache();

        $this->artisan('app:repair-schema')->assertSuccessful();

        foreach (StudentRulesSettings::USER_COLUMNS as $column) {
            $this->assertTrue(Schema::hasColumn('users', $column), "users.{$column} belum ditambahkan");
        }

        foreach (['violation_flag', 'violation_type', 'violation_detail', 'violated_at'] as $column) {
            $this->assertTrue(
                Schema::hasColumn('peserta_ujians', $column),
                "peserta_ujians.{$column} belum ditambahkan"
            );
        }

        foreach (StudentRulesSettings::KEYS as $key) {
            $this->assertDatabaseHas('system_settings', ['key' => $key]);
        }
    }

    public function test_repair_schema_aman_dijalankan_berulang(): void
    {
        $this->artisan('app:repair-schema')->assertSuccessful();
        $this->artisan('app:repair-schema')->assertSuccessful();

        $this->assertSame(
            count(StudentRulesSettings::KEYS),
            SystemSetting::whereIn('key', StudentRulesSettings::KEYS)->count()
        );
    }

    public function test_dry_run_tidak_mengubah_apa_pun(): void
    {
        SystemSetting::whereIn('key', StudentRulesSettings::KEYS)->delete();
        SystemSetting::clearCache();
        Schema::table('users', function ($table) {
            $table->dropColumn(StudentRulesSettings::USER_COLUMNS);
        });

        $this->artisan('app:repair-schema', ['--dry-run' => true])->assertSuccessful();

        $this->assertFalse(Schema::hasColumn('users', 'student_rules_ack_version'));
        $this->assertSame(0, SystemSetting::whereIn('key', StudentRulesSettings::KEYS)->count());
    }

    public function test_migrasi_tetap_bisa_jalan_sesudah_repair_schema_menambahkan_kolomnya(): void
    {
        // Setelah app:repair-schema menambahkan kolom secara manual, server
        // yang akhirnya menerima file migrasinya harus tetap bisa menjalankan
        // `migrate` tanpa error 1060 (duplicate column).
        Schema::table('users', function ($table) {
            $table->dropColumn(StudentRulesSettings::USER_COLUMNS);
        });

        $this->artisan('app:repair-schema')->assertSuccessful();

        $migration = require database_path(
            'migrations/2026_09_13_000000_add_student_rules_settings_and_acknowledgement.php'
        );

        $migration->up();

        foreach (StudentRulesSettings::USER_COLUMNS as $column) {
            $this->assertTrue(Schema::hasColumn('users', $column));
        }
    }

    public function test_ensure_exists_tidak_menimpa_peraturan_yang_sudah_disunting_admin(): void
    {
        $isi = str_repeat('Peraturan khusus sekolah ini. ', 10);
        SystemSetting::set('student_rules_content', $isi);
        SystemSetting::set('student_rules_version', '7');
        SystemSetting::clearCache();

        app(StudentRulesSettings::class)->ensureExists();

        SystemSetting::clearCache();
        $this->assertSame($isi, SystemSetting::get('student_rules_content'));
        $this->assertSame('7', SystemSetting::get('student_rules_version'));
    }
}
