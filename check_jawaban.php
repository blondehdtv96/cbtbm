<?php
/**
 * Script untuk mengecek jawaban siswa di database
 * Jalankan: php check_jawaban.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PesertaUjian;
use App\Models\JawabanSiswa;
use App\Models\Ujian;

echo "=== CEK JAWABAN SISWA ===\n\n";

// Ambil peserta ujian terakhir
$peserta = PesertaUjian::with(['siswa', 'ujian'])->latest()->first();

if (!$peserta) {
    echo "Tidak ada data peserta ujian.\n";
    exit;
}

echo "Peserta: {$peserta->siswa->nama_siswa}\n";
echo "NIS: {$peserta->siswa->nis}\n";
echo "Ujian: {$peserta->ujian->nama_ujian}\n";
echo "Status: {$peserta->status}\n";
echo "Nilai: {$peserta->nilai}\n";
echo "Waktu Mulai: {$peserta->waktu_mulai}\n";
echo "Waktu Selesai: " . ($peserta->waktu_selesai ?? 'Belum selesai') . "\n\n";

// Ambil semua jawaban
$jawabans = JawabanSiswa::with('bankSoal')
    ->where('peserta_ujian_id', $peserta->id)
    ->get();

echo "Total Soal: {$jawabans->count()}\n";
echo "Jawaban Terisi: " . $jawabans->whereNotNull('jawaban_dipilih')->where('jawaban_dipilih', '!=', '')->count() . "\n";
echo "Jawaban Kosong: " . $jawabans->where(function($j) { 
    return empty($j->jawaban_dipilih); 
})->count() . "\n\n";

echo "=== DETAIL JAWABAN ===\n\n";

foreach ($jawabans as $index => $jawaban) {
    $no = $index + 1;
    $soal = $jawaban->bankSoal;
    
    echo "Soal #{$no} (ID: {$jawaban->bank_soal_id})\n";
    echo "  Pertanyaan: " . substr($soal->pertanyaan ?? 'N/A', 0, 50) . "...\n";
    echo "  Tipe: " . ($soal->tipe_soal ?? 'N/A') . "\n";
    echo "  Jawaban Siswa: " . ($jawaban->jawaban_dipilih ?: '[KOSONG]') . "\n";
    echo "  Is Correct: " . ($jawaban->is_correct === null ? 'NULL' : ($jawaban->is_correct ? 'BENAR' : 'SALAH')) . "\n";
    echo "  Nilai: " . ($jawaban->nilai ?? 0) . "\n";
    echo "  Is Ragu: " . ($jawaban->is_ragu ? 'Ya' : 'Tidak') . "\n";
    
    if ($soal && $soal->tipe_soal === 'pg') {
        $correctOption = $soal->opsiJawabans()->where('is_correct', true)->first();
        echo "  Jawaban Benar: " . ($correctOption->opsi_label ?? 'N/A') . "\n";
    }
    
    echo "\n";
}

echo "=== SUMMARY ===\n";
$benar = $jawabans->where('is_correct', true)->count();
$salah = $jawabans->where('is_correct', false)->count();
$belumDinilai = $jawabans->where('is_correct', null)->count();

echo "Benar: {$benar}\n";
echo "Salah: {$salah}\n";
echo "Belum Dinilai: {$belumDinilai}\n";
echo "Nilai Akhir: {$peserta->nilai}\n";
