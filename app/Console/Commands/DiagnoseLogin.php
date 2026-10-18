<?php

namespace App\Console\Commands;

use App\Http\Controllers\DashboardController;
use App\Models\Siswa;
use App\Models\SystemSetting;
use App\Services\PasswordVerifier;
use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Throwable;

/**
 * Melacak penyebab "500 Server Error" pada login siswa langsung di server.
 *
 * Di produksi APP_DEBUG=false, jadi layar hanya menampilkan halaman 500 tanpa
 * pesan apa pun dan penyebab aslinya tersembunyi di storage/logs/laravel.log.
 * Perintah ini menjalankan ulang seluruh jalur login siswa — dari pencarian
 * NIS, verifikasi password, pencatatan aktivitas, sampai render dashboard yang
 * jadi tujuan redirect — lalu mencetak langkah mana yang gagal beserta kelas
 * exception, berkas, dan barisnya.
 *
 *     php artisan diagnose:login                          # cek skema + deploy
 *     php artisan diagnose:login 1234                     # + simulasi 1 akun
 *     php artisan diagnose:login 1234 --password=rahasia  # + verifikasi password
 *
 * Perintah ini hanya membaca data. Satu-satunya penulisan yang mungkin terjadi
 * adalah perbaikan hash oleh PasswordVerifier ketika --password diberikan dan
 * cocok, sama seperti saat siswa login sungguhan.
 *
 * @see \App\Services\PasswordVerifier
 * @see \App\Console\Commands\RepairPasswordHashes
 */
class DiagnoseLogin extends Command
{
    protected $signature = 'diagnose:login
                            {nis? : NIS siswa yang mau diuji jalur login-nya}
                            {--password= : Password yang dikirim siswa, untuk menguji verifikasi}';

    protected $description = 'Cari penyebab 500 pada login siswa (skema DB, migrasi tertunda, cache deploy, simulasi login)';

    /**
     * Kolom dan tabel yang disentuh jalur login siswa beserta dashboard-nya.
     *
     * Migrasi yang belum dijalankan di server adalah penyebab 500 paling sering
     * sesudah masalah hash password: kode baru menanyakan kolom yang belum ada,
     * MySQL menolak querynya, dan requestnya mati sebelum sempat membalas apa
     * pun. Daftar ini memetakan tiap kebutuhan ke migrasi asalnya supaya
     * laporannya langsung menunjuk perbaikan yang harus dijalankan.
     *
     * @var array<string, array{columns: array<int, string>, migration: string, impact: string}>
     */
    protected array $requirements = [
        'users' => [
            'columns' => [
                'password', 'plain_password', 'role', 'is_active',
                'login_attempts', 'locked_until', 'last_login', 'last_seen_at',
                'student_rules_ack_version', 'student_rules_acknowledged_at',
            ],
            'migration' => '2024_01_01_000001_update_users_table, 2026_08_12_001036_add_last_seen_at_to_users_table, 2026_09_13_000000_add_student_rules_settings_and_acknowledgement',
            'impact' => 'POST /login gagal saat menyimpan status login siswa',
        ],
        'siswas' => [
            'columns' => ['nis', 'nisn', 'nama', 'kelas_id', 'user_id'],
            'migration' => '2024_01_01_000005_create_siswas_table, 2024_01_01_000016_add_nisn_to_siswas_table',
            'impact' => 'POST /login gagal saat mencari NIS',
        ],
        'activity_logs' => [
            'columns' => ['user_id', 'action', 'module', 'description', 'ip_address', 'user_agent', 'data'],
            'migration' => '2024_01_01_000013_create_activity_logs_table',
            'impact' => 'POST /login gagal saat mencatat aktivitas login',
        ],
        'system_settings' => [
            'columns' => ['key', 'value', 'type', 'group'],
            'migration' => '2024_01_02_000001_create_system_settings_table',
            'impact' => 'POST /login gagal saat membaca pengaturan peraturan siswa',
        ],
        'peserta_ujians' => [
            'columns' => [
                'siswa_id', 'ujian_id', 'status', 'soal_order',
                'violation_flag', 'violation_type', 'violation_detail', 'violated_at',
            ],
            'migration' => '2024_01_01_000011_create_peserta_ujians_table, 2026_09_18_000313_add_violation_fields_to_peserta_ujians_table',
            'impact' => 'GET /siswa/dashboard sesudah login berhasil balas 500 (alert pelanggaran anti-cheat)',
        ],
    ];

    /** @var array<int, string> */
    protected array $problems = [];

    public function handle(PasswordVerifier $verifier): int
    {
        // 'migrator' diambil dari container lewat alias, bukan type-hint:
        // Migrator butuh MigrationRepositoryInterface yang hanya terikat di
        // service provider migrasi, jadi autowiring-nya gagal.
        $migrator = app('migrator');

        assert($migrator instanceof Migrator);

        $this->line('');
        $this->info('=== Diagnosa Login Siswa ===');
        $this->line('Environment : ' . app()->environment());
        $this->line('Debug       : ' . (config('app.debug') ? 'on' : 'off'));
        $this->line('DB          : ' . config('database.default') . ' / ' . config('database.connections.' . config('database.default') . '.database'));
        $this->line('Cache       : ' . config('cache.default'));
        $this->line('Session     : ' . config('session.driver'));
        $this->line('');

        if (!$this->checkDatabase()) {
            return $this->report();
        }

        $this->checkPendingMigrations($migrator);
        $this->checkSchema();
        $this->checkDeployCaches();
        $this->checkStores();
        $this->checkBrokenHashes($verifier);

        if ($nis = $this->argument('nis')) {
            $this->simulate((string) $nis, $verifier);
        } else {
            $this->line('');
            $this->comment('Lewati simulasi login: jalankan ulang dengan NIS, mis. php artisan diagnose:login 1234');
        }

        return $this->report();
    }

    protected function checkDatabase(): bool
    {
        return $this->step('Koneksi database', function () {
            DB::connection()->getPdo();

            return 'tersambung';
        }, 'Aplikasi tidak bisa menyentuh database — periksa DB_* di .env server');
    }

    /**
     * Migrasi yang belum jalan di server.
     *
     * Dicek lewat Migrator, bukan `migrate:status`, supaya hasilnya bisa ikut
     * dirangkum di akhir sebagai satu daftar perbaikan.
     */
    protected function checkPendingMigrations(Migrator $migrator): void
    {
        $this->step('Migrasi database', function () use ($migrator) {
            if (!$migrator->repositoryExists()) {
                $this->problems[] = 'Tabel migrations belum ada — jalankan di server: php artisan migrate --force';

                return 'repositori migrasi belum dibuat';
            }

            $ran = $migrator->getRepository()->getRan();
            $files = $migrator->getMigrationFiles($migrator->paths() ?: [database_path('migrations')]);
            $pending = array_values(array_diff(array_keys($files), $ran));

            if ($pending === []) {
                return count($ran) . ' migrasi sudah dijalankan, tidak ada yang tertunda';
            }

            foreach ($pending as $migration) {
                $this->line('    - tertunda: ' . $migration);
            }

            $this->problems[] = count($pending) . ' migrasi belum dijalankan di server — jalankan: php artisan migrate --force';

            return count($pending) . ' migrasi TERTUNDA';
        });
    }

    /**
     * Kolom yang dibutuhkan jalur login tapi belum ada di database server.
     *
     * Pemeriksaan ini terpisah dari daftar migrasi tertunda karena keduanya
     * bisa tidak sinkron: database yang pernah di-restore dari dump lama kadang
     * membawa tabel migrations yang mengaku sudah lengkap padahal kolomnya
     * belum ada.
     */
    protected function checkSchema(): void
    {
        foreach ($this->requirements as $table => $spec) {
            $this->step("Skema tabel {$table}", function () use ($table, $spec) {
                if (!Schema::hasTable($table)) {
                    $this->problems[] = "Tabel '{$table}' tidak ada → {$spec['impact']}. Migrasi: {$spec['migration']}";

                    return 'TABEL TIDAK ADA';
                }

                $missing = array_values(array_filter(
                    $spec['columns'],
                    static fn (string $column): bool => !Schema::hasColumn($table, $column)
                ));

                if ($missing === []) {
                    return 'lengkap';
                }

                $this->problems[] = "Kolom hilang di '{$table}': " . implode(', ', $missing)
                    . " → {$spec['impact']}. Migrasi: {$spec['migration']}";

                return 'KOLOM HILANG: ' . implode(', ', $missing);
            });
        }
    }

    /**
     * Cache hasil deploy yang lebih tua dari kodenya.
     *
     * `config:cache` dan `route:cache` membekukan config serta daftar route ke
     * bootstrap/cache. Kalau kode baru sudah di-pull tapi cache-nya tidak
     * dibangun ulang, server tetap menjalankan versi lama — termasuk versi
     * LoginController yang masih bisa 500 — sehingga perbaikan terlihat "tidak
     * berpengaruh" sama sekali.
     */
    protected function checkDeployCaches(): void
    {
        $this->step('Cache config & route', function () {
            $newestCode = max(array_map(
                static fn (string $path): int => is_file($path) ? (int) filemtime($path) : 0,
                [
                    base_path('routes/web.php'),
                    app_path('Http/Controllers/Auth/LoginController.php'),
                    app_path('Services/PasswordVerifier.php'),
                    app_path('Http/Controllers/DashboardController.php'),
                ]
            ));

            $notes = [];
            $stale = false;

            foreach (['config' => 'config:cache', 'routes-v7' => 'route:cache'] as $file => $command) {
                $path = base_path("bootstrap/cache/{$file}.php");

                if (!is_file($path)) {
                    $notes[] = "{$file}: tidak di-cache (ok)";
                    continue;
                }

                if ((int) filemtime($path) < $newestCode) {
                    $stale = true;
                    $notes[] = "{$file}: KEDALUWARSA (lebih tua dari kode)";
                    $this->problems[] = "bootstrap/cache/{$file}.php lebih tua dari kode terbaru — jalankan: php artisan optimize:clear lalu {$command}";
                    continue;
                }

                $notes[] = "{$file}: ter-cache dan lebih baru dari kode";
            }

            if (!$stale && function_exists('opcache_get_status')) {
                $notes[] = 'opcache aktif — restart php-fpm sesudah deploy (systemctl reload php8.2-fpm)';
            }

            return implode('; ', $notes);
        });
    }

    /**
     * Driver cache dan session benar-benar bisa dipakai.
     *
     * Produksi memakai redis untuk keduanya (lihat .env.production). Redis yang
     * mati atau ekstensi phpredis yang belum terpasang membuat login balas 500
     * di langkah session()->regenerate(), bukan di verifikasi password.
     */
    protected function checkStores(): void
    {
        $this->step('Driver cache (' . config('cache.default') . ')', function () {
            $key = 'diagnose:login:' . uniqid('', true);
            Cache::put($key, 'ok', 10);
            $value = Cache::get($key);
            Cache::forget($key);

            if ($value !== 'ok') {
                $this->problems[] = 'Cache menulis tanpa error tapi nilainya tidak terbaca kembali — periksa driver cache di server';

                return 'TULIS/BACA TIDAK KONSISTEN';
            }

            return 'tulis dan baca berhasil';
        }, 'Driver cache tidak bisa dipakai — throttle login dan pembacaan pengaturan akan balas 500');

        $this->step('Driver session (' . config('session.driver') . ')', function () {
            $handler = app('session')->driver()->getHandler();
            $id = 'diagnose' . bin2hex(random_bytes(16));
            $handler->write($id, 'ok');
            $read = (string) $handler->read($id);
            $handler->destroy($id);

            if ($read === '') {
                $this->problems[] = 'Session tidak bisa ditulis — login akan gagal di session()->regenerate(). '
                    . (config('session.driver') === 'file'
                        ? 'Periksa izin tulis storage/framework/sessions (chown -R www-data:www-data storage).'
                        : 'Periksa layanan ' . config('session.driver') . ' di server.');

                return 'TIDAK BISA DITULIS';
            }

            return 'tulis dan baca berhasil';
        }, 'Driver session tidak bisa dipakai — login akan balas 500 saat membuat sesi baru');
    }

    /**
     * Baris users yang passwordnya bukan hash bcrypt.
     *
     * Sejak PasswordVerifier dipakai, baris seperti ini tidak lagi melempar
     * exception, tapi akunnya tetap tidak bisa login kalau plain_password-nya
     * juga sudah hilang. Dihitung di sini supaya terlihat sebelum siswa
     * melaporkannya satu per satu.
     */
    protected function checkBrokenHashes(PasswordVerifier $verifier): void
    {
        $this->step('Hash password akun siswa', function () use ($verifier) {
            $broken = 0;
            $recoverable = 0;

            DB::table('users')
                ->select(['id', 'password', 'plain_password'])
                ->where('role', 'siswa')
                ->orderBy('id')
                ->chunk(1000, function ($users) use ($verifier, &$broken, &$recoverable) {
                    foreach ($users as $user) {
                        if ($verifier->isUsable($user->password)) {
                            continue;
                        }

                        $broken++;

                        if ((string) ($user->plain_password ?? '') !== '') {
                            $recoverable++;
                        }
                    }
                });

            if ($broken === 0) {
                return 'semua hash bcrypt (wajar)';
            }

            $this->problems[] = "{$broken} akun siswa punya hash password tidak sah ({$recoverable} masih bisa dipulihkan dari plain_password) — jalankan: php artisan users:repair-password-hashes";

            return "{$broken} akun bermasalah, {$recoverable} bisa dipulihkan";
        });
    }

    /**
     * Jalankan ulang jalur login untuk satu NIS, langkah demi langkah.
     *
     * Dipecah per langkah supaya exception yang muncul bisa langsung dikaitkan
     * ke bagian kodenya — pencarian NIS, relasi user, verifikasi password,
     * pencatatan aktivitas, atau render dashboard tujuan redirect.
     */
    protected function simulate(string $nis, PasswordVerifier $verifier): void
    {
        $this->line('');
        $this->info("=== Simulasi login NIS {$nis} ===");

        $siswa = null;
        $user = null;

        $this->step('Cari siswa berdasarkan NIS', function () use ($nis, &$siswa) {
            $siswa = Siswa::where('nis', $nis)->first();

            if (!$siswa) {
                $this->problems[] = "NIS '{$nis}' tidak ada di tabel siswas — login akan membalas 'NIS tidak ditemukan' (bukan 500)";

                return 'TIDAK DITEMUKAN';
            }

            return "ditemukan: {$siswa->nama} (siswa_id {$siswa->id})";
        });

        if (!$siswa) {
            return;
        }

        $this->step('Relasi siswa → user', function () use ($siswa, &$user) {
            $user = $siswa->user;

            if (!$user) {
                $this->problems[] = "Siswa '{$siswa->nis}' tidak punya baris users (user_id {$siswa->user_id}) — login akan membalas 'Akun tidak ditemukan'";

                return 'USER TIDAK ADA';
            }

            return "user_id {$user->id}, role {$user->role}, aktif: " . ($user->is_active ? 'ya' : 'TIDAK');
        });

        if (!$user) {
            return;
        }

        $this->step('Status hash password', function () use ($user, $verifier) {
            if ($verifier->isUsable($user->getAuthPassword())) {
                return 'bcrypt (wajar)';
            }

            $this->problems[] = "Hash password user_id {$user->id} tidak sah (" . $verifier->describe($user->getAuthPassword())
                . ') — jalankan: php artisan users:repair-password-hashes';

            return 'TIDAK SAH: ' . $verifier->describe($user->getAuthPassword());
        });

        if (($password = $this->option('password')) !== null) {
            $this->step('Verifikasi password', function () use ($user, $verifier, $password) {
                return match ($verifier->check($user, (string) $password)) {
                    PasswordVerifier::VALID => 'COCOK — password ini bisa dipakai login',
                    PasswordVerifier::INVALID => 'tidak cocok (login balas "NIS atau password salah", bukan 500)',
                    default => 'hash tidak bisa diverifikasi (login balas pesan "hubungi administrator", bukan 500)',
                };
            });
        }

        $this->step('Baca pengaturan peraturan siswa', function () {
            $enabled = SystemSetting::get('student_rules_enabled', '1');
            $version = SystemSetting::get('student_rules_version', 1);

            return "enabled={$enabled}, version={$version}";
        }, 'Pembacaan system_settings gagal — POST /login akan balas 500 sesudah password terverifikasi');

        $this->step('Simpan status login (login_attempts, last_login)', function () use ($user) {
            // Tidak benar-benar disimpan: query ini hanya memastikan ketiga
            // kolomnya ada dan tipenya diterima MySQL, sama seperti update
            // yang dijalankan controller sesudah login berhasil.
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'login_attempts' => $user->login_attempts ?? 0,
                    'locked_until' => $user->locked_until,
                    'last_login' => $user->last_login,
                ]);

            return 'kolom status login bisa ditulis';
        }, 'Update status login gagal — POST /login akan balas 500 sesudah password terverifikasi');

        $this->step('Catat ke activity_logs', function () use ($user) {
            $id = DB::table('activity_logs')->insertGetId([
                'user_id' => $user->id,
                'action' => 'diagnose',
                'module' => 'auth',
                'description' => 'Uji jalur login lewat diagnose:login',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'artisan diagnose:login',
                'data' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Barisnya langsung dihapus: ini uji tulis, bukan aktivitas nyata.
            DB::table('activity_logs')->where('id', $id)->delete();

            return 'bisa ditulis';
        }, 'Penulisan activity_logs gagal — POST /login akan balas 500 sesudah password terverifikasi');

        $this->step('Render dashboard siswa (tujuan redirect)', function () use ($user) {
            $previous = Auth::user();
            Auth::setUser($user);

            // $errors biasanya dibagikan ke view oleh middleware
            // ShareErrorsFromSession, yang tidak ikut jalan di konsol. Tanpa
            // ini, layout selalu gagal pada "Undefined variable $errors" dan
            // menutupi error dashboard yang sebenarnya dicari.
            View::share('errors', new ViewErrorBag());

            try {
                $html = app(DashboardController::class)->siswa()->render();

                return 'berhasil, ' . number_format(strlen($html)) . ' byte HTML';
            } finally {
                $previous ? Auth::setUser($previous) : Auth::logout();
            }
        }, 'Dashboard siswa gagal dirender — login sendiri berhasil, lalu GET /siswa/dashboard balas 500');
    }

    /**
     * Jalankan satu langkah dan tangkap exception-nya.
     *
     * Exception sengaja tidak dibiarkan menggelembung: tujuan perintah ini
     * justru mencetak lokasinya, dan langkah sesudahnya masih berguna meski
     * satu langkah gagal.
     */
    protected function step(string $label, callable $callback, ?string $hint = null): bool
    {
        $before = count($this->problems);

        try {
            $result = $callback();

            // Langkah bisa selesai tanpa exception tapi tetap menemukan
            // masalah (mis. kolom hilang terdeteksi lewat Schema::hasColumn),
            // jadi penandanya mengikuti isi laporan, bukan sekadar tidak error.
            $marker = count($this->problems) > $before ? '[WARN]' : '[ OK ]';
            $this->line('  ' . $marker . ' ' . $this->pad($label) . ' ' . (string) $result);

            return true;
        } catch (Throwable $e) {
            $this->line('  [FAIL] ' . $this->pad($label) . ' ' . get_class($e));
            $this->line('         pesan : ' . $e->getMessage());
            $this->line('         lokasi: ' . $this->relative($e->getFile()) . ':' . $e->getLine());

            $problem = "{$label} gagal: " . get_class($e) . ' — ' . $e->getMessage();

            if ($hint !== null) {
                $this->line('         dampak: ' . $hint);
                $problem .= " | {$hint}";
            }

            $this->problems[] = $problem;

            return false;
        }
    }

    /**
     * Ratakan label ke lebar tetap berdasarkan jumlah karakter.
     *
     * sprintf('%-42s') menghitung byte, jadi label yang memuat karakter
     * multibyte (mis. tanda panah pada "siswa → user") membuat kolom hasilnya
     * tidak sejajar dan laporannya susah dibaca cepat.
     */
    protected function pad(string $label, int $width = 42): string
    {
        $length = mb_strlen($label);

        return $length >= $width ? $label : $label . str_repeat(' ', $width - $length);
    }

    protected function relative(string $path): string
    {
        $base = base_path() . DIRECTORY_SEPARATOR;
        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        return str_starts_with($normalized, $base)
            ? str_replace(DIRECTORY_SEPARATOR, '/', substr($normalized, strlen($base)))
            : $path;
    }

    protected function report(): int
    {
        $this->line('');

        if ($this->problems === []) {
            $this->info('=== Tidak ada masalah terdeteksi di jalur login siswa ===');
            $this->line('Kalau siswa masih dapat 500, ambil pesan aslinya dari log server:');
            $this->line('  tail -n 120 storage/logs/laravel.log');

            return self::SUCCESS;
        }

        $this->error('=== ' . count($this->problems) . ' masalah ditemukan ===');

        foreach ($this->problems as $i => $problem) {
            $this->line('  ' . ($i + 1) . '. ' . $problem);
        }

        return self::FAILURE;
    }
}
