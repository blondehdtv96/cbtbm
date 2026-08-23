<?php

namespace Tests\Feature;

use App\Models\BankSoal;
use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresi bug: sebelum perbaikan, memilih metode_soal="manual" menghasilkan
 * ujian dengan NOL soal terpasang karena tidak ada UI/logic pemilihan soal
 * manual sama sekali. Sekarang soal_ids dari picker harus benar-benar ter-attach,
 * sesuai urutan yang dikirim (jadi nomor_urut).
 */
class UjianManualSoalPickerTest extends TestCase
{
    use RefreshDatabase;

    public function test_metode_manual_attach_soal_sesuai_urutan_yang_dipilih(): void
    {
        $jurusan = Jurusan::create(['nama_jurusan' => 'IPA', 'kode_jurusan' => 'IPA', 'is_active' => true]);
        $kelas = Kelas::create(['nama_kelas' => 'X-1', 'jurusan_id' => $jurusan->id, 'tingkat' => '10', 'is_active' => true]);
        $mapel = Mapel::create(['nama_mapel' => 'Matematika', 'kode_mapel' => 'MTK', 'jurusan_id' => $jurusan->id, 'is_umum' => true, 'is_active' => true]);

        $adminUser = User::create(['name' => 'Admin Test', 'email' => 'admin@test.local', 'username' => 'admin1', 'password' => bcrypt('secret'), 'role' => 'admin', 'is_active' => true]);
        $guruUser = User::create(['name' => 'Guru Test', 'email' => 'guru@test.local', 'username' => 'guru1', 'password' => bcrypt('secret'), 'role' => 'guru', 'is_active' => true]);
        $guru = Guru::create(['nama' => 'Guru Test', 'user_id' => $guruUser->id]);

        $soal1 = BankSoal::create(['mapel_id' => $mapel->id, 'guru_id' => $guru->id, 'tipe_soal' => 'pg', 'bobot_nilai' => 1, 'pertanyaan' => 'Soal 1', 'status' => 'aktif']);
        $soal2 = BankSoal::create(['mapel_id' => $mapel->id, 'guru_id' => $guru->id, 'tipe_soal' => 'pg', 'bobot_nilai' => 1, 'pertanyaan' => 'Soal 2', 'status' => 'aktif']);

        // Sengaja dikirim terbalik (soal2 dulu) untuk memastikan nomor_urut ikut urutan submit, bukan urutan ID.
        $response = $this->actingAs($adminUser)->post(route('ujian.store'), [
            'nama_ujian' => 'Ulangan Manual',
            'jenis_ujian' => 'harian',
            'mapel_id' => $mapel->id,
            'guru_id' => $guru->id,
            'durasi_menit' => 60,
            'tanggal_mulai' => now()->format('Y-m-d\TH:i'),
            'tanggal_selesai' => now()->addHour()->format('Y-m-d\TH:i'),
            'metode_soal' => 'manual',
            'soal_ids' => [$soal2->id, $soal1->id],
            'kelas_ids' => [$kelas->id],
        ]);

        $response->assertRedirect(route('ujian.index'));

        $ujian = \App\Models\Ujian::where('nama_ujian', 'Ulangan Manual')->firstOrFail();

        $this->assertEquals(2, $ujian->jumlah_soal);
        $attached = $ujian->bankSoals()->orderBy('ujian_bank_soals.nomor_urut')->pluck('bank_soals.id')->toArray();
        $this->assertEquals([$soal2->id, $soal1->id], $attached);
    }
}
