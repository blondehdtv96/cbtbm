<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Ujian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresi bug: form edit ujian sebelumnya tidak punya field acak_opsi/
 * tampilkan_nilai/tampilkan_pembahasan, jadi tiap kali update() dipanggil,
 * $request->boolean(...) selalu bernilai false untuk ketiganya (field tidak
 * pernah terkirim) — nilai yang sudah di-set diam-diam ke-reset. Test ini
 * mengirim request update yang bentuknya sesuai form baru (field-field itu
 * ikut terkirim) dan memastikan nilainya tersimpan sesuai yang dikirim.
 */
class UjianEditPreservesBooleanSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_dengan_field_boolean_terkirim_tidak_mereset_nilainya(): void
    {
        $jurusan = Jurusan::create(['nama_jurusan' => 'IPA', 'kode_jurusan' => 'IPA', 'is_active' => true]);
        $kelas = Kelas::create(['nama_kelas' => 'X-1', 'jurusan_id' => $jurusan->id, 'tingkat' => '10', 'is_active' => true]);
        $mapel = Mapel::create(['nama_mapel' => 'Matematika', 'kode_mapel' => 'MTK', 'jurusan_id' => $jurusan->id, 'is_umum' => true, 'is_active' => true]);

        $adminUser = User::create(['name' => 'Admin Test', 'email' => 'admin@test.local', 'username' => 'admin1', 'password' => bcrypt('secret'), 'role' => 'admin', 'is_active' => true]);
        $guruUser = User::create(['name' => 'Guru Test', 'email' => 'guru@test.local', 'username' => 'guru1', 'password' => bcrypt('secret'), 'role' => 'guru', 'is_active' => true]);
        $guru = Guru::create(['nama' => 'Guru Test', 'user_id' => $guruUser->id]);

        $ujian = Ujian::create([
            'nama_ujian' => 'Ulangan Random',
            'jenis_ujian' => 'harian',
            'mapel_id' => $mapel->id,
            'guru_id' => $guru->id,
            'durasi_menit' => 60,
            'tanggal_mulai' => now(),
            'tanggal_selesai' => now()->addHour(),
            'metode_soal' => 'random',
            'acak_opsi' => false,
            'jumlah_soal' => 0,
            'status' => 'draft',
            'token' => 'ABCDE',
            'tampilkan_nilai' => false,
            'tampilkan_pembahasan' => false,
        ]);
        $ujian->kelasList()->attach($kelas->id);

        $response = $this->actingAs($adminUser)->put(route('ujian.update', $ujian), [
            'nama_ujian' => $ujian->nama_ujian,
            'jenis_ujian' => 'harian',
            'mapel_id' => $mapel->id,
            'guru_id' => $guru->id,
            'durasi_menit' => 60,
            'tanggal_mulai' => now()->format('Y-m-d\TH:i'),
            'tanggal_selesai' => now()->addHour()->format('Y-m-d\TH:i'),
            'metode_soal' => 'random',
            'jumlah_soal' => 5,
            // acak_ulang sengaja TIDAK dicentang -> soal existing (kosong) dibiarkan, tidak error.
            'acak_opsi' => '1',
            'tampilkan_nilai' => '1',
            'tampilkan_pembahasan' => '1',
            'kelas_ids' => [$kelas->id],
        ]);

        $response->assertRedirect(route('ujian.index'));

        $ujian->refresh();
        $this->assertTrue($ujian->acak_opsi);
        $this->assertTrue($ujian->tampilkan_nilai);
        $this->assertTrue($ujian->tampilkan_pembahasan);
    }
}
