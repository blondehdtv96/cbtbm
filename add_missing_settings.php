<?php

/**
 * Script to add missing system settings
 * Run: php add_missing_settings.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

echo "=== ADDING MISSING SYSTEM SETTINGS ===\n\n";

$missingSettings = [
    [
        'key' => 'app_tagline',
        'value' => 'Sistem Ujian Online',
        'type' => 'text',
        'group' => 'general',
        'label' => 'Tagline Aplikasi',
        'description' => 'Tagline atau slogan aplikasi',
        'order' => 2,
    ],
    [
        'key' => 'app_description',
        'value' => 'Sistem Ujian Online Modern untuk Sekolah Menengah Kejuruan',
        'type' => 'textarea',
        'group' => 'general',
        'label' => 'Deskripsi Aplikasi',
        'description' => 'Deskripsi lengkap aplikasi',
        'order' => 3,
    ],
];

$inserted = 0;
$skipped = 0;

foreach ($missingSettings as $setting) {
    $existing = DB::table('system_settings')
        ->where('key', $setting['key'])
        ->first();
    
    if ($existing) {
        echo "⏭️  Skipped: {$setting['key']} (already exists)\n";
        $skipped++;
    } else {
        DB::table('system_settings')->insert(array_merge($setting, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));
        echo "✅ Inserted: {$setting['key']}\n";
        $inserted++;
    }
}

echo "\n=== CLEARING CACHE ===\n";
Cache::flush();
echo "✅ Cache cleared\n";

echo "\n=== SUMMARY ===\n";
echo "Inserted: {$inserted}\n";
echo "Skipped: {$skipped}\n";

echo "\n✅ Done!\n";
