<?php
/**
 * Script untuk test save jawaban secara manual
 * Jalankan: php test_save_jawaban.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PesertaUjian;
use App\Models\JawabanSiswa;
use App\Models\BankSoal;

echo "=== TEST SAVE JAWABAN ===\n\n";

// Input peserta_ujian_id
echo "Enter peserta_ujian_id: ";
$handle = fopen("php://stdin", "r");
$pesertaId = trim(fgets($handle));
fclose($handle);

if (!$pesertaId) {
    echo "Error: peserta_ujian_id required\n";
    exit(1);
}

$peserta = PesertaUjian::with(['ujian', 'siswa'])->find($pesertaId);

if (!$peserta) {
    echo "Error: Peserta not found\n";
    exit(1);
}

echo "\nPeserta: {$peserta->siswa->nama}\n";
echo "Ujian: {$peserta->ujian->nama_ujian}\n";
echo "Status: {$peserta->status}\n\n";

// Get soal order
$soalOrder = $peserta->getSoalOrderArray();
if (empty($soalOrder)) {
    echo "Error: No soal_order found\n";
    exit(1);
}

echo "Total Soal: " . count($soalOrder) . "\n\n";

// Test save untuk soal pertama
$soalId = $soalOrder[0];
$soal = BankSoal::with('opsiJawabans')->find($soalId);

if (!$soal) {
    echo "Error: Soal not found\n";
    exit(1);
}

echo "Testing with Soal ID: {$soalId}\n";
echo "Pertanyaan: " . substr($soal->pertanyaan, 0, 50) . "...\n";
echo "Tipe: {$soal->tipe_soal}\n\n";

if ($soal->tipe_soal === 'pg') {
    echo "Opsi Jawaban:\n";
    foreach ($soal->opsiJawabans as $opsi) {
        $correct = $opsi->is_correct ? ' [BENAR]' : '';
        echo "  {$opsi->opsi_label}. {$opsi->isi_opsi}{$correct}\n";
    }
    
    $correctOption = $soal->opsiJawabans->where('is_correct', true)->first();
    $testJawaban = $correctOption ? $correctOption->opsi_label : 'A';
    
    echo "\nTest: Saving jawaban '{$testJawaban}'...\n";
    
    try {
        $jawaban = JawabanSiswa::updateOrCreate(
            [
                'peserta_ujian_id' => $peserta->id,
                'bank_soal_id' => $soalId,
            ],
            [
                'jawaban_dipilih' => $testJawaban,
                'is_ragu' => false,
            ]
        );
        
        echo "✓ Success! Jawaban ID: {$jawaban->id}\n";
        echo "  jawaban_dipilih: '{$jawaban->jawaban_dipilih}'\n";
        echo "  is_null: " . ($jawaban->jawaban_dipilih === null ? 'true' : 'false') . "\n";
        echo "  is_empty: " . ($jawaban->jawaban_dipilih === '' ? 'true' : 'false') . "\n";
        echo "  length: " . strlen($jawaban->jawaban_dipilih ?? '') . "\n";
        
        // Verify by reading back
        $verify = JawabanSiswa::find($jawaban->id);
        echo "\nVerification (read from DB):\n";
        echo "  jawaban_dipilih: '{$verify->jawaban_dipilih}'\n";
        echo "  is_null: " . ($verify->jawaban_dipilih === null ? 'true' : 'false') . "\n";
        echo "  is_empty: " . ($verify->jawaban_dipilih === '' ? 'true' : 'false') . "\n";
        
        if ($verify->jawaban_dipilih === $testJawaban) {
            echo "\n✓✓✓ VERIFICATION PASSED ✓✓✓\n";
        } else {
            echo "\n✗✗✗ VERIFICATION FAILED ✗✗✗\n";
            echo "Expected: '{$testJawaban}'\n";
            echo "Got: '{$verify->jawaban_dipilih}'\n";
        }
        
    } catch (\Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
        echo "Trace: " . $e->getTraceAsString() . "\n";
    }
}

echo "\n=== CHECKING ALL JAWABAN ===\n\n";

$allJawaban = JawabanSiswa::where('peserta_ujian_id', $peserta->id)->get();

echo "Total: {$allJawaban->count()}\n";

$terisi = $allJawaban->filter(function($j) {
    return $j->jawaban_dipilih !== null && trim($j->jawaban_dipilih) !== '';
})->count();

$kosong = $allJawaban->filter(function($j) {
    return $j->jawaban_dipilih === null || trim($j->jawaban_dipilih) === '';
})->count();

echo "Terisi: {$terisi}\n";
echo "Kosong: {$kosong}\n\n";

foreach ($allJawaban->take(5) as $j) {
    $status = 'KOSONG';
    if ($j->jawaban_dipilih === null) {
        $status = 'NULL';
    } elseif ($j->jawaban_dipilih === '') {
        $status = 'EMPTY';
    } elseif (trim($j->jawaban_dipilih) === '') {
        $status = 'WHITESPACE';
    } else {
        $status = "VALUE: {$j->jawaban_dipilih}";
    }
    echo "  Soal {$j->bank_soal_id}: {$status}\n";
}

echo "\n=== DONE ===\n";
