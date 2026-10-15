<?php

namespace Tests\Feature;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\RunsSiswaImport;
use Tests\TestCase;

/**
 * Akun siswa hasil import (dibuat lewat insert massal, melewati cast/event
 * model) harus bisa langsung dipakai login sampai dashboard terbuka — baik
 * password manual dari file maupun password yang digenerate sistem.
 */
class ImportedSiswaCanLoginTest extends TestCase
{
    use RefreshDatabase;
    use RunsSiswaImport;

    private function adminUser(): User
    {
        return User::create([
            'name' => 'Admin Login Import',
            'email' => 'admin.loginimport@test.local',
            'username' => 'adminloginimport',
            'password' => bcrypt('secret'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_siswa_hasil_import_bisa_login_dan_membuka_dashboard(): void
    {
        $jurusan = Jurusan::create(['nama_jurusan' => 'TKJ', 'kode_jurusan' => 'TKJ', 'is_active' => true]);
        Kelas::create(['nama_kelas' => '10 TKJ', 'jurusan_id' => $jurusan->id, 'is_active' => true]);

        $admin = $this->adminUser();

        $path = $this->makeImportXlsx([
            [1, '0032000001', '20242001', 'Siswa Import Manual', '10 TKJ', 'rahasia123'],
            [2, '0032000002', '20242002', 'Siswa Import Otomatis', '10 TKJ', ''],
        ]);

        $this->previewImport($admin, $path);
        $this->runImportToCompletion($admin);

        $credentials = $this->actingAs($admin)
            ->get(route('admin.import-siswa.result'))
            ->assertOk()
            ->viewData('credentials');

        foreach ($credentials as $cred) {
            // Sesi admin dilepas supaya login benar-benar dari kondisi tamu.
            $this->flushSession();
            auth()->logout();

            $response = $this->post(route('login.process'), [
                'nis' => $cred['nis'],
                'password' => $cred['password'],
            ]);

            $response->assertRedirect(route('siswa.dashboard'));

            $user = Siswa::where('nis', $cred['nis'])->firstOrFail()->user;
            $this->assertAuthenticatedAs($user);

            // Dashboard siswa baru (belum pernah menyetujui peraturan) harus
            // terbuka normal, bukan 500.
            $this->get(route('siswa.dashboard'))->assertOk();

            $this->assertNull($user->student_rules_ack_version);
        }

        @unlink($path);
    }
}
