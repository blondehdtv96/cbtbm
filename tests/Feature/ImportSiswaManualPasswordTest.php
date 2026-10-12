<?php

namespace Tests\Feature;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Kolom "Password (Opsional)" pada file import siswa: kalau diisi, password itu
 * yang dipakai untuk login; kalau dikosongkan, sistem generate otomatis.
 */
class ImportSiswaManualPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function buildXlsx(array $rows): string
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

        $path = storage_path('app/test_import_siswa_password.xlsx');
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

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

    public function test_password_manual_dipakai_dan_kolom_kosong_digenerate_otomatis(): void
    {
        $jurusan = Jurusan::create(['nama_jurusan' => 'TKJ', 'kode_jurusan' => 'TKJ', 'is_active' => true]);
        $kelas = Kelas::create(['nama_kelas' => '10 TKJ', 'jurusan_id' => $jurusan->id, 'is_active' => true]);

        $path = $this->buildXlsx([
            [1, '0030000100', '20240100', 'Siswa Password Manual', '10 TKJ', 'rahasia123'],
            [2, '0030000101', '20240101', 'Siswa Password Otomatis', '10 TKJ', ''],
        ]);

        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->post(route('admin.import-siswa.preview'), [
                'file' => new UploadedFile($path, 'import.xlsx', null, null, true),
            ])
            ->assertOk();

        $preview = session('import_preview');
        $this->assertCount(2, $preview);
        $this->assertTrue($preview[0]['valid']);
        $this->assertTrue($preview[0]['password_manual']);
        $this->assertSame('rahasia123', $preview[0]['password']);
        $this->assertTrue($preview[1]['valid']);
        $this->assertFalse($preview[1]['password_manual']);

        $this->actingAs($admin)
            ->post(route('admin.import-siswa.import'))
            ->assertRedirect(route('admin.import-siswa.result'));

        $manual = Siswa::where('nisn', '0030000100')->firstOrFail()->user;
        $this->assertTrue(Hash::check('rahasia123', $manual->password));
        $this->assertSame('rahasia123', $manual->plain_password);

        $auto = Siswa::where('nisn', '0030000101')->firstOrFail()->user;
        $this->assertNotEmpty($auto->plain_password);
        $this->assertNotSame('rahasia123', $auto->plain_password);
        $this->assertTrue(Hash::check($auto->plain_password, $auto->password));

        $credentials = session('import_credentials');
        $this->assertTrue($credentials[0]['password_manual']);
        $this->assertFalse($credentials[1]['password_manual']);

        @unlink($path);
    }

    public function test_password_manual_terlalu_pendek_atau_berspasi_ditandai_error(): void
    {
        $jurusan = Jurusan::create(['nama_jurusan' => 'TKJ', 'kode_jurusan' => 'TKJ', 'is_active' => true]);
        Kelas::create(['nama_kelas' => '10 TKJ', 'jurusan_id' => $jurusan->id, 'is_active' => true]);

        $path = $this->buildXlsx([
            [1, '0030000200', '20240200', 'Siswa Password Pendek', '10 TKJ', '123'],
            [2, '0030000201', '20240201', 'Siswa Password Spasi', '10 TKJ', 'pass word'],
            [3, '0030000202', '20240202', 'Siswa Password Panjang', '10 TKJ', str_repeat('a', 51)],
        ]);

        $this->actingAs($this->adminUser())
            ->post(route('admin.import-siswa.preview'), [
                'file' => new UploadedFile($path, 'import.xlsx', null, null, true),
            ])
            ->assertOk();

        $preview = session('import_preview');
        $this->assertFalse($preview[0]['valid']);
        $this->assertContains('Password minimal 4 karakter', $preview[0]['errors']);
        $this->assertFalse($preview[1]['valid']);
        $this->assertContains('Password tidak boleh mengandung spasi', $preview[1]['errors']);
        $this->assertFalse($preview[2]['valid']);
        $this->assertContains('Password maksimal 50 karakter', $preview[2]['errors']);

        @unlink($path);
    }
}
