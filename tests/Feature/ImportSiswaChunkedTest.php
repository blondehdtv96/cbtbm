<?php

namespace Tests\Feature;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\RunsSiswaImport;
use Tests\TestCase;

/**
 * Regresi 504 Gateway Time-out pada import siswa berjumlah besar.
 *
 * Dulu seluruh file dikerjakan dalam SATU request. Hash bcrypt ±0,26 detik per
 * siswa, jadi 1.200 siswa = ±5 menit kerja CPU dan nginx/php-fpm memutus
 * koneksinya lebih dulu. Sekarang pekerjaannya dipotong menjadi banyak request
 * kecil yang masing-masing punya jatah waktu, dengan posisi (cursor) disimpan
 * di berkas antrean sehingga bisa dilanjutkan.
 */
class ImportSiswaChunkedTest extends TestCase
{
    use RefreshDatabase;
    use RunsSiswaImport;

    private function adminUser(): User
    {
        return User::create([
            'name' => 'Admin Chunk',
            'email' => 'admin.chunk@test.local',
            'username' => 'adminchunk',
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

    private function rows(int $jumlah, int $mulai = 1): array
    {
        $rows = [];
        for ($i = $mulai; $i < $mulai + $jumlah; $i++) {
            $urut = str_pad((string) $i, 5, '0', STR_PAD_LEFT);
            $rows[] = [$i, '0030' . $urut, '20240' . $urut, 'Siswa Massal ' . $i, '10 TKJ', ''];
        }

        return $rows;
    }

    public function test_import_1200_siswa_selesai_lewat_beberapa_potongan(): void
    {
        $kelas = $this->kelas();
        $admin = $this->adminUser();
        $jumlah = 1200;

        $path = $this->makeImportXlsx($this->rows($jumlah));

        $preview = $this->previewImport($admin, $path);
        $this->assertCount($jumlah, $preview);

        $insertStatements = 0;
        DB::listen(function ($query) use (&$insertStatements) {
            if (str_starts_with(strtolower(trim($query->sql)), 'insert into')) {
                $insertStatements++;
            }
        });

        $summary = $this->runImportToCompletion($admin);

        // Dikerjakan lebih dari satu request — inti perbaikannya.
        $this->assertGreaterThan(1, $summary['chunks'], 'Import besar harus dipotong jadi beberapa request.');
        $this->assertSame($jumlah, $summary['total']);
        $this->assertSame($jumlah, $summary['processed']);
        $this->assertSame($jumlah, $summary['imported']);
        $this->assertSame(0, $summary['skipped']);
        $this->assertSame(100, $summary['percent']);

        // Semua data benar-benar masuk.
        $this->assertSame($jumlah, Siswa::count());
        $this->assertSame($jumlah, User::where('role', 'siswa')->count());

        // Tetap insert massal: 2 statement per potongan (users + siswas), bukan per siswa.
        $this->assertLessThanOrEqual(
            $summary['chunks'] * 2 + 2,
            $insertStatements,
            'Import harus tetap memakai insert massal per potongan.'
        );

        // Relasi tidak tertukar antar potongan.
        $terakhir = Siswa::where('nisn', '003001200')->firstOrFail();
        $this->assertSame('siswa.003001200@cbtsmk.local', $terakhir->user->email);
        $this->assertSame($kelas->id, $terakhir->kelas_id);

        $this->actingAs($admin)
            ->get(route('admin.import-siswa.result'))
            ->assertOk()
            ->assertViewHas('successCount', $jumlah);

        // Satu baris log untuk satu import, walau dikerjakan 12+ potongan dan
        // halaman progress sempat memanggil potongan terakhir lebih dari sekali.
        $this->actingAs($admin)->postJson(route('admin.import-siswa.process-chunk'))->assertOk();
        $this->assertSame(1, \App\Models\ActivityLog::where('action', 'import')->where('module', 'siswa')->count());

        @unlink($path);
    }

    public function test_halaman_progress_melaporkan_posisi_dan_bisa_dilanjutkan(): void
    {
        $this->kelas();
        $admin = $this->adminUser();
        $jumlah = 150;

        $path = $this->makeImportXlsx($this->rows($jumlah));
        $this->previewImport($admin, $path);

        $this->actingAs($admin)
            ->post(route('admin.import-siswa.import'))
            ->assertRedirect(route('admin.import-siswa.progress'));

        // Satu potongan saja, lalu halaman progress harus melaporkan sisa pekerjaan.
        $first = $this->actingAs($admin)->postJson(route('admin.import-siswa.process-chunk'))->assertOk()->json();
        $this->assertFalse($first['done']);
        $this->assertGreaterThan(0, $first['processed']);
        $this->assertLessThan($jumlah, $first['processed']);

        $this->actingAs($admin)
            ->get(route('admin.import-siswa.progress'))
            ->assertOk()
            ->assertViewHas('done', $first['processed'])
            ->assertViewHas('total', $jumlah);

        // Halaman Import Siswa menawarkan melanjutkan proses yang terputus.
        $this->actingAs($admin)
            ->get(route('admin.import-siswa.index'))
            ->assertOk()
            ->assertSee('Lanjutkan Import');

        // Sisanya diselesaikan tanpa mengulang data yang sudah masuk.
        do {
            $summary = $this->actingAs($admin)->postJson(route('admin.import-siswa.process-chunk'))->assertOk()->json();
        }
        while (empty($summary['done']));

        $this->assertSame($jumlah, Siswa::count());
        $this->assertSame($jumlah, $summary['imported']);

        @unlink($path);
    }

    public function test_nisn_yang_sudah_terdaftar_dilewati_bukan_menggagalkan_potongan(): void
    {
        $kelas = $this->kelas();
        $admin = $this->adminUser();

        $path = $this->makeImportXlsx($this->rows(5));
        $this->previewImport($admin, $path);

        // Siswa dengan NISN yang sama masuk dari jalur lain SETELAH preview dibuat.
        $penyusup = User::create([
            'name' => 'Siswa Duluan',
            'email' => 'siswa.003000003@cbtsmk.local',
            'password' => bcrypt('secret'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        Siswa::create([
            'nis' => '2024000033',
            'nisn' => '003000003',
            'nama' => 'Siswa Duluan',
            'kelas_id' => $kelas->id,
            'user_id' => $penyusup->id,
        ]);

        $summary = $this->runImportToCompletion($admin);

        $this->assertSame(4, $summary['imported']);
        $this->assertSame(1, $summary['skipped']);
        $this->assertSame(5, Siswa::where('nama', 'like', 'Siswa Massal%')->count() + 1);

        $this->actingAs($admin)
            ->get(route('admin.import-siswa.result'))
            ->assertOk()
            ->assertSee('sudah terdaftar');

        @unlink($path);
    }

    public function test_potongan_tanpa_proses_aktif_ditolak(): void
    {
        $this->actingAs($this->adminUser())
            ->postJson(route('admin.import-siswa.process-chunk'))
            ->assertStatus(410);
    }
}
