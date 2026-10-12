<?php

namespace Tests\Concerns;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Import siswa dikerjakan bertahap (beberapa request kecil) supaya file berisi
 * ribuan siswa tidak kena 504 Gateway Time-out. Helper ini menjalankan alurnya
 * dari preview sampai potongan terakhir.
 */
trait RunsSiswaImport
{
    /**
     * Buat file Excel import dengan header template resmi.
     * Setiap baris: [no, nisn, nis, nama, kelas, password].
     */
    protected function makeImportXlsx(array $rows, string $fileName = 'test_import_siswa.xlsx'): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Import Siswa');
        $sheet->fromArray(
            ['No', 'NISN', 'NIS', 'Nama Lengkap', 'Kelas (ID atau Nama)', 'Password (Opsional)'],
            null,
            'A1'
        );
        $sheet->fromArray($rows, null, 'A2');

        $path = storage_path('app/' . $fileName);
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    /**
     * Upload file dan kembalikan baris hasil validasi (data view preview).
     */
    protected function previewImport(User $admin, string $path): array
    {
        $response = $this->actingAs($admin)->post(route('admin.import-siswa.preview'), [
            'file' => new UploadedFile($path, 'import.xlsx', null, null, true),
        ]);

        $response->assertOk();

        return $response->viewData('previewData');
    }

    /**
     * Jalankan import sampai semua potongan selesai, kembalikan ringkasan terakhir.
     */
    protected function runImportToCompletion(User $admin, array $payload = []): array
    {
        $this->actingAs($admin)
            ->post(route('admin.import-siswa.import'), $payload)
            ->assertRedirect(route('admin.import-siswa.progress'));

        $chunks = 0;
        do {
            $response = $this->actingAs($admin)->postJson(route('admin.import-siswa.process-chunk'));
            $response->assertOk();
            $summary = $response->json();

            $this->assertLessThan(500, ++$chunks, 'Import tidak selesai setelah 500 potongan.');
        }
        while (empty($summary['done']));

        $summary['chunks'] = $chunks;

        return $summary;
    }
}
