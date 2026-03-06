<?php

/**
 * Script untuk troubleshooting dan fix System Settings
 * Run: php fix-settings.php
 */

echo "===========================================\n";
echo "System Settings Troubleshooting & Fix\n";
echo "===========================================\n\n";

// Check if running from command line
if (php_sapi_name() !== 'cli') {
    die("This script must be run from command line\n");
}

// Load Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

echo "Step 1: Checking database connection...\n";
try {
    DB::connection()->getPdo();
    echo "✓ Database connected\n\n";
} catch (\Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Step 2: Checking if system_settings table exists...\n";
if (Schema::hasTable('system_settings')) {
    echo "✓ Table exists\n";
    
    $count = DB::table('system_settings')->count();
    echo "  Records: $count\n\n";
    
    if ($count === 0) {
        echo "⚠ Table is empty. Running seeder...\n";
        insertDefaultSettings();
    }
} else {
    echo "✗ Table does not exist\n";
    echo "  Running migration...\n";
    
    try {
        Artisan::call('migrate', [
            '--path' => 'database/migrations/2024_01_02_000001_create_system_settings_table.php',
            '--force' => true
        ]);
        echo "✓ Migration completed\n\n";
    } catch (\Exception $e) {
        echo "✗ Migration failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

echo "Step 3: Checking helper file...\n";
if (file_exists(__DIR__.'/app/Helpers/SettingHelper.php')) {
    echo "✓ Helper file exists\n\n";
} else {
    echo "✗ Helper file not found\n";
    echo "  Creating helper file...\n";
    createHelperFile();
    echo "✓ Helper file created\n\n";
}

echo "Step 4: Updating composer autoload...\n";
exec('composer dump-autoload 2>&1', $output, $return);
if ($return === 0) {
    echo "✓ Composer autoload updated\n\n";
} else {
    echo "⚠ Composer autoload update failed\n";
    echo "  Please run manually: composer dump-autoload\n\n";
}

echo "Step 5: Clearing cache...\n";
try {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('view:clear');
    Artisan::call('route:clear');
    echo "✓ Cache cleared\n\n";
} catch (\Exception $e) {
    echo "⚠ Cache clear failed: " . $e->getMessage() . "\n\n";
}

echo "Step 6: Checking routes...\n";
try {
    $routes = Artisan::call('route:list', ['--name' => 'settings']);
    echo "✓ Routes registered\n\n";
} catch (\Exception $e) {
    echo "⚠ Route check failed: " . $e->getMessage() . "\n\n";
}

echo "Step 7: Testing settings access...\n";
try {
    $setting = DB::table('system_settings')->where('key', 'app_name')->first();
    if ($setting) {
        echo "✓ Can read settings\n";
        echo "  app_name: " . $setting->value . "\n\n";
    } else {
        echo "⚠ No settings found\n\n";
    }
} catch (\Exception $e) {
    echo "✗ Settings access failed: " . $e->getMessage() . "\n\n";
}

echo "===========================================\n";
echo "Troubleshooting Complete!\n";
echo "===========================================\n\n";

echo "Next steps:\n";
echo "1. Clear browser cache (Ctrl + Shift + R)\n";
echo "2. Try accessing: http://127.0.0.1:8000/superadmin/settings\n";
echo "3. If still error, check: storage/logs/laravel.log\n\n";

// Helper functions
function insertDefaultSettings() {
    $settings = [
        // General Settings
        [
            'key' => 'app_name',
            'value' => 'CBT SMK',
            'type' => 'text',
            'group' => 'general',
            'label' => 'Nama Aplikasi',
            'description' => 'Nama aplikasi yang ditampilkan di seluruh sistem',
            'order' => 1,
        ],
        [
            'key' => 'school_name',
            'value' => 'SMK Negeri 1',
            'type' => 'text',
            'group' => 'general',
            'label' => 'Nama Sekolah',
            'description' => 'Nama sekolah lengkap',
            'order' => 2,
        ],
        [
            'key' => 'school_address',
            'value' => 'Jl. Pendidikan No. 1',
            'type' => 'textarea',
            'group' => 'general',
            'label' => 'Alamat Sekolah',
            'description' => 'Alamat lengkap sekolah',
            'order' => 3,
        ],
        [
            'key' => 'school_phone',
            'value' => '021-12345678',
            'type' => 'text',
            'group' => 'general',
            'label' => 'Telepon Sekolah',
            'description' => 'Nomor telepon sekolah',
            'order' => 4,
        ],
        [
            'key' => 'school_email',
            'value' => 'info@smkn1.sch.id',
            'type' => 'text',
            'group' => 'general',
            'label' => 'Email Sekolah',
            'description' => 'Email resmi sekolah',
            'order' => 5,
        ],
        [
            'key' => 'school_website',
            'value' => 'https://smkn1.sch.id',
            'type' => 'text',
            'group' => 'general',
            'label' => 'Website Sekolah',
            'description' => 'URL website sekolah',
            'order' => 6,
        ],
        // Appearance Settings
        [
            'key' => 'logo',
            'value' => null,
            'type' => 'image',
            'group' => 'appearance',
            'label' => 'Logo Sekolah',
            'description' => 'Logo yang ditampilkan di header (max 2MB, format: jpg, png)',
            'order' => 1,
        ],
        [
            'key' => 'logo_small',
            'value' => null,
            'type' => 'image',
            'group' => 'appearance',
            'label' => 'Logo Kecil',
            'description' => 'Logo kecil untuk favicon dan mobile',
            'order' => 2,
        ],
        [
            'key' => 'primary_color',
            'value' => '#4f46e5',
            'type' => 'color',
            'group' => 'appearance',
            'label' => 'Warna Utama',
            'description' => 'Warna utama aplikasi',
            'order' => 3,
        ],
        [
            'key' => 'secondary_color',
            'value' => '#7c3aed',
            'type' => 'color',
            'group' => 'appearance',
            'label' => 'Warna Sekunder',
            'description' => 'Warna sekunder aplikasi',
            'order' => 4,
        ],
        [
            'key' => 'login_background',
            'value' => null,
            'type' => 'image',
            'group' => 'appearance',
            'label' => 'Background Login',
            'description' => 'Gambar background halaman login',
            'order' => 5,
        ],
        [
            'key' => 'footer_text',
            'value' => '© 2024 CBT SMK. All rights reserved.',
            'type' => 'text',
            'group' => 'appearance',
            'label' => 'Teks Footer',
            'description' => 'Teks yang ditampilkan di footer',
            'order' => 6,
        ],
        [
            'key' => 'show_powered_by',
            'value' => '1',
            'type' => 'boolean',
            'group' => 'appearance',
            'label' => 'Tampilkan "Powered by"',
            'description' => 'Tampilkan credit di footer',
            'order' => 7,
        ],
        // Exam Settings
        [
            'key' => 'default_exam_duration',
            'value' => '90',
            'type' => 'number',
            'group' => 'exam',
            'label' => 'Durasi Ujian Default (menit)',
            'description' => 'Durasi default untuk ujian baru',
            'order' => 1,
        ],
        [
            'key' => 'auto_submit_enabled',
            'value' => '1',
            'type' => 'boolean',
            'group' => 'exam',
            'label' => 'Auto Submit',
            'description' => 'Otomatis submit ujian saat waktu habis',
            'order' => 2,
        ],
        [
            'key' => 'show_result_immediately',
            'value' => '1',
            'type' => 'boolean',
            'group' => 'exam',
            'label' => 'Tampilkan Hasil Langsung',
            'description' => 'Tampilkan hasil ujian setelah submit',
            'order' => 3,
        ],
        [
            'key' => 'anti_cheat_enabled',
            'value' => '1',
            'type' => 'boolean',
            'group' => 'exam',
            'label' => 'Anti-Cheat',
            'description' => 'Aktifkan sistem anti-cheat',
            'order' => 4,
        ],
        [
            'key' => 'max_tab_switch',
            'value' => '2',
            'type' => 'number',
            'group' => 'exam',
            'label' => 'Maksimal Pindah Tab',
            'description' => 'Jumlah maksimal pindah tab sebelum auto-submit',
            'order' => 5,
        ],
        // Email Settings
        [
            'key' => 'email_from_name',
            'value' => 'CBT SMK',
            'type' => 'text',
            'group' => 'email',
            'label' => 'Nama Pengirim Email',
            'description' => 'Nama yang muncul sebagai pengirim email',
            'order' => 1,
        ],
        [
            'key' => 'email_from_address',
            'value' => 'noreply@cbt.sch.id',
            'type' => 'text',
            'group' => 'email',
            'label' => 'Email Pengirim',
            'description' => 'Alamat email pengirim',
            'order' => 2,
        ],
    ];

    foreach ($settings as $setting) {
        $setting['created_at'] = now();
        $setting['updated_at'] = now();
        DB::table('system_settings')->insert($setting);
    }

    echo "✓ Default settings inserted\n\n";
}

function createHelperFile() {
    $content = <<<'PHP'
<?php

if (!function_exists('setting')) {
    function setting($key, $default = null)
    {
        return \App\Models\SystemSetting::get($key, $default);
    }
}

if (!function_exists('app_name')) {
    function app_name()
    {
        return setting('app_name', config('app.name', 'CBT SMK'));
    }
}

if (!function_exists('school_name')) {
    function school_name()
    {
        return setting('school_name', 'SMK Negeri 1');
    }
}

if (!function_exists('school_logo')) {
    function school_logo()
    {
        $logo = setting('logo');
        return $logo ? asset('storage/' . $logo) : null;
    }
}

if (!function_exists('school_logo_small')) {
    function school_logo_small()
    {
        $logo = setting('logo_small');
        return $logo ? asset('storage/' . $logo) : null;
    }
}

if (!function_exists('primary_color')) {
    function primary_color()
    {
        return setting('primary_color', '#4f46e5');
    }
}

if (!function_exists('secondary_color')) {
    function secondary_color()
    {
        return setting('secondary_color', '#7c3aed');
    }
}
PHP;

    file_put_contents(__DIR__.'/app/Helpers/SettingHelper.php', $content);
}
