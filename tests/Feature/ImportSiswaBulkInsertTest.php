<?php

namespace Tests\Feature;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Regresi deadlock import siswa.
 *
 * Dulu import membuat akun satu per satu DI DALAM satu transaksi panjang,
 * lengkap dengan Hash::make() (bcrypt) per siswa — transaksi bisa terbuka
 * puluhan detik dan menahan lock tabel users, sampai MySQL membunuhnya:
 * "1213 Deadlock found when trying to get lock".
 *
 * Sekarang hash dihitung di luar transaksi dan baris ditulis dengan insert
 * massal, jadi transaksinya hanya beberapa statement. Test ini mengunci
 * perilaku itu: hasil akhir tetap benar DAN jumlah statement insert tetap
 * sedikit (tidak kembali jadi satu insert per siswa).
 */
class ImportSiswaBulkInsertTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::create([
            'name' => 'Admin Bulk',
            'email' => 'admin.bulk@test.local',
            'username' => 'adminbulk',
            'password' => bcrypt('secret'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    private function buildXlsx(int $jumlah): string
    {
        $rows = [];
        for ($i = 1; $i <= $jumlah; $i++) {
            $urut = str_pad((string) $i, 4, '0', STR_PAD_LEFT);
            $rows[] = [$i, '00300' . $urut, '202400' . $urut, 'Siswa Massal ' . $i, '10 TKJ', ''];
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Import Siswa');
        $sheet->fromArray(
            ['No', 'NISN', 'NIS', 'Nama Lengkap', 'Kelas (ID atau Nama)', 'Password (Opsional)'],
            null,
            'A1'
        );
        $sheet->fromArray($rows, null, 'A2');

        $path = storage_path('app/test_import_siswa_bulk.xlsx');
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    public function test_import_banyak_siswa_tersimpan_lengkap_dengan_insert_massal(): void
    {
        $jurusan = Jurusan::create(['nama_jurusan' => 'TKJ', 'kode_jurusan' => 'TKJ', 'is_active' => true]);
        $kelas = Kelas::create(['nama_kelas' => '10 TKJ', 'jurusan_id' => $jurusan->id, 'is_active' => true]);

        // Lebih dari satu chunk (INSERT_CHUNK = 100) supaya pemotongan ikut teruji.
        $jumlah = 150;
        $path = $this->buildXlsx($jumlah);
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->post(route('admin.import-siswa.preview'), [
                'file' => new UploadedFile($path, 'import.xlsx', null, null, true),
            ])
            ->assertOk();

        $this->assertCount($jumlah, session('import_preview'));

        $insertStatements = [];
        DB::listen(function ($query) use (&$insertStatements) {
            if (str_starts_with(strtolower(trim($query->sql)), 'insert into')) {
                $insertStatements[] = $query->sql;
            }
        });

        $this->actingAs($admin)
            ->post(route('admin.import-siswa.import'))
            ->assertRedirect(route('admin.import-siswa.result'));

        // Semua data masuk utuh.
        $this->assertSame($jumlah, Siswa::count());
        $this->assertSame($jumlah, User::where('role', 'siswa')->count());
        $this->assertSame($jumlah, session('import_success_count'));
        $this->assertCount($jumlah, session('import_credentials'));

        // user_id setiap siswa benar-benar menunjuk akun dengan email NISN-nya,
        // bukan tertukar antar baris saat id dipetakan ulang setelah insert massal.
        $siswaPertama = Siswa::where('nisn', '003000001')->firstOrFail();
        $this->assertSame('siswa.003000001@cbtsmk.local', $siswaPertama->user->email);
        $siswaTerakhir = Siswa::where('nisn', '003000150')->firstOrFail();
        $this->assertSame('siswa.003000150@cbtsmk.local', $siswaTerakhir->user->email);
        $this->assertSame($kelas->id, $siswaTerakhir->kelas_id);

        // Password hasil generate tetap bisa dipakai login (hash valid walau
        // ditulis lewat insert massal yang melewati cast model).
        $kredensialTerakhir = collect(session('import_credentials'))->firstWhere('nisn', '003000150');
        $this->assertTrue(Hash::check($kredensialTerakhir['password'], $siswaTerakhir->user->password));

        // 150 siswa = 2 chunk × (1 insert users + 1 insert siswas) + 1 insert activity_logs.
        // Kalau nanti ada yang mengembalikan pola "create() per baris", jumlah ini
        // meledak jadi ratusan dan test gagal.
        $this->assertLessThanOrEqual(
            6,
            count($insertStatements),
            'Import harus memakai insert massal, bukan satu insert per siswa. Statement: ' . count($insertStatements)
        );

        @unlink($path);
    }
}
