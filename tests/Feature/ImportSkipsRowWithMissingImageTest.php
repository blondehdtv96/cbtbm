<?php

namespace Tests\Feature;

use App\Models\BankSoal;
use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\Mapel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Import Excel: baris yang mereferensikan gambar yang belum diupload harus
 * di-skip tanpa menghentikan proses atau me-rollback baris lain yang sudah
 * berhasil diproses sebelumnya (per-baris transaction, bukan satu transaksi
 * besar untuk seluruh batch).
 */
class ImportSkipsRowWithMissingImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_baris_dengan_gambar_hilang_dilewati_baris_lain_tetap_masuk(): void
    {
        $jurusan = Jurusan::create(['nama_jurusan' => 'IPA', 'kode_jurusan' => 'IPA', 'is_active' => true]);
        $mapel = Mapel::create(['nama_mapel' => 'Matematika', 'kode_mapel' => 'MTK', 'jurusan_id' => $jurusan->id, 'is_umum' => true, 'is_active' => true]);

        $adminUser = User::create(['name' => 'Admin Test', 'email' => 'admin@test.local', 'username' => 'admin1', 'password' => bcrypt('secret'), 'role' => 'admin', 'is_active' => true]);
        $guruUser = User::create(['name' => 'Guru Test', 'email' => 'guru2@test.local', 'username' => 'guru2', 'password' => bcrypt('secret'), 'role' => 'guru', 'is_active' => true]);
        Guru::create(['nama' => 'Guru Test', 'user_id' => $guruUser->id]);

        // Mapel "hantu": model in-memory dengan id yang TIDAK ada di tabel mapels,
        // supaya BankSoal::create() untuk baris ini gagal di level DB (FK violation)
        // walau baris tsb sempat ditandai valid=true saat preview.
        $ghostMapel = new Mapel(['nama_mapel' => 'Mapel Terhapus']);
        $ghostMapel->id = 99999;
        $ghostMapel->exists = true;

        $previewData = [
            [
                'sheet' => 'Soal Pilihan Ganda',
                'row' => 2,
                'tipe_soal' => 'pg',
                'kode_mapel' => 'MTK',
                'mapel' => $mapel,
                'bobot_nilai' => 1,
                'pertanyaan' => 'Baris valid: hasil dari 3 x 3?',
                'gambar_soal_path' => null,
                'opsi' => [
                    ['label' => 'A', 'isi' => '6', 'gambar_path' => null, 'is_correct' => false],
                    ['label' => 'B', 'isi' => '9', 'gambar_path' => null, 'is_correct' => true],
                ],
                'jawaban_benar' => 'B',
                'errors' => [],
                'valid' => true,
            ],
            [
                'sheet' => 'Soal Pilihan Ganda',
                'row' => 3,
                'tipe_soal' => 'pg',
                'kode_mapel' => 'MTK',
                'mapel' => $ghostMapel,
                'bobot_nilai' => 1,
                'pertanyaan' => 'Baris gagal di DB: mapel sudah terhapus',
                'gambar_soal_path' => null,
                'opsi' => [
                    ['label' => 'A', 'isi' => '1', 'gambar_path' => null, 'is_correct' => true],
                    ['label' => 'B', 'isi' => '2', 'gambar_path' => null, 'is_correct' => false],
                ],
                'jawaban_benar' => 'A',
                'errors' => [],
                'valid' => true,
            ],
            [
                'sheet' => 'Soal Pilihan Ganda',
                'row' => 4,
                'tipe_soal' => 'pg',
                'kode_mapel' => 'MTK',
                'mapel' => $mapel,
                'bobot_nilai' => 1,
                'pertanyaan' => 'Baris ditolak: gambar opsi belum diupload',
                'gambar_soal_path' => null,
                'opsi' => [
                    ['label' => 'A', 'isi' => '1', 'gambar_path' => null, 'is_correct' => true],
                    ['label' => 'B', 'isi' => '2', 'gambar_path' => null, 'is_correct' => false],
                ],
                'jawaban_benar' => 'A',
                'errors' => ["Gambar Opsi A: file 'missing.jpg' belum diupload ke Pustaka Gambar Soal"],
                'valid' => false,
            ],
        ];

        $response = $this->actingAs($adminUser)
            ->withSession(['import_banksoal_preview' => $previewData])
            ->post(route('admin.import-banksoal.import'), ['skip_errors' => 1]);

        // Tidak boleh crash (500) walau ada baris yang gagal di tengah proses.
        $response->assertRedirect(route('admin.import-banksoal.result'));

        // Hanya baris valid pertama yang tersimpan — baris kedua (gagal di DB)
        // TIDAK ikut me-rollback baris pertama, dan baris ketiga (gambar hilang) di-skip.
        $this->assertDatabaseCount('bank_soals', 1);
        $this->assertDatabaseHas('bank_soals', ['pertanyaan' => 'Baris valid: hasil dari 3 x 3?']);
        $this->assertDatabaseMissing('bank_soals', ['pertanyaan' => 'Baris gagal di DB: mapel sudah terhapus']);
        $this->assertDatabaseMissing('bank_soals', ['pertanyaan' => 'Baris ditolak: gambar opsi belum diupload']);

        $failedRows = session('import_banksoal_failed_rows');
        $this->assertCount(1, $failedRows);
        $this->assertEquals(3, $failedRows[0]['row']);
    }
}
