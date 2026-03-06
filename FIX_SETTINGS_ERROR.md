# 🔧 Fix System Settings Error

Jika mengalami error saat mengakses `/superadmin/settings`, ikuti langkah berikut:

## 🚀 Quick Fix (Otomatis)

```bash
# Run fix script
php fix-settings.php
```

Script ini akan otomatis:
- ✅ Check database connection
- ✅ Check & create table jika belum ada
- ✅ Insert default settings
- ✅ Create helper file
- ✅ Update composer autoload
- ✅ Clear all cache

## 🔧 Manual Fix

Jika script otomatis gagal, lakukan manual:

### Step 1: Run Migration

```bash
php artisan migrate
```

Jika error "table already exists":
```bash
php artisan migrate:fresh
# PERINGATAN: Ini akan menghapus semua data!
# Atau drop table manual:
php artisan tinker
>>> Schema::dropIfExists('system_settings');
>>> exit
php artisan migrate
```

### Step 2: Check Table

```bash
php artisan tinker
>>> DB::table('system_settings')->count()
# Harus return angka > 0
>>> exit
```

Jika return 0 (kosong), insert manual:
```bash
php artisan tinker
>>> DB::table('system_settings')->insert([
    'key' => 'app_name',
    'value' => 'CBT SMK',
    'type' => 'text',
    'group' => 'general',
    'label' => 'Nama Aplikasi',
    'description' => 'Nama aplikasi',
    'order' => 1,
    'created_at' => now(),
    'updated_at' => now()
]);
>>> exit
```

### Step 3: Update Autoload

```bash
composer dump-autoload
```

### Step 4: Clear Cache

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

### Step 5: Check Routes

```bash
php artisan route:list | grep settings
```

Expected output:
```
GET|HEAD  superadmin/settings .... superadmin.settings.index
PUT       superadmin/settings .... superadmin.settings.update
GET|HEAD  admin/settings ......... admin.settings.index
PUT       admin/settings ......... admin.settings.update
```

### Step 6: Test Access

```bash
# Test di browser
http://127.0.0.1:8000/superadmin/settings
```

## 🐛 Common Errors & Solutions

### Error 1: "Class 'App\Models\SystemSetting' not found"

**Solution:**
```bash
composer dump-autoload
php artisan config:clear
```

### Error 2: "Table 'cbt_smk.system_settings' doesn't exist"

**Solution:**
```bash
php artisan migrate
```

### Error 3: "Call to undefined function setting()"

**Solution:**
```bash
# Check if helper file exists
ls -la app/Helpers/SettingHelper.php

# If not exists, create it
mkdir -p app/Helpers
# Copy content from SYSTEM_SETTINGS_GUIDE.md

# Update composer.json autoload section
composer dump-autoload
```

### Error 4: "Route [superadmin.settings.index] not defined"

**Solution:**
```bash
php artisan route:clear
php artisan route:cache
```

### Error 5: "403 Forbidden"

**Solution:**
```sql
-- Check user role
SELECT id, name, email, role FROM users WHERE id = YOUR_USER_ID;

-- Update to superadmin
UPDATE users SET role = 'superadmin' WHERE id = YOUR_USER_ID;
```

### Error 6: "500 Internal Server Error"

**Solution:**
```bash
# Check logs
tail -f storage/logs/laravel.log

# Common causes:
# 1. Permission issues
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 2. Cache issues
php artisan optimize:clear

# 3. Autoload issues
composer dump-autoload -o
```

## 📋 Verification Checklist

```
[ ] Migration berhasil (table system_settings ada)
[ ] Table berisi data (minimal 18 records)
[ ] Helper file ada (app/Helpers/SettingHelper.php)
[ ] Composer autoload updated
[ ] Cache cleared
[ ] Routes terdaftar
[ ] Bisa akses /superadmin/settings
[ ] Bisa akses /admin/settings
[ ] Menu muncul di sidebar
```

## 🔍 Debug Mode

Jika masih error, aktifkan debug mode:

```env
# Edit .env
APP_DEBUG=true
APP_ENV=local
```

Kemudian akses lagi dan lihat error detail di browser.

## 📞 Get Help

Jika masih error setelah semua langkah:

1. **Check Laravel logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Check PHP errors:**
   ```bash
   tail -f /var/log/php8.1-fpm.log
   ```

3. **Check Nginx/Apache errors:**
   ```bash
   tail -f /var/log/nginx/error.log
   ```

4. **Screenshot error** dan kirim untuk analisis

---

## ✅ Success Indicators

Jika berhasil, Anda akan melihat:

1. ✅ Halaman settings terbuka tanpa error
2. ✅ Ada 4 tabs: General, Appearance, Exam, Email
3. ✅ Form fields terisi dengan nilai default
4. ✅ Bisa edit dan save settings
5. ✅ Bisa upload gambar

---

**Jalankan `php fix-settings.php` untuk fix otomatis!** 🚀
