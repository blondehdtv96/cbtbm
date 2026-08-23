<?php

namespace Tests\Feature;

use App\Models\BankSoal;
use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\PesertaUjian;
use App\Models\Siswa;
use App\Models\Ujian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Keselamatan data: begitu ada peserta yang sudah mulai mengerjakan (status
 * != 'belum'), soal yang terpasang pada ujian TIDAK BOLEH diubah lagi lewat
 * edit — soal_order per-siswa dan JawabanSiswa yang sudah dibuat mengacu ke
 * bank_soal_id lama, mengganti set soal akan merusak data itu.
 */
class UjianEditBlocksSoalChangeAfterPesertaStartedTest extends TestCase
{
    use RefreshDatabase;

    public function test_soal_tidak_berubah_kalau_sudah_ada_peserta_mulai_mengerjakan(): void
    {
        $jurusan = Jurusan::create(['nama_jurusan' => 'IPA', 'kode_jurusan' => 'IPA', 'is_active' => true]);
        $kelas = Kelas::create(['nama_kelas' => 'X-1', 'jurusan_id' => $jurusan->id, 'tingkat' => '10', 'is_active' => true]);
        $mapel = Mapel::create(['nama_mapel' => 'Matematika', 'kode_mapel' => 'MTK', 'jurusan_id' => $jurusan->id, 'is_umum' => true, 'is_active' => true]);

        $adminUser = User::create(['name' => 'Admin Test', 'email' => 'admin@test.local', 'username' => 'admin1', 'password' => bcrypt('secret'), 'role' => 'admin', 'is_active' => true]);
        $guruUser = User::create(['name' => 'Guru Test', 'email' => 'guru@test.local', 'username' => 'guru1', 'password' => bcrypt('secret'), 'role' => 'guru', 'is_active' => true]);
        $guru = Guru::create(['nama' => 'Guru Test', 'user_id' => $guruUser->id]);
        $siswaUser = User::create(['name' => 'Siswa Test', 'email' => 'siswa@test.local', 'username' => 'siswa1', 'password' => bcrypt('secret'), 'role' => 'siswa', 'is_active' => true]);
        $siswa = Siswa::create(['nis' => '1001', 'nama' => 'Siswa Test', 'kelas_id' => $kelas->id, 'user_id' => $siswaUser->id]);

        $soalLama1 = BankSoal::create(['mapel_id' => $mapel->id, 'guru_id' => $guru->id, 'tipe_soal' => 'pg', 'bobot_nilai' => 1, 'pertanyaan' => 'Soal Lama 1', 'status' => 'aktif']);
        $soalLama2 = BankSoal::create(['mapel_id' => $mapel->id, 'guru_id' => $guru->id, 'tipe_soal' => 'pg', 'bobot_nilai' => 1, 'pertanyaan' => 'Soal Lama 2', 'status' => 'aktif']);
        $soalBaru = BankSoal::create(['mapel_id' => $mapel->id, 'guru_id' => $guru->id, 'tipe_soal' => 'pg', 'bobot_nilai' => 1, 'pertanyaan' => 'Soal Baru', 'status' => 'aktif']);

        $ujian = Ujian::create([
            'nama_ujian' => 'Ulangan Berjalan',
            'jenis_ujian' => 'harian',
            'mapel_id' => $mapel->id,
            'guru_id' => $guru->id,
            'durasi_menit' => 60,
            'tanggal_mulai' => now()->subHour(),
            'tanggal_selesai' => now()->addHour(),
            'metode_soal' => 'manual',
            'jumlah_soal' => 2,
            'status' => 'publish',
            'token' => 'ABCDE',
        ]);
        $ujian->kelasList()->attach($kelas->id);
        $ujian->bankSoals()->attach($soalLama1->id, ['nomor_urut' => 1]);
        $ujian->bankSoals()->attach($soalLama2->id, ['nomor_urut' => 2]);

        PesertaUjian::create([
            'ujian_id' => $ujian->id,
            'siswa_id' => $siswa->id,
            'status' => 'sedang',
            'waktu_mulai' => now(),
            'soal_order' => json_encode([$soalLama1->id, $soalLama2->id]),
        ]);

        $response = $this->actingAs($adminUser)->put(route('ujian.update', $ujian), [
            'nama_ujian' => $ujian->nama_ujian,
            'jenis_ujian' => 'harian',
            'mapel_id' => $mapel->id,
            'guru_id' => $guru->id,
            'durasi_menit' => 60,
            'tanggal_mulai' => now()->subHour()->format('Y-m-d\TH:i'),
            'tanggal_selesai' => now()->addHour()->format('Y-m-d\TH:i'),
            'metode_soal' => 'manual',
            'soal_ids' => [$soalBaru->id],
            'kelas_ids' => [$kelas->id],
        ]);

        $response->assertRedirect(route('ujian.index'));

        $attached = $ujian->bankSoals()->orderBy('ujian_bank_soals.nomor_urut')->pluck('bank_soals.id')->toArray();
        $this->assertEquals([$soalLama1->id, $soalLama2->id], $attached);
    }
}
