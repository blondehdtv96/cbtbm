<?php
/**
 * Script untuk memperbaiki jawaban yang tersimpan sebagai string kosong
 * Jalankan: php fix_empty_answers.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\JawabanSiswa;
use Illuminate\Support\Facades\DB;

echo "=== FIX EMPTY ANSWERS ===\n\n";

// Cari jawaban yang tersimpan sebagai string kosong tapi seharusnya NULL
$emptyAnswers = JawabanSiswa::where(function($query) {
    $query->where('jawaban_dipilih', '')
          ->orWhere('jawaban_dipilih', ' ')
          ->orWhereRaw('TRIM(jawaban_dipilih) = ""');
})->get();

echo "Found {$emptyAnswers->count()} answers with empty strings\n\n";

if ($emptyAnswers->count() > 0) {
    echo "Do you want to set these to NULL? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    
    if (trim($line) === 'y' || trim($line) === 'Y') {
        foreach ($emptyAnswers as $jawaban) {
            $jawaban->update(['jawaban_dipilih' => null]);
            echo "Updated jawaban_id: {$jawaban->id}\n";
        }
        echo "\n✓ All empty answers set to NULL\n";
    } else {
        echo "\nCancelled.\n";
    }
    
    fclose($handle);
}

echo "\n=== CHECKING SPECIFIC PESERTA ===\n";
echo "Enter peserta_ujian_id (or press Enter to skip): ";
$handle = fopen("php://stdin", "r");
$pesertaId = trim(fgets($handle));
fclose($handle);

if ($pesertaId) {
    $jawabans = JawabanSiswa::with('bankSoal')
        ->where('peserta_ujian_id', $pesertaId)
        ->get();
    
    echo "\nPeserta ID: {$pesertaId}\n";
    echo "Total Soal: {$jawabans->count()}\n";
    
    $terisi = $jawabans->filter(function($j) {
        return $j->jawaban_dipilih !== null && trim($j->jawaban_dipilih) !== '';
    })->count();
    
    $kosong = $jawabans->filter(function($j) {
        return $j->jawaban_dipilih === null || trim($j->jawaban_dipilih) === '';
    })->count();
    
    echo "Terisi: {$terisi}\n";
    echo "Kosong: {$kosong}\n\n";
    
    echo "Detail:\n";
    foreach ($jawabans as $index => $jawaban) {
        $no = $index + 1;
        $status = 'KOSONG';
        
        if ($jawaban->jawaban_dipilih === null) {
            $status = 'NULL';
        } elseif ($jawaban->jawaban_dipilih === '') {
            $status = 'EMPTY STRING';
        } elseif (trim($jawaban->jawaban_dipilih) === '') {
            $status = 'WHITESPACE';
        } else {
            $status = 'TERISI: ' . $jawaban->jawaban_dipilih;
        }
        
        echo "  #{$no} Soal ID {$jawaban->bank_soal_id}: {$status}\n";
    }
}

echo "\n=== DONE ===\n";
