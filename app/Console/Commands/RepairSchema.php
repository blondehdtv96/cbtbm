<?php

namespace App\Console\Commands;

use App\Services\StudentRulesSettings;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom dan baris pengaturan yang dibutuhkan aplikasi tapi belum
 * ada di database.
 *
 * Normalnya ini pekerjaan `php artisan migrate`. Perintah ini untuk server yang
 * file migrasinya sendiri tertinggal — di situ `migrate` melaporkan "tidak ada
 * yang tertunda" padahal kolomnya belum ada, karena yang dibandingkan adalah
 * daftar berkas di database/migrations, bukan kebutuhan kodenya. Akibatnya
 * siswa tidak bisa login dan halaman Pengaturan Sistem kehilangan seluruh
 * field "Peraturan Siswa".
 *
 *     php artisan app:repair-schema --dry-run   # lihat dulu
 *     php artisan app:repair-schema             # perbaiki
 *
 * Aman dijalankan berulang: tiap kolom dicek dulu, dan tidak ada data yang
 * ditimpa. Migrasi aslinya juga sudah dibuat idempoten, jadi `migrate --force`
 * sesudah ini tidak akan gagal dengan error 1060 (duplicate column).
 *
 * @see \App\Console\Commands\DiagnoseLogin untuk mendeteksi apa yang kurang.
 */
class RepairSchema extends Command
{
    protected $signature = 'app:repair-schema
                            {--dry-run : Hanya laporkan, jangan ubah apa pun}';

    protected $description = 'Tambahkan kolom & pengaturan yang kurang di database (untuk server yang file migrasinya tertinggal)';

    /**
     * Kolom yang ditambahkan kembali, beserta migrasi asalnya.
     *
     * Tiap entri menjelaskan dampaknya supaya laporannya bisa dibaca tanpa
     * membuka kode — lihat DiagnoseLogin untuk pemetaan yang sama.
     *
     * @var array<int, array{table: string, column: string, migration: string, impact: string, add: callable}>
     */
    protected function columns(): array
    {
        return [
            [
                'table' => 'users',
                'column' => 'student_rules_ack_version',
                'migration' => '2026_09_13_000000',
                'impact' => 'popup peraturan muncul lagi setiap login dan tombol Setuju gagal',
                'add' => static function (Blueprint $table): void {
                    $table->unsignedInteger('student_rules_ack_version')->nullable();
                },
            ],
            [
                'table' => 'users',
                'column' => 'student_rules_acknowledged_at',
                'migration' => '2026_09_13_000000',
                'impact' => 'waktu persetujuan peraturan tidak tercatat',
                'add' => static function (Blueprint $table): void {
                    $table->timestamp('student_rules_acknowledged_at')->nullable();
                },
            ],
            [
                'table' => 'peserta_ujians',
                'column' => 'violation_flag',
                'migration' => '2026_09_18_000313',
                'impact' => 'dashboard siswa balas 500 dan pelanggaran anti-cheat tidak tersimpan',
                'add' => static function (Blueprint $table): void {
                    $table->boolean('violation_flag')->default(false);
                },
            ],
            [
                'table' => 'peserta_ujians',
                'column' => 'violation_type',
                'migration' => '2026_09_18_000313',
                'impact' => 'jenis pelanggaran anti-cheat tidak tersimpan',
                'add' => static function (Blueprint $table): void {
                    $table->string('violation_type')->nullable();
                },
            ],
            [
                'table' => 'peserta_ujians',
                'column' => 'violation_detail',
                'migration' => '2026_09_18_000313',
                'impact' => 'detail pelanggaran anti-cheat tidak tersimpan',
                'add' => static function (Blueprint $table): void {
                    $table->text('violation_detail')->nullable();
                },
            ],
            [
                'table' => 'peserta_ujians',
                'column' => 'violated_at',
                'migration' => '2026_09_18_000313',
                'impact' => 'waktu pelanggaran anti-cheat tidak tersimpan',
                'add' => static function (Blueprint $table): void {
                    $table->timestamp('violated_at')->nullable();
                },
            ],
        ];
    }

    public function handle(StudentRulesSettings $studentRules): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->line('');
        $this->info('=== Perbaikan Skema Database ===');

        if ($dryRun) {
            $this->comment('Mode --dry-run: tidak ada yang diubah.');
        }

        $this->line('');

        $added = 0;

        foreach ($this->columns() as $spec) {
            $table = $spec['table'];
            $column = $spec['column'];

            if (!Schema::hasTable($table)) {
                $this->line("  [SKIP] {$table}.{$column} — tabelnya belum ada, jalankan php artisan migrate --force");
                continue;
            }

            if (Schema::hasColumn($table, $column)) {
                $this->line("  [ OK ] {$table}.{$column} sudah ada");
                continue;
            }

            if ($dryRun) {
                $this->line("  [ADD?] {$table}.{$column} akan ditambahkan (migrasi {$spec['migration']}) — {$spec['impact']}");
                $added++;
                continue;
            }

            // Tanpa ->after(): posisi kolom tidak memengaruhi apa pun di
            // aplikasi, sedangkan kolom acuan pada migrasi aslinya belum tentu
            // ada di server yang skemanya tertinggal lebih jauh.
            Schema::table($table, static fn (Blueprint $blueprint) => $spec['add']($blueprint));

            $this->line("  [ADD ] {$table}.{$column} ditambahkan (migrasi {$spec['migration']})");
            $added++;
        }

        $this->line('');

        $createdSettings = [];

        if (!Schema::hasTable('system_settings')) {
            $this->line('  [SKIP] baris pengaturan peraturan siswa — tabel system_settings belum ada');
        } elseif ($dryRun) {
            $missing = array_values(array_filter(
                StudentRulesSettings::KEYS,
                static fn (string $key): bool => !\App\Models\SystemSetting::where('key', $key)->exists()
            ));

            $missing === []
                ? $this->line('  [ OK ] baris pengaturan peraturan siswa lengkap')
                : $this->line('  [ADD?] baris pengaturan akan dibuat: ' . implode(', ', $missing));

            $createdSettings = $missing;
        } else {
            $createdSettings = $studentRules->ensureExists();

            $createdSettings === []
                ? $this->line('  [ OK ] baris pengaturan peraturan siswa lengkap')
                : $this->line('  [ADD ] baris pengaturan dibuat: ' . implode(', ', $createdSettings));
        }

        $this->line('');

        if ($added === 0 && $createdSettings === []) {
            $this->info('Tidak ada yang perlu diperbaiki.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->comment('Jalankan ulang tanpa --dry-run untuk menerapkannya.');

            return self::SUCCESS;
        }

        $this->info("Selesai: {$added} kolom, " . count($createdSettings) . ' baris pengaturan.');
        $this->line('Verifikasi dengan: php artisan diagnose:login <NIS>');

        return self::SUCCESS;
    }
}
