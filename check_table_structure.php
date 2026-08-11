<?php

/**
 * Script to check actual database table structure
 * Run: php check_table_structure.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== CHECKING DATABASE TABLE STRUCTURES ===\n\n";

$tables = [
    'jawaban_siswas',
    'peserta_ujians',
    'bank_soals',
    'opsi_jawabans',
    'ujians',
    'users',
    'siswa',
    'activity_logs'
];

foreach ($tables as $table) {
    echo "TABLE: {$table}\n";
    echo str_repeat('-', 50) . "\n";
    
    if (!Schema::hasTable($table)) {
        echo "❌ Table does not exist!\n\n";
        continue;
    }
    
    try {
        $columns = DB::select("DESCRIBE {$table}");
        
        echo "Columns:\n";
        foreach ($columns as $column) {
            echo "  ✓ {$column->Field} ({$column->Type})\n";
        }
        
        // Check indexes
        $indexes = DB::select("SHOW INDEX FROM {$table}");
        if (!empty($indexes)) {
            echo "\nExisting Indexes:\n";
            $indexNames = [];
            foreach ($indexes as $index) {
                if (!in_array($index->Key_name, $indexNames)) {
                    echo "  • {$index->Key_name} on {$index->Column_name}\n";
                    $indexNames[] = $index->Key_name;
                }
            }
        }
        
    } catch (\Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "\n=== MIGRATION COMPATIBILITY CHECK ===\n\n";

// Check specific columns that migration tries to index
$checks = [
    'jawaban_siswas' => ['peserta_ujian_id', 'bank_soal_id', 'is_correct', 'created_at'],
    'peserta_ujians' => ['ujian_id', 'siswa_id', 'status', 'waktu_mulai'],
    'bank_soals' => ['mapel_id', 'tipe_soal', 'digunakan_count'],
    'opsi_jawabans' => ['bank_soal_id', 'is_correct'],
    'ujians' => ['mapel_id', 'is_published', 'tanggal_ujian'],
    'users' => ['role', 'is_active'],
    'siswa' => ['user_id', 'kelas_id', 'nisn', 'nis'],
    'activity_logs' => ['user_id', 'action', 'created_at']
];

foreach ($checks as $table => $columns) {
    if (!Schema::hasTable($table)) {
        continue;
    }
    
    echo "Checking {$table}:\n";
    foreach ($columns as $column) {
        $exists = Schema::hasColumn($table, $column);
        $status = $exists ? '✓' : '❌';
        echo "  {$status} {$column}\n";
    }
    echo "\n";
}

echo "Done!\n";
