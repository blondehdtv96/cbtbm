# 🚀 Instalasi System Settings

## Langkah-Langkah Instalasi

### Step 1: Run Migration

```bash
php artisan migrate
```

Output yang diharapkan:
```
Migrating: 2024_01_02_000001_create_system_settings_table
Migrated:  2024_01_02_000001_create_system_settings_table (XX.XXms)
```

### Step 2: Update Composer Autoload

```bash
composer dump-autoload
```

Output yang diharapkan:
```
Generating optimized autoload files
Generated optimized autoload files containing XXXX classes
```

### Step 3: Clear Cache

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

### Step 4: Create Storage Link (jika belum)

```bash
php artisan storage:link
```

### Step 5: Set Permissions (Linux/Mac)

```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache
```

### Step 6: Test Akses

**Untuk Superadmin:**
```
1. Login sebagai superadmin
2. Lihat sidebar, harus ada menu "Pengaturan Sistem" di bagian SISTEM
3. Klik menu tersebut
4. URL: http://127.0.0.1:8000/superadmin/settings
```

**Untuk Admin:**
```
1. Login sebagai admin
2. Lihat sidebar, harus ada menu "Pengaturan Sistem" di bagian SISTEM
3. Klik menu tersebut
4. URL: http://127.0.0.1:8000/admin/settings
```

---

## Troubleshooting

### Issue 1: Menu Tidak Muncul

**Solusi:**
```bash
# Clear cache
php artisan view:clear
php artisan cache:clear

# Hard refresh browser
Ctrl + Shift + R (Windows/Linux)
Cmd + Shift + R (Mac)

# Atau buka di Incognito mode
Ctrl + Shift + N
```

### Issue 2: Migration Error

**Error:** `Table 'system_settings' already exists`

**Solusi:**
```bash
# Drop table jika sudah ada
php artisan tinker
>>> Schema::dropIfExists('system_settings');
>>> exit

# Run migration lagi
php artisan migrate
```

### Issue 3: Route Not Found

**Error:** `Route [superadmin.settings.index] not defined`

**Solusi:**
```bash
# Clear route cache
php artisan route:clear

# Check routes
php artisan route:list | grep settings

# Expected output:
# GET|HEAD  superadmin/settings .... superadmin.settings.index
# GET|HEAD  admin/settings ......... admin.settings.index
```

### Issue 4: 403 Forbidden

**Cause:** User tidak memiliki role yang tepat

**Solusi:**
```sql
-- Check user role
SELECT id, name, email, role FROM users WHERE email = 'your@email.com';

-- Update role jika perlu
UPDATE users SET role = 'superadmin' WHERE email = 'your@email.com';
-- atau
UPDATE users SET role = 'admin' WHERE email = 'your@email.com';
```

### Issue 5: Helper Function Not Found

**Error:** `Call to undefined function setting()`

**Solusi:**
```bash
# Update composer autoload
composer dump-autoload

# Clear cache
php artisan config:clear
php artisan cache:clear

# Restart PHP-FPM (jika menggunakan)
sudo systemctl restart php8.1-fpm
```

### Issue 6: Upload Gambar Gagal

**Error:** `The logo failed to upload`

**Solusi:**
```bash
# 1. Check storage link
php artisan storage:link

# 2. Check permissions
chmod -R 775 storage/app/public
chown -R www-data:www-data storage/app/public

# 3. Check PHP upload limits
php -i | grep upload_max_filesize
php -i | grep post_max_size

# Edit php.ini jika perlu:
upload_max_filesize = 20M
post_max_size = 20M

# Restart PHP-FPM
sudo systemctl restart php8.1-fpm
```

---

## Verifikasi Instalasi

### Checklist:

```
[ ] Migration berhasil dijalankan
[ ] Composer autoload updated
[ ] Cache cleared
[ ] Storage link created
[ ] Permissions set (Linux/Mac)
[ ] Menu "Pengaturan Sistem" muncul di sidebar
[ ] Bisa akses halaman settings
[ ] Bisa edit dan save settings
[ ] Bisa upload gambar
[ ] Helper functions berfungsi
```

### Test Helper Functions:

```bash
php artisan tinker

# Test setting()
>>> setting('app_name')
=> "CBT SMK"

>>> setting('school_name')
=> "SMK Negeri 1"

# Test app_name()
>>> app_name()
=> "CBT SMK"

# Test school_name()
>>> school_name()
=> "SMK Negeri 1"

# Exit
>>> exit
```

### Test di Browser:

1. **Login sebagai Superadmin/Admin**
2. **Klik menu "Pengaturan Sistem"**
3. **Edit beberapa setting:**
   - Ubah "Nama Aplikasi" menjadi "CBT Test"
   - Klik "Simpan Pengaturan"
4. **Refresh halaman**
5. **Check apakah perubahan tersimpan**

---

## Default Settings

Setelah migration, sistem akan memiliki settings default berikut:

### General:
- app_name: "CBT SMK"
- school_name: "SMK Negeri 1"
- school_address: "Jl. Pendidikan No. 1"
- school_phone: "021-12345678"
- school_email: "info@smkn1.sch.id"
- school_website: "https://smkn1.sch.id"

### Appearance:
- logo: null (belum ada)
- logo_small: null (belum ada)
- primary_color: "#4f46e5"
- secondary_color: "#7c3aed"
- login_background: null (belum ada)
- footer_text: "© 2024 CBT SMK. All rights reserved."
- show_powered_by: true

### Exam:
- default_exam_duration: 90 (menit)
- auto_submit_enabled: true
- show_result_immediately: true
- anti_cheat_enabled: true
- max_tab_switch: 2

### Email:
- email_from_name: "CBT SMK"
- email_from_address: "noreply@cbt.sch.id"

---

## Next Steps

Setelah instalasi berhasil:

1. ✅ Customize settings sesuai kebutuhan sekolah
2. ✅ Upload logo sekolah
3. ✅ Ubah warna tema jika perlu
4. ✅ Test semua fitur
5. ✅ Backup database

---

## Support

Jika mengalami masalah:

1. Check logs: `tail -f storage/logs/laravel.log`
2. Check error di browser console (F12)
3. Baca dokumentasi: `SYSTEM_SETTINGS_GUIDE.md`
4. Baca access control: `SYSTEM_SETTINGS_ACCESS.md`

---

**Instalasi selesai! Menu "Pengaturan Sistem" sekarang tersedia di sidebar untuk Superadmin dan Admin.** 🎉
