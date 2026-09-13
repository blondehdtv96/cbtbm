<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;

class SystemSettingController extends Controller
{
    /**
     * Display settings page
     */
    public function index()
    {
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

        return view('admin.settings.index', compact('settings', 'groups'));
    }

    /**
     * Update settings
     */
    public function update(Request $request)
    {
        $currentRulesVersion = max(1, (int) SystemSetting::get('student_rules_version', 1));
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

            $this->restoreStudentRuleSettings($nextRulesVersion);
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

    private function restoreStudentRuleSettings(int $version): void
    {
        $settings = [
            ['key' => 'student_rules_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'student', 'label' => 'Aktifkan Peraturan Siswa', 'description' => 'Wajibkan siswa membaca dan menyetujui peraturan setelah login.', 'order' => 1],
            ['key' => 'student_rules_title', 'value' => 'Peraturan Penggunaan CBT untuk Siswa', 'type' => 'text', 'group' => 'student', 'label' => 'Judul Popup Peraturan', 'description' => 'Judul yang ditampilkan pada popup peraturan siswa.', 'order' => 2],
            ['key' => 'student_rules_content', 'value' => $this->defaultStudentRulesContent(), 'type' => 'textarea', 'group' => 'student', 'label' => 'Isi Peraturan Siswa', 'description' => 'Isi peraturan wajib yang dibaca siswa. Konten ditampilkan sebagai teks aman.', 'order' => 3],
            ['key' => 'student_rules_version', 'value' => (string) $version, 'type' => 'number', 'group' => 'student', 'label' => 'Versi Peraturan', 'description' => 'Naikkan versi agar seluruh siswa diminta menyetujui kembali.', 'order' => 4],
        ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }

    private function defaultStudentRulesContent(): string
    {
        $path = base_path('PERATURAN_PENGGUNAAN_SISWA.md');
        if (!is_file($path)) {
            return 'Siswa wajib mengerjakan ujian secara jujur, menjaga kerahasiaan akun dan token, tidak membuka tab atau aplikasi lain, serta mengikuti arahan pengawas.';
        }

        $content = (string) file_get_contents($path);
        $content = preg_replace('/^#{1,6}\s+/m', '', $content);

        return trim(str_replace(['**', '---'], '', $content));
    }
}
