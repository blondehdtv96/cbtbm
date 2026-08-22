<?php

namespace Tests\Feature;

use App\Models\BankSoal;
use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\Mapel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Upload gambar opsi (A-E) harus menolak file yang bukan jpg/jpeg/png atau
 * yang melebihi batas ukuran, dengan pesan error yang jelas — dan tidak boleh
 * membuat data BankSoal/OpsiJawaban dari request yang ditolak.
 */
class OpsiGambarUploadValidationTest extends TestCase
{
    use RefreshDatabase;

    private function actingGuru(): array
    {
        $jurusan = Jurusan::create(['nama_jurusan' => 'IPA', 'kode_jurusan' => 'IPA', 'is_active' => true]);
        $mapel = Mapel::create(['nama_mapel' => 'Matematika', 'kode_mapel' => 'MTK', 'jurusan_id' => $jurusan->id, 'is_umum' => true, 'is_active' => true]);
        $guruUser = User::create(['name' => 'Guru Test', 'email' => 'guru@test.local', 'username' => 'guru1', 'password' => bcrypt('secret'), 'role' => 'guru', 'is_active' => true]);
        $guru = Guru::create(['nama' => 'Guru Test', 'user_id' => $guruUser->id]);

        return [$guruUser, $guru, $mapel];
    }

    private function baseFormData(Mapel $mapel): array
    {
        return [
            'mapel_id' => $mapel->id,
            'tipe_soal' => 'pg',
            'bobot_nilai' => 1,
            'pertanyaan' => 'Soal uji validasi gambar opsi',
            'opsi_label' => ['A', 'B', 'C', 'D', 'E'],
            'opsi_isi' => ['Opsi A', 'Opsi B', '', '', ''],
            'opsi_correct' => [0],
        ];
    }

    public function test_upload_gambar_opsi_dengan_mime_invalid_ditolak(): void
    {
        [$guruUser, , $mapel] = $this->actingGuru();

        $data = $this->baseFormData($mapel);
        $data['opsi_gambar'] = [UploadedFile::fake()->create('dokumen.pdf', 10, 'application/pdf')];

        $response = $this->actingAs($guruUser)->post(route('banksoal.store'), $data);

        $response->assertSessionHasErrors('opsi_gambar.0');
        $this->assertDatabaseCount('bank_soals', 0);
    }

    public function test_upload_gambar_opsi_melebihi_batas_ukuran_ditolak(): void
    {
        [$guruUser, , $mapel] = $this->actingGuru();

        $data = $this->baseFormData($mapel);
        // max:1024 (1MB) — file 2000KB harus ditolak.
        $data['opsi_gambar'] = [UploadedFile::fake()->image('opsi.jpg')->size(2000)];

        $response = $this->actingAs($guruUser)->post(route('banksoal.store'), $data);

        $response->assertSessionHasErrors('opsi_gambar.0');
        $this->assertDatabaseCount('bank_soals', 0);
    }

    public function test_upload_gambar_opsi_valid_diterima(): void
    {
        [$guruUser, , $mapel] = $this->actingGuru();

        $data = $this->baseFormData($mapel);
        $data['opsi_gambar'] = [UploadedFile::fake()->image('opsi.jpg')->size(200)];

        $response = $this->actingAs($guruUser)->post(route('banksoal.store'), $data);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseCount('bank_soals', 1);
        $bankSoal = BankSoal::first();
        $opsiA = $bankSoal->opsiJawabans()->where('opsi_label', 'A')->first();
        $this->assertNotNull($opsiA->gambar_opsi);
    }
}
