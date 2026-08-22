<?php

namespace Tests\Feature;

use App\Models\BankSoal;
use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\OpsiJawaban;
use App\Models\PesertaUjian;
use App\Models\Siswa;
use App\Models\Ujian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test: soal PG teks lama (tanpa gambar_soal/gambar_opsi) harus tetap
 * bisa dikerjakan siswa persis seperti sebelum fitur gambar opsi ditambahkan.
 */
class ExistingTextSoalStillWorksTest extends TestCase
{
    use RefreshDatabase;

    public function test_siswa_bisa_mengerjakan_soal_pg_teks_lama_tanpa_gambar(): void
    {
        $jurusan = Jurusan::create(['nama_jurusan' => 'IPA', 'kode_jurusan' => 'IPA', 'is_active' => true]);
        $kelas = Kelas::create(['nama_kelas' => 'X-1', 'jurusan_id' => $jurusan->id, 'tingkat' => '10', 'is_active' => true]);
        $mapel = Mapel::create(['nama_mapel' => 'Matematika', 'kode_mapel' => 'MTK', 'jurusan_id' => $jurusan->id, 'is_umum' => true, 'is_active' => true]);

        $guruUser = User::create(['name' => 'Guru Test', 'email' => 'guru@test.local', 'username' => 'guru1', 'password' => bcrypt('secret'), 'role' => 'guru', 'is_active' => true]);
        $guru = Guru::create(['nama' => 'Guru Test', 'user_id' => $guruUser->id]);

        $siswaUser = User::create(['name' => 'Siswa Test', 'email' => 'siswa@test.local', 'username' => 'siswa1', 'password' => bcrypt('secret'), 'role' => 'siswa', 'is_active' => true]);
        $siswa = Siswa::create(['nis' => '1001', 'nama' => 'Siswa Test', 'kelas_id' => $kelas->id, 'user_id' => $siswaUser->id]);

        // Soal PG teks lama - tidak ada gambar_soal sama sekali (kolom nullable, seperti data existing sebelum fitur ini).
        $bankSoal = BankSoal::create([
            'mapel_id' => $mapel->id,
            'guru_id' => $guru->id,
            'tipe_soal' => 'pg',
            'bobot_nilai' => 1,
            'pertanyaan' => 'Berapakah hasil dari 2 + 2?',
            'status' => 'aktif',
        ]);

        OpsiJawaban::create(['bank_soal_id' => $bankSoal->id, 'opsi_label' => 'A', 'isi_opsi' => '3', 'is_correct' => false]);
        OpsiJawaban::create(['bank_soal_id' => $bankSoal->id, 'opsi_label' => 'B', 'isi_opsi' => '4', 'is_correct' => true]);

        $ujian = Ujian::create([
            'nama_ujian' => 'Ulangan Harian MTK',
            'jenis_ujian' => 'harian',
            'mapel_id' => $mapel->id,
            'guru_id' => $guru->id,
            'durasi_menit' => 60,
            'tanggal_mulai' => now()->subHour(),
            'tanggal_selesai' => now()->addHour(),
            'metode_soal' => 'manual',
            'acak_opsi' => false,
            'jumlah_soal' => 1,
            'status' => 'publish',
        ]);
        $ujian->bankSoals()->attach($bankSoal->id, ['nomor_urut' => 1]);

        $peserta = PesertaUjian::create([
            'ujian_id' => $ujian->id,
            'siswa_id' => $siswa->id,
            'status' => 'sedang',
            'waktu_mulai' => now(),
            'soal_order' => json_encode([$bankSoal->id]),
        ]);

        $response = $this->actingAs($siswaUser)->get(route('exam.mengerjakan', $ujian->id));

        $response->assertOk();
        $response->assertSee('Berapakah hasil dari 2 + 2?');
        $response->assertSee('4');
    }
}
