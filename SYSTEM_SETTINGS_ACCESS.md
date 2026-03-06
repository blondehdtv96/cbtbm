# 🔐 System Settings - Access Control

## Akses Berdasarkan Role

### ✅ Superadmin
- **URL**: `/superadmin/settings`
- **Akses**: Full access ke semua pengaturan
- **Permissions**: 
  - View settings
  - Edit settings
  - Upload images
  - Delete images
  - Reset to default
  - Clear cache

### ✅ Admin
- **URL**: `/admin/settings`
- **Akses**: Full access ke semua pengaturan
- **Permissions**: 
  - View settings
  - Edit settings
  - Upload images
  - Delete images
  - Reset to default
  - Clear cache

### ❌ Guru
- **Akses**: Tidak ada akses ke system settings
- **Reason**: Settings bersifat global dan mempengaruhi seluruh sistem

### ❌ Siswa
- **Akses**: Tidak ada akses ke system settings
- **Reason**: Settings hanya untuk administrator

---

## Routes

### Superadmin Routes
```php
Route::middleware(['auth', 'role:superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/settings', [SystemSettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SystemSettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/delete-image', [SystemSettingController::class, 'deleteImage'])->name('settings.delete-image');
    Route::post('/settings/reset', [SystemSettingController::class, 'reset'])->name('settings.reset');
    Route::get('/settings/clear-cache', [SystemSettingController::class, 'clearCache'])->name('settings.clear-cache');
});
```

### Admin Routes
```php
Route::middleware(['auth', 'role:superadmin,admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/settings', [SystemSettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SystemSettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/delete-image', [SystemSettingController::class, 'deleteImage'])->name('settings.delete-image');
    Route::post('/settings/reset', [SystemSettingController::class, 'reset'])->name('settings.reset');
    Route::get('/settings/clear-cache', [SystemSettingController::class, 'clearCache'])->name('settings.clear-cache');
});
```

---

## Cara Akses

### Sebagai Superadmin:
```
1. Login dengan akun superadmin
2. Akses: http://yourdomain.com/superadmin/settings
3. Atau klik menu "Pengaturan Sistem" di sidebar
```

### Sebagai Admin:
```
1. Login dengan akun admin
2. Akses: http://yourdomain.com/admin/settings
3. Atau klik menu "Pengaturan Sistem" di sidebar
```

---

## Security

### Middleware Protection
- ✅ Authentication required (`auth`)
- ✅ Role-based access control (`role:superadmin` atau `role:superadmin,admin`)
- ✅ CSRF protection untuk semua POST/PUT requests
- ✅ File upload validation (max 2MB, image only)
- ✅ XSS protection di blade templates

### File Upload Security
```php
// Validation
$request->validate([
    'logo' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
]);

// Storage
$path = $file->store('settings', 'public');

// Access
asset('storage/' . $path)
```

---

## Testing Access

### Test Superadmin Access:
```bash
# Login sebagai superadmin
# Akses URL
curl -X GET http://localhost:8000/superadmin/settings \
  -H "Cookie: laravel_session=YOUR_SESSION"

# Expected: 200 OK
```

### Test Admin Access:
```bash
# Login sebagai admin
# Akses URL
curl -X GET http://localhost:8000/admin/settings \
  -H "Cookie: laravel_session=YOUR_SESSION"

# Expected: 200 OK
```

### Test Unauthorized Access:
```bash
# Login sebagai guru atau siswa
# Akses URL
curl -X GET http://localhost:8000/admin/settings \
  -H "Cookie: laravel_session=YOUR_SESSION"

# Expected: 403 Forbidden atau redirect
```

---

## Menu Navigation

### Tambahkan ke Sidebar (Superadmin)

```blade
<!-- resources/views/layouts/superadmin.blade.php -->
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('superadmin.settings.*') ? 'active' : '' }}" 
       href="{{ route('superadmin.settings.index') }}">
        <i class="bi bi-gear-fill"></i>
        <span>Pengaturan Sistem</span>
    </a>
</li>
```

### Tambahkan ke Sidebar (Admin)

```blade
<!-- resources/views/layouts/admin.blade.php -->
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" 
       href="{{ route('admin.settings.index') }}">
        <i class="bi bi-gear-fill"></i>
        <span>Pengaturan Sistem</span>
    </a>
</li>
```

---

## Permissions Matrix

| Feature | Superadmin | Admin | Guru | Siswa |
|---------|-----------|-------|------|-------|
| View Settings | ✅ | ✅ | ❌ | ❌ |
| Edit Settings | ✅ | ✅ | ❌ | ❌ |
| Upload Logo | ✅ | ✅ | ❌ | ❌ |
| Change Colors | ✅ | ✅ | ❌ | ❌ |
| Reset Default | ✅ | ✅ | ❌ | ❌ |
| Clear Cache | ✅ | ✅ | ❌ | ❌ |

---

## Best Practices

### 1. Audit Log
Tambahkan logging untuk perubahan settings:

```php
use App\Models\ActivityLog;

// Di SystemSettingController
public function update(Request $request)
{
    // ... update logic ...
    
    ActivityLog::log(
        'update_settings',
        'system',
        'Mengubah pengaturan sistem',
        ['changes' => $request->except(['_token', '_method'])]
    );
}
```

### 2. Backup Before Reset
Backup settings sebelum reset:

```php
public function reset()
{
    // Backup current settings
    $backup = SystemSetting::all()->toArray();
    Storage::put('backups/settings_' . now()->format('Y-m-d_H-i-s') . '.json', json_encode($backup));
    
    // Reset...
}
```

### 3. Notification
Kirim notifikasi saat settings diubah:

```php
// Notify all admins
$admins = User::where('role', 'admin')->orWhere('role', 'superadmin')->get();
Notification::send($admins, new SettingsUpdatedNotification($changes));
```

---

## Troubleshooting

### Issue: 403 Forbidden

**Cause**: User tidak memiliki role yang tepat

**Solution**:
```sql
-- Check user role
SELECT id, name, email, role FROM users WHERE email = 'your@email.com';

-- Update role jika perlu
UPDATE users SET role = 'superadmin' WHERE email = 'your@email.com';
```

### Issue: Route Not Found

**Cause**: Routes belum di-cache atau ada typo

**Solution**:
```bash
php artisan route:clear
php artisan route:cache
php artisan route:list | grep settings
```

### Issue: Middleware Error

**Cause**: Middleware tidak terdaftar

**Solution**:
```php
// Check app/Http/Kernel.php
protected $routeMiddleware = [
    'role' => \App\Http\Middleware\CheckRole::class,
    // ...
];
```

---

## Summary

✅ **Superadmin**: Full access via `/superadmin/settings`
✅ **Admin**: Full access via `/admin/settings`
❌ **Guru**: No access
❌ **Siswa**: No access

**Security**: Protected by authentication & role-based middleware
**Features**: Same features for both Superadmin & Admin
**Purpose**: Centralized system configuration management

---

*Last Updated: 2024*
*Version: 1.0*
