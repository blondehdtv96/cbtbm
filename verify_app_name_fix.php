<?php

/**
 * Script to verify app name fix is working
 * Run: php verify_app_name_fix.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║     VERIFIKASI PERBAIKAN NAMA APLIKASI                    ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$allPassed = true;

// Test 1: Check if settings exist in database
echo "1️⃣  Checking Database Settings...\n";
$requiredSettings = ['app_name', 'app_tagline', 'app_description', 'school_name'];
$missingSettings = [];

foreach ($requiredSettings as $key) {
    $exists = DB::table('system_settings')->where('key', $key)->exists();
    if ($exists) {
        $value = DB::table('system_settings')->where('key', $key)->value('value');
        echo "   ✅ {$key} = {$value}\n";
    } else {
        echo "   ❌ {$key} = MISSING\n";
        $missingSettings[] = $key;
        $allPassed = false;
    }
}

if (empty($missingSettings)) {
    echo "   ✅ All required settings exist\n";
} else {
    echo "   ❌ Missing settings: " . implode(', ', $missingSettings) . "\n";
}

// Test 2: Check if helper functions work
echo "\n2️⃣  Testing Helper Functions...\n";
try {
    $appName = app_name();
    $schoolName = school_name();
    echo "   ✅ app_name() = {$appName}\n";
    echo "   ✅ school_name() = {$schoolName}\n";
    echo "   ✅ Helper functions are working\n";
} catch (\Exception $e) {
    echo "   ❌ Helper functions failed: " . $e->getMessage() . "\n";
    $allPassed = false;
}

// Test 3: Check if views are updated
echo "\n3️⃣  Checking View Files...\n";
$viewsToCheck = [
    'resources/views/layouts/app.blade.php' => ['{{ app_name()', 'school_logo()'],
    'resources/views/auth/login.blade.php' => ['{{ app_name()', 'school_logo()'],
    'resources/views/exam/mengerjakan.blade.php' => ['{{ app_name()'],
    'resources/views/exam/result.blade.php' => ['{{ app_name()'],
    'resources/views/exam/anti-cheat-violation.blade.php' => ['{{ app_name()'],
];

$viewsUpdated = 0;
$viewsNotUpdated = 0;

foreach ($viewsToCheck as $file => $patterns) {
    if (!File::exists($file)) {
        echo "   ⚠️  {$file} - FILE NOT FOUND\n";
        continue;
    }
    
    $content = File::get($file);
    $allPatternsFound = true;
    
    foreach ($patterns as $pattern) {
        if (strpos($content, $pattern) === false) {
            $allPatternsFound = false;
            break;
        }
    }
    
    if ($allPatternsFound) {
        echo "   ✅ " . basename($file) . " - Updated\n";
        $viewsUpdated++;
    } else {
        echo "   ❌ " . basename($file) . " - Not updated\n";
        $viewsNotUpdated++;
        $allPassed = false;
    }
}

echo "   📊 Updated: {$viewsUpdated}, Not Updated: {$viewsNotUpdated}\n";

// Test 4: Check autoload
echo "\n4️⃣  Checking Autoload...\n";
if (function_exists('app_name')) {
    echo "   ✅ Helper functions are autoloaded\n";
} else {
    echo "   ❌ Helper functions not autoloaded\n";
    echo "   💡 Run: composer dump-autoload\n";
    $allPassed = false;
}

// Test 5: Check cache
echo "\n5️⃣  Checking Cache Status...\n";
try {
    $cachedConfig = config('app.name');
    echo "   ℹ️  Cached config app.name = {$cachedConfig}\n";
    echo "   ℹ️  Database app_name = " . app_name() . "\n";
    
    if (app_name() !== 'CBT SMK') {
        echo "   ✅ Using database settings (not default)\n";
    } else {
        echo "   ⚠️  Using default value (might need cache clear)\n";
    }
} catch (\Exception $e) {
    echo "   ⚠️  Could not check cache: " . $e->getMessage() . "\n";
}

// Final Summary
echo "\n╔════════════════════════════════════════════════════════════╗\n";
if ($allPassed) {
    echo "║  ✅ SEMUA TEST PASSED - PERBAIKAN BERHASIL!               ║\n";
} else {
    echo "║  ❌ ADA TEST YANG GAGAL - PERLU PERBAIKAN                 ║\n";
}
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Instructions
echo "📋 CARA MENGUBAH NAMA APLIKASI:\n";
echo "   1. Buka: http://127.0.0.1:8000/superadmin/settings\n";
echo "   2. Tab 'Pengaturan Umum'\n";
echo "   3. Ubah 'Nama Aplikasi'\n";
echo "   4. Klik 'Simpan Pengaturan'\n";
echo "   5. Refresh browser (F5)\n\n";

echo "🔧 JIKA PERUBAHAN TIDAK MUNCUL:\n";
echo "   php artisan optimize:clear\n";
echo "   Lalu refresh browser dengan Ctrl+Shift+R\n\n";

echo "📖 DOKUMENTASI LENGKAP:\n";
echo "   Baca: CARA_UBAH_NAMA_APLIKASI.md\n\n";

if ($allPassed) {
    exit(0);
} else {
    exit(1);
}
