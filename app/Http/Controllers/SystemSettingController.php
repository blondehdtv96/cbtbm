<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Services\StudentRulesSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;

class SystemSettingController extends Controller
{
    public function __construct(protected StudentRulesSettings $studentRules)
    {
    }

    /**
     * Display settings page
     */
    public function index()
    {
        // Form-nya dirender dari baris system_settings yang ada, jadi grup yang
        // kosong tampil tanpa satu pun field — admin melihat "Peraturan Siswa"
        // tanpa kolom dan tidak punya cara membuatnya. Itu terjadi di server
        // yang belum menjalankan migrasi 2026_09_13_000000. Barisnya dipastikan
        // ada di sini supaya halamannya selalu bisa dipakai.
        $this->studentRules->ensureExists();

        $groups = [
            'general' => 'Pengaturan Umum',
            'appearance' => 'Tampilan',
            'exam' => 'Ujian',
            'student' => 'Peraturan Siswa',
            'email' => 'Email',
        ];

        $settings = [];
        foreach ($groups as $key => $label) {
            $settings[$key] = SystemSetting::getByGroup($key);
        }

        // Peringatan untuk admin kalau kolom persetujuan belum ada: peraturannya
        // tetap bisa disunting dan tampil ke siswa, tapi persetujuan siswa tidak
        // tersimpan sehingga popupnya muncul lagi setiap login.
        $rulesAckPersists = $this->studentRules->canPersistAcknowledgement();

        return view('admin.settings.index', compact('settings', 'groups', 'rulesAckPersists'));
    }

    /**
     * Update settings
     */
    public function update(Request $request)
    {
        // Barisnya dipastikan ada lebih dulu: perulangan penyimpanan di bawah
        // berjalan atas SystemSetting::all(), jadi kunci yang belum punya baris
        // akan dilewati begitu saja — nilainya terkirim dari form tapi tidak
        // pernah tersimpan.
        $this->studentRules->ensureExists();

        $currentRulesVersion = max(1, (int) SystemSetting::get('student_rules_version', 1));

        // Aturan peraturan siswa hanya divalidasi kalau field-nya ikut terkirim.
        // Halaman lama yang masih ter-cache di browser admin (atau form yang
        // dirender sebelum barisnya ada) tidak memuat field ini; memaksanya
        // 'required' membuat SELURUH pengaturan gagal disimpan dengan pesan
        // validasi tentang peraturan siswa yang field-nya tidak kelihatan.
        if ($request->hasAny(['student_rules_title', 'student_rules_content', 'student_rules_version'])) {
            $rulesChanged = trim((string) $request->input('student_rules_title'))
                    !== trim((string) SystemSetting::get('student_rules_title', ''))
                || trim((string) $request->input('student_rules_content'))
                    !== trim((string) SystemSetting::get('student_rules_content', ''));
            $requestedRulesVersion = (int) $request->input('student_rules_version', $currentRulesVersion);
            $versionRaisedAutomatically = $rulesChanged && $requestedRulesVersion <= $currentRulesVersion;

            if ($versionRaisedAutomatically) {
                $request->merge(['student_rules_version' => $currentRulesVersion + 1]);
            }

            $request->validate([
                'student_rules_title' => ['required', 'string', 'max:150'],
                'student_rules_content' => ['required', 'string', 'min:100', 'max:20000'],
                'student_rules_version' => ['required', 'integer', 'min:' . $currentRulesVersion, 'max:999999'],
            ], [
                'student_rules_content.min' => 'Isi peraturan siswa minimal 100 karakter.',
                'student_rules_version.min' => 'Versi peraturan tidak boleh lebih rendah dari versi aktif.',
            ]);
        } else {
            $versionRaisedAutomatically = false;
        }

        try {
            foreach (SystemSetting::all() as $setting) {
                $key = $setting->key;

                if ($setting->type === 'image') {
                    if (!$request->hasFile($key)) {
                        continue;
                    }

                    $request->validate([
                        $key => 'image|mimes:jpeg,png,jpg,gif|max:2048',
                    ]);
                    $file = $request->file($key);

                    if ($setting->value && Storage::disk('public')->exists($setting->value)) {
                        Storage::disk('public')->delete($setting->value);
                    }

                    $value = $file->store('settings', 'public');
                } elseif ($setting->type === 'boolean') {
                    $value = $request->has($key) ? '1' : '0';
                } elseif ($request->has($key)) {
                    $value = $request->input($key);
                } else {
                    continue;
                }

                SystemSetting::set($key, $value);
            }

            SystemSetting::clearCache();
            Artisan::call('config:clear');
            Artisan::call('cache:clear');

            $message = $versionRaisedAutomatically
                ? 'Pengaturan berhasil disimpan. Versi peraturan siswa dinaikkan otomatis agar siswa menyetujui ulang.'
                : 'Pengaturan berhasil disimpan!';

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menyimpan pengaturan: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Delete uploaded image
     */
    public function deleteImage(Request $request)
    {
        try {
            $key = $request->input('key');
            $setting = SystemSetting::where('key', $key)->first();

            if (!$setting || $setting->type !== 'image') {
                return response()->json(['error' => 'Setting tidak ditemukan'], 404);
            }

            if ($setting->value && Storage::disk('public')->exists($setting->value)) {
                Storage::disk('public')->delete($setting->value);
            }

            SystemSetting::set($key, null);
            SystemSetting::clearCache();

            return response()->json(['success' => true, 'message' => 'Gambar berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Reset to default settings
     */
    public function reset()
    {
        try {
            $nextRulesVersion = max(1, (int) SystemSetting::get('student_rules_version', 1)) + 1;

            Artisan::call('migrate:refresh', [
                '--path' => 'database/migrations/2024_01_02_000001_create_system_settings_table.php',
                '--force' => true,
            ]);

            $this->studentRules->restoreDefaults($nextRulesVersion);
            SystemSetting::clearCache();

            return redirect()->back()->with(
                'success',
                'Pengaturan berhasil direset. Siswa akan diminta menyetujui kembali peraturan default.'
            );
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal reset pengaturan: ' . $e->getMessage());
        }
    }

    /**
     * Clear cache
     */
    public function clearCache()
    {
        try {
            SystemSetting::clearCache();
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('view:clear');

            return redirect()->back()->with('success', 'Cache berhasil dibersihkan!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membersihkan cache: ' . $e->getMessage());
        }
    }
}
