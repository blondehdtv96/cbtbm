<?php
/**
 * Test route URL generation
 * Jalankan: php test_route_url.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TEST ROUTE URL ===\n\n";

// Test route generation
$ujianId = 13;

try {
    $url = route('exam.save-jawaban', $ujianId);
    echo "Route URL: {$url}\n\n";
    
    // Parse URL
    $parsed = parse_url($url);
    echo "Scheme: " . ($parsed['scheme'] ?? 'N/A') . "\n";
    echo "Host: " . ($parsed['host'] ?? 'N/A') . "\n";
    echo "Path: " . ($parsed['path'] ?? 'N/A') . "\n\n";
    
    // Expected path
    $expected = "/exam/{$ujianId}/save-jawaban";
    echo "Expected path: {$expected}\n";
    echo "Match: " . ($parsed['path'] === $expected ? 'YES' : 'NO') . "\n\n";
    
    // Check APP_URL
    echo "APP_URL: " . config('app.url') . "\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
