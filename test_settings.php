<?php

/**
 * Script to test system settings
 * Run: php test_settings.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== TESTING SYSTEM SETTINGS ===\n\n";

// Test 1: Check if settings exist in database
echo "1. Checking database settings:\n";
$settings = DB::table('system_settings')->get();
echo "   Total settings: " . $settings->count() . "\n";

foreach ($settings as $setting) {
    echo "   - {$setting->key} = {$setting->value}\n";
}

echo "\n2. Testing helper functions:\n";

// Test helper functions
echo "   app_name() = " . app_name() . "\n";
echo "   school_name() = " . school_name() . "\n";
echo "   primary_color() = " . primary_color() . "\n";
echo "   secondary_color() = " . secondary_color() . "\n";

echo "\n3. Testing setting() function:\n";
echo "   setting('app_name') = " . setting('app_name') . "\n";
echo "   setting('school_name') = " . setting('school_name') . "\n";
echo "   setting('app_tagline', 'Default Tagline') = " . setting('app_tagline', 'Default Tagline') . "\n";

echo "\n✅ All tests completed!\n";
echo "\nTo change settings, visit:\n";
echo "http://127.0.0.1:8000/superadmin/settings\n";
