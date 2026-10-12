<?php

namespace Tests\Feature;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\RunsSiswaImport;
use Tests\TestCase;

/**
 * Kolom "Password (Opsional)" pada file import siswa: kalau diisi, password itu
 * yang dipakai untuk login; kalau dikosongkan, sistem generate otomatis.
 */
class ImportSiswaManualPasswordTest extends TestCase
{
    use RefreshDatabase;
    use RunsSiswaImport;

    private function adminUser(): User
    {
        return User::create([
            'name' => 'Admin Test',
            'email' => 'admin.import@test.local',
            'username' => 'adminimport',
            'password' => bcrypt('secret'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    private function kelas(): Kelas
    {
        $jurusan = Jurusan::create(['nama_jurusan' => 'TKJ', 'kode_jurusan' => 'TKJ', 'is_active' => true]);

        return Kelas::create(['nama_kelas' => '10 TKJ', 'jurusan_id' => $jurusan->id, 'is_active' => true]);
    }

    public function test_password_manual_dipakai_dan_kolom_kosong_digenerate_otomatis(): void
    {
        $this->kelas();
        $admin = $this->adminUser();

        $path = $this->makeImportXlsx([
            [1, '0030000100', '20240100', 'Siswa Password Manual', '10 TKJ', 'rahasia123'],
            [2, '0030000101', '20240101', 'Siswa Password Otomatis', '10 TKJ', ''],
        ]);

        $preview = $this->previewImport($admin, $path);

        $this->assertCount(2, $preview);
        $this->assertTrue($preview[0]['valid']);
        $this->assertTrue($preview[0]['password_manual']);
        $this->assertSame('rahasia123', $preview[0]['password']);
        $this->assertTrue($preview[1]['valid']);
        $this->assertFalse($preview[1]['password_manual']);

        $summary = $this->runImportToCompletion($admin);
        $this->assertSame(2, $summary['imported']);

        $manual = Siswa::where('nisn', '0030000100')->firstOrFail()->user;
        $this->assertTrue(Hash::check('rahasia123', $manual->password));
        $this->assertSame('rahasia123', $manual->plain_password);

        $auto = Siswa::where('nisn', '0030000101')->firstOrFail()->user;
        $this->assertNotEmpty($auto->plain_password);
        $this->assertNotSame('rahasia123', $auto->plain_password);
        $this->assertTrue(Hash::check($auto->plain_password, $auto->password));

        $credentials = $this->actingAs($admin)
            ->get(route('admin.import-siswa.result'))
            ->assertOk()
            ->viewData('credentials');

        $this->assertTrue($credentials[0]['password_manual']);
        $this->assertFalse($credentials[1]['password_manual']);

        @unlink($path);
    }

    public function test_password_manual_terlalu_pendek_atau_berspasi_ditandai_error(): void
    {
        $this->kelas();

        $path = $this->makeImportXlsx([
            [1, '0030000200', '20240200', 'Siswa Password Pendek', '10 TKJ', '123'],
            [2, '0030000201', '20240201', 'Siswa Password Spasi', '10 TKJ', 'pass word'],
            [3, '0030000202', '20240202', 'Siswa Password Panjang', '10 TKJ', str_repeat('a', 51)],
        ]);

        $preview = $this->previewImport($this->adminUser(), $path);

        $this->assertFalse($preview[0]['valid']);
        $this->assertContains('Password minimal 4 karakter', $preview[0]['errors']);
        $this->assertFalse($preview[1]['valid']);
        $this->assertContains('Password tidak boleh mengandung spasi', $preview[1]['errors']);
        $this->assertFalse($preview[2]['valid']);
        $this->assertContains('Password maksimal 50 karakter', $preview[2]['errors']);

        @unlink($path);
    }
}
