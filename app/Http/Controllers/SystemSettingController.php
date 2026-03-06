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
        try {
            foreach ($request->except(['_token', '_method']) as $key => $value) {
                $setting = SystemSetting::where('key', $key)->first();
                
                if (!$setting) {
                    continue;
                }

                // Handle file uploads
                if ($setting->type === 'image' && $request->hasFile($key)) {
                    $file = $request->file($key);
                    
                    // Validate image
                    $request->validate([
                        $key => 'image|mimes:jpeg,png,jpg,gif|max:2048'
                    ]);

                    // Delete old image
                    if ($setting->value && Storage::disk('public')->exists($setting->value)) {
                        Storage::disk('public')->delete($setting->value);
                    }

                    // Store new image
                    $path = $file->store('settings', 'public');
                    $value = $path;
                }
                // Handle boolean
                elseif ($setting->type === 'boolean') {
                    $value = $request->has($key) ? '1' : '0';
                }

                // Update setting
                SystemSetting::set($key, $value);
            }

            // Clear cache
            SystemSetting::clearCache();
            Artisan::call('config:clear');
            Artisan::call('cache:clear');

            return redirect()->back()->with('success', 'Pengaturan berhasil disimpan!');

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

            // Delete file
            if ($setting->value && Storage::disk('public')->exists($setting->value)) {
                Storage::disk('public')->delete($setting->value);
            }

            // Update setting
            SystemSetting::set($key, null);

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
            // Run migration fresh (will reset to default values)
            Artisan::call('migrate:refresh', [
                '--path' => 'database/migrations/2024_01_02_000001_create_system_settings_table.php',
                '--force' => true
            ]);

            SystemSetting::clearCache();

            return redirect()->back()->with('success', 'Pengaturan berhasil direset ke default!');

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
