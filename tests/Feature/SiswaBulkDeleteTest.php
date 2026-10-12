<?php

namespace Tests\Feature;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Hapus banyak siswa sekarang memakai dua statement set-based (bukan satu
 * delete per baris di dalam transaksi panjang) supaya transaksinya pendek dan
 * tidak bertabrakan dengan tulisan lain ke tabel users — sumber deadlock yang
 * sama seperti pada import siswa.
 */
class SiswaBulkDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::create([
            'name' => 'Admin Hapus',
            'email' => 'admin.hapus@test.local',
            'username' => 'adminhapus',
            'password' => bcrypt('secret'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    private function siswa(Kelas $kelas, string $nisn): Siswa
    {
        $user = User::create([
            'name' => 'Siswa ' . $nisn,
            'email' => 'siswa.' . $nisn . '@cbtsmk.local',
            'password' => bcrypt('secret'),
            'role' => 'siswa',
            'is_active' => true,
        ]);

        return Siswa::create([
            'nis' => '2024' . $nisn,
            'nisn' => $nisn,
            'nama' => 'Siswa ' . $nisn,
            'kelas_id' => $kelas->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_bulk_delete_menghapus_siswa_beserta_akunnya_dengan_sedikit_statement(): void
    {
        $jurusan = Jurusan::create(['nama_jurusan' => 'TKJ', 'kode_jurusan' => 'TKJ', 'is_active' => true]);
        $kelas = Kelas::create(['nama_kelas' => '10 TKJ', 'jurusan_id' => $jurusan->id, 'is_active' => true]);

        $hapus = collect(['003000801', '003000802', '003000803'])->map(fn($nisn) => $this->siswa($kelas, $nisn));
        $sisa = $this->siswa($kelas, '003000899');

        $deleteStatements = [];
        DB::listen(function ($query) use (&$deleteStatements) {
            if (str_starts_with(strtolower(trim($query->sql)), 'delete from')) {
                $deleteStatements[] = $query->sql;
            }
        });

        $this->actingAs($this->adminUser())
            ->delete(route('admin.siswa.bulk-destroy'), ['ids' => $hapus->pluck('id')->all()])
            ->assertRedirect(route('admin.siswa.index'));

        foreach ($hapus as $siswa) {
            $this->assertDatabaseMissing('siswas', ['id' => $siswa->id]);
            $this->assertDatabaseMissing('users', ['id' => $siswa->user_id]);
        }

        $this->assertDatabaseHas('siswas', ['id' => $sisa->id]);
        $this->assertDatabaseHas('users', ['id' => $sisa->user_id]);

        // Dua statement: hapus siswas lalu hapus users — bukan 3 siswa × 2 delete.
        $this->assertLessThanOrEqual(
            2,
            count($deleteStatements),
            'Bulk delete harus set-based. Statement: ' . implode(' | ', $deleteStatements)
        );
    }

    public function test_bulk_delete_tanpa_pilihan_tidak_menghapus_apa_pun(): void
    {
        $jurusan = Jurusan::create(['nama_jurusan' => 'TKJ', 'kode_jurusan' => 'TKJ', 'is_active' => true]);
        $kelas = Kelas::create(['nama_kelas' => '10 TKJ', 'jurusan_id' => $jurusan->id, 'is_active' => true]);
        $this->siswa($kelas, '003000901');

        $this->actingAs($this->adminUser())
            ->delete(route('admin.siswa.bulk-destroy'), ['ids' => []])
            ->assertRedirect();

        $this->assertSame(1, Siswa::count());
    }
}
