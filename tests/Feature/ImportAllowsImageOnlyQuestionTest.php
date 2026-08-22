<?php

namespace Tests\Feature;

use App\Models\BankSoal;
use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\Mapel;
use App\Models\SoalGambarLibrary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Regresi: soal/opsi yang HANYA berupa gambar (kolom Pertanyaan/Opsi dikosongkan,
 * kolom "Gambar Soal"/"Gambar Opsi X" diisi nama file) harus tetap valid & terimport —
 * bukan ditolak sebagai "kosong" seperti sebelum perbaikan.
 */
class ImportAllowsImageOnlyQuestionTest extends TestCase
{
    use RefreshDatabase;

    private function buildXlsx(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Soal Pilihan Ganda');

        // Header row (kolom A-Q, sama urutannya dengan downloadTemplate()).
        $sheet->fromArray([
            'No', 'Kode Mapel', 'Tipe Soal', 'Bobot Nilai', 'Pertanyaan',
            'Opsi A', 'Opsi B', 'Opsi C', 'Opsi D', 'Opsi E', 'Jawaban Benar',
            'Gambar Soal', 'Gambar Opsi A', 'Gambar Opsi B', 'Gambar Opsi C', 'Gambar Opsi D', 'Gambar Opsi E',
        ], null, 'A1');

        // Baris soal gambar-saja: Pertanyaan & Opsi A/B teks kosong, hanya nama file gambar diisi.
        $sheet->fromArray([
            1, 'MTK', 'pg', 1, '',
            '', '', '', '', '', 'A',
            'soal-gambar-only.jpg', 'opsi-a.jpg', 'opsi-b.jpg', '', '', '',
        ], null, 'A2');

        $path = storage_path('app/test_import_image_only.xlsx');
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    public function test_soal_dan_opsi_gambar_saja_tanpa_teks_dianggap_valid(): void
    {
        $jurusan = Jurusan::create(['nama_jurusan' => 'IPA', 'kode_jurusan' => 'IPA', 'is_active' => true]);
        $mapel = Mapel::create(['nama_mapel' => 'Matematika', 'kode_mapel' => 'MTK', 'jurusan_id' => $jurusan->id, 'is_umum' => true, 'is_active' => true]);

        $adminUser = User::create(['name' => 'Admin Test', 'email' => 'admin@test.local', 'username' => 'admin1', 'password' => bcrypt('secret'), 'role' => 'admin', 'is_active' => true]);
        $guruUser = User::create(['name' => 'Guru Test', 'email' => 'guru3@test.local', 'username' => 'guru3', 'password' => bcrypt('secret'), 'role' => 'guru', 'is_active' => true]);
        Guru::create(['nama' => 'Guru Test', 'user_id' => $guruUser->id]);

        foreach (['soal-gambar-only.jpg', 'opsi-a.jpg', 'opsi-b.jpg'] as $fileName) {
            SoalGambarLibrary::create([
                'original_filename' => $fileName,
                'stored_path' => 'soal-gambar/library/' . $fileName,
                'size' => 1000,
                'mime_type' => 'image/jpeg',
                'uploaded_by' => $adminUser->id,
            ]);
        }

        $xlsxPath = $this->buildXlsx();
        $uploaded = new UploadedFile($xlsxPath, 'import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $previewResponse = $this->actingAs($adminUser)
            ->post(route('admin.import-banksoal.preview'), ['file' => $uploaded]);

        $previewResponse->assertOk();
        // Tidak boleh muncul error "kosong" untuk baris ini — inti dari perbaikan.
        $previewResponse->assertDontSee('Pertanyaan kosong', false);
        $previewResponse->assertDontSee('Opsi A kosong', false);
        $previewResponse->assertDontSee('Opsi B kosong', false);
        $previewResponse->assertSee('Siap import');

        $importResponse = $this->post(route('admin.import-banksoal.import'));
        $importResponse->assertRedirect(route('admin.import-banksoal.result'));

        $this->assertDatabaseCount('bank_soals', 1);
        $bankSoal = BankSoal::first();
        $this->assertEquals('soal-gambar/library/soal-gambar-only.jpg', $bankSoal->gambar_soal);

        $opsiA = $bankSoal->opsiJawabans()->where('opsi_label', 'A')->first();
        $opsiB = $bankSoal->opsiJawabans()->where('opsi_label', 'B')->first();
        $this->assertEquals('soal-gambar/library/opsi-a.jpg', $opsiA->gambar_opsi);
        $this->assertEquals('soal-gambar/library/opsi-b.jpg', $opsiB->gambar_opsi);
        $this->assertTrue((bool) $opsiA->is_correct);

        @unlink($xlsxPath);
    }
}
