<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Regresi: upload gambar ke Pustaka Gambar Soal harus tetap berfungsi untuk
 * request JSON (dipakai oleh upload bertahap di frontend agar tidak kena
 * batas PHP max_file_uploads saat memilih banyak file sekaligus).
 */
class SoalGambarLibraryBatchUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_merespon_json_dengan_jumlah_uploaded_dan_skipped(): void
    {
        $adminUser = User::create(['name' => 'Admin Test', 'email' => 'admin@test.local', 'username' => 'admin1', 'password' => bcrypt('secret'), 'role' => 'admin', 'is_active' => true]);

        $response = $this->actingAs($adminUser)
            ->postJson(route('admin.soal-gambar.store'), [
                'gambar' => [
                    UploadedFile::fake()->image('batch1.jpg')->size(100),
                    UploadedFile::fake()->image('batch2.jpg')->size(100),
                ],
            ]);

        $response->assertOk();
        $response->assertJson(['uploaded' => 2, 'skipped' => []]);
        $this->assertDatabaseCount('soal_gambar_library', 2);
    }

    public function test_batch_kedua_dengan_nama_file_sama_dilewati_bukan_error(): void
    {
        $adminUser = User::create(['name' => 'Admin Test', 'email' => 'admin@test.local', 'username' => 'admin1', 'password' => bcrypt('secret'), 'role' => 'admin', 'is_active' => true]);

        $this->actingAs($adminUser)->postJson(route('admin.soal-gambar.store'), [
            'gambar' => [UploadedFile::fake()->image('dup.jpg')->size(100)],
        ]);

        $response = $this->actingAs($adminUser)->postJson(route('admin.soal-gambar.store'), [
            'gambar' => [
                UploadedFile::fake()->image('dup.jpg')->size(100),
                UploadedFile::fake()->image('unik.jpg')->size(100),
            ],
        ]);

        $response->assertOk();
        $response->assertJson(['uploaded' => 1, 'skipped' => ['dup.jpg']]);
        $this->assertDatabaseCount('soal_gambar_library', 2);
    }
}
