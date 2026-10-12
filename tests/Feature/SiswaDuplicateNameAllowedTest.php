<?php

namespace Tests\Feature;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Nama siswa kembar BUKAN duplikat: selama NIS (dan NISN) berbeda, data harus
 * tetap diterima — baik lewat import Excel maupun form tambah/edit manual.
 */
class SiswaDuplicateNameAllowedTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::create([
            'name' => 'Admin Nama',
            'email' => 'admin.nama@test.local',
            'username' => 'adminnama',
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

        $path = storage_path('app/test_import_siswa_nama_kembar.xlsx');
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    public function test_import_menerima_dua_siswa_dengan_nama_sama_asal_nis_berbeda(): void
    {
        $this->kelas();

        $path = $this->buildXlsx([
            [1, '0030000300', '20240300', 'Ahmad Rafi Pratama', '10 TKJ', ''],
            [2, '0030000301', '20240301', 'Ahmad Rafi Pratama', '10 TKJ', ''],
        ]);

        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->post(route('admin.import-siswa.preview'), [
                'file' => new UploadedFile($path, 'import.xlsx', null, null, true),
            ])
            ->assertOk();

        $preview = session('import_preview');
        $this->assertCount(2, $preview);
        $this->assertTrue($preview[0]['valid'], 'Baris 1 harus valid');
        $this->assertTrue($preview[1]['valid'], 'Nama kembar tidak boleh dianggap duplikat: ' . implode(', ', $preview[1]['errors']));

        $this->actingAs($admin)
            ->post(route('admin.import-siswa.import'))
            ->assertRedirect(route('admin.import-siswa.result'));

        $this->assertSame(2, Siswa::where('nama', 'Ahmad Rafi Pratama')->count());

        @unlink($path);
    }

    public function test_import_menerima_nama_yang_sudah_ada_di_database(): void
    {
        $kelas = $this->kelas();

        $existingUser = User::create([
            'name' => 'Bella Anisa Putri',
            'email' => 'siswa.0030000400@cbtsmk.local',
            'password' => bcrypt('secret'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        Siswa::create([
            'nis' => '20240400',
            'nisn' => '0030000400',
            'nama' => 'Bella Anisa Putri',
            'kelas_id' => $kelas->id,
            'user_id' => $existingUser->id,
        ]);

        $path = $this->buildXlsx([
            [1, '0030000401', '20240401', 'Bella Anisa Putri', '10 TKJ', ''],
        ]);

        $this->actingAs($this->adminUser())
            ->post(route('admin.import-siswa.preview'), [
                'file' => new UploadedFile($path, 'import.xlsx', null, null, true),
            ])
            ->assertOk();

        $preview = session('import_preview');
        $this->assertTrue($preview[0]['valid'], 'Nama yang sudah ada di sistem tidak boleh ditolak: ' . implode(', ', $preview[0]['errors']));

        @unlink($path);
    }

    public function test_import_tetap_menolak_nis_dan_nisn_duplikat(): void
    {
        $this->kelas();

        $path = $this->buildXlsx([
            [1, '0030000500', '20240500', 'Candra Dwi Saputra', '10 TKJ', ''],
            [2, '0030000500', '20240501', 'Candra Dwi Saputra', '10 TKJ', ''],
            [3, '0030000502', '20240500', 'Candra Dwi Saputra', '10 TKJ', ''],
        ]);

        $this->actingAs($this->adminUser())
            ->post(route('admin.import-siswa.preview'), [
                'file' => new UploadedFile($path, 'import.xlsx', null, null, true),
            ])
            ->assertOk();

        $preview = session('import_preview');
        $this->assertTrue($preview[0]['valid']);
        $this->assertFalse($preview[1]['valid']);
        $this->assertContains("NISN '0030000500' duplikat dalam file", $preview[1]['errors']);
        $this->assertFalse($preview[2]['valid']);
        $this->assertContains("NIS '20240500' duplikat dalam file", $preview[2]['errors']);

        @unlink($path);
    }

    public function test_tambah_siswa_manual_dengan_nama_yang_sudah_ada_diterima(): void
    {
        $kelas = $this->kelas();

        $existingUser = User::create([
            'name' => 'Dewi Lestari',
            'email' => 'siswa.0030000600@cbtsmk.local',
            'password' => bcrypt('secret'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        Siswa::create([
            'nis' => '20240600',
            'nisn' => '0030000600',
            'nama' => 'Dewi Lestari',
            'kelas_id' => $kelas->id,
            'user_id' => $existingUser->id,
        ]);

        $this->actingAs($this->adminUser())
            ->post(route('admin.siswa.store'), [
                'name' => 'Dewi Lestari',
                'nisn' => '0030000601',
                'nis' => '20240601',
                'kelas_id' => $kelas->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Siswa::where('nama', 'Dewi Lestari')->count());
    }

    public function test_tambah_siswa_manual_tetap_menolak_nis_duplikat(): void
    {
        $kelas = $this->kelas();

        $existingUser = User::create([
            'name' => 'Eka Saputra',
            'email' => 'siswa.0030000700@cbtsmk.local',
            'password' => bcrypt('secret'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        Siswa::create([
            'nis' => '20240700',
            'nisn' => '0030000700',
            'nama' => 'Eka Saputra',
            'kelas_id' => $kelas->id,
            'user_id' => $existingUser->id,
        ]);

        $this->actingAs($this->adminUser())
            ->post(route('admin.siswa.store'), [
                'name' => 'Nama Lain',
                'nisn' => '0030000701',
                'nis' => '20240700',
                'kelas_id' => $kelas->id,
            ])
            ->assertSessionHasErrors('nis');

        $this->assertSame(1, Siswa::count());
    }
}
