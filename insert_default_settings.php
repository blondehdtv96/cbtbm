<?php

/**
 * Script to insert default system settings
 * Run: php insert_default_settings.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

echo "=== INSERTING DEFAULT SYSTEM SETTINGS ===\n\n";

$defaultSettings = [
    // General Settings
    ['key' => 'app_name', 'value' => 'CBT SMK', 'type' => 'text', 'group' => 'general'],
    ['key' => 'app_tagline', 'value' => 'Sistem Ujian Online', 'type' => 'text', 'group' => 'general'],
    ['key' => 'app_description', 'value' => 'Sistem Ujian Online Modern untuk Sekolah Menengah Kejuruan', 'type' => 'textarea', 'group' => 'general'],
    ['key' => 'school_name', 'value' => 'SMK Negeri 1', 'type' => 'text', 'group' => 'general'],
    ['key' => 'school_address', 'value' => '', 'type' => 'textarea', 'group' => 'general'],
    ['key' => 'school_phone', 'value' => '', 'type' => 'text', 'group' => 'general'],
    ['key' => 'school_email', 'value' => '', 'type' => 'text', 'group' => 'general'],
    
    // Appearance Settings
    ['key' => 'logo', 'value' => '', 'type' => 'image', 'group' => 'appearance'],
    ['key' => 'logo_small', 'value' => '', 'type' => 'image', 'group' => 'appearance'],
    ['key' => 'primary_color', 'value' => '#4f46e5', 'type' => 'color', 'group' => 'appearance'],
    ['key' => 'secondary_color', 'value' => '#7c3aed', 'type' => 'color', 'group' => 'appearance'],
    
    // Exam Settings
    ['key' => 'default_exam_duration', 'value' => '90', 'type' => 'number', 'group' => 'exam'],
    ['key' => 'enable_anti_cheat', 'value' => '1', 'type' => 'boolean', 'group' => 'exam'],
    ['key' => 'max_tab_switch', 'value' => '3', 'type' => 'number', 'group' => 'exam'],
    ['key' => 'show_result_immediately', 'value' => '1', 'type' => 'boolean', 'group' => 'exam'],
    
    // Email Settings
    ['key' => 'smtp_host', 'value' => '', 'type' => 'text', 'group' => 'email'],
    ['key' => 'smtp_port', 'value' => '587', 'type' => 'number', 'group' => 'email'],
    ['key' => 'smtp_username', 'value' => '', 'type' => 'text', 'group' => 'email'],
    ['key' => 'smtp_password', 'value' => '', 'type' => 'text', 'group' => 'email'],
    ['key' => 'smtp_encryption', 'value' => 'tls', 'type' => 'text', 'group' => 'email'],
    ['key' => 'mail_from_address', 'value' => '', 'type' => 'text', 'group' => 'email'],
    ['key' => 'mail_from_name', 'value' => 'CBT SMK', 'type' => 'text', 'group' => 'email'],
];

$inserted = 0;
$updated = 0;
$skipped = 0;

foreach ($defaultSettings as $setting) {
    $existing = DB::table('system_settings')
        ->where('key', $setting['key'])
        ->first();
    
    if ($existing) {
        echo "⏭️  Skipped: {$setting['key']} (already exists)\n";
        $skipped++;
    } else {
        DB::table('system_settings')->insert([
            'key' => $setting['key'],
            'value' => $setting['value'],
            'type' => $setting['type'],
            'group' => $setting['group'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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
echo "Total: " . ($inserted + $skipped) . "\n";

echo "\n✅ Done! Settings are ready to use.\n";
echo "\nYou can now change the app name at:\n";
echo "http://127.0.0.1:8000/superadmin/settings\n";
