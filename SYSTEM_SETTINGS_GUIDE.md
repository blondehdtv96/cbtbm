# 🎨 Panduan System Settings Management

Fitur System Settings memungkinkan admin untuk mengubah pengaturan sistem seperti logo, nama aplikasi, warna tema, dan konfigurasi lainnya melalui interface web tanpa perlu edit kode.

---

## 📋 Fitur yang Tersedia

### 1. Pengaturan Umum (General)
- ✅ Nama Aplikasi
- ✅ Nama Sekolah
- ✅ Alamat Sekolah
- ✅ Telepon Sekolah
- ✅ Email Sekolah
- ✅ Website Sekolah

### 2. Tampilan (Appearance)
- ✅ Logo Sekolah (upload gambar)
- ✅ Logo Kecil/Favicon
- ✅ Warna Utama (Primary Color)
- ✅ Warna Sekunder (Secondary Color)
- ✅ Background Login (upload gambar)
- ✅ Teks Footer
- ✅ Tampilkan "Powered by"

### 3. Pengaturan Ujian (Exam)
- ✅ Durasi Ujian Default (menit)
- ✅ Auto Submit (aktif/nonaktif)
- ✅ Tampilkan Hasil Langsung
- ✅ Anti-Cheat (aktif/nonaktif)
- ✅ Maksimal Pindah Tab

### 4. Email
- ✅ Nama Pengirim Email
- ✅ Email Pengirim

---

## 🚀 Instalasi

### Step 1: Run Migration

```bash
php artisan migrate
```

Migration akan membuat tabel `system_settings` dan mengisi dengan nilai default.

### Step 2: Update Composer Autoload

```bash
composer dump-autoload
```

Ini akan me-load helper functions untuk mengakses settings.

### Step 3: Clear Cache

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

---

## 📖 Cara Menggunakan

### Akses Halaman Settings

**Untuk Superadmin:**
1. Login sebagai Superadmin
2. Buka menu **Pengaturan Sistem** atau akses URL: `/superadmin/settings`
3. Pilih tab sesuai kategori pengaturan
4. Edit nilai yang ingin diubah
5. Klik **Simpan Pengaturan**

**Untuk Admin:**
1. Login sebagai Admin
2. Buka menu **Pengaturan Sistem** atau akses URL: `/admin/settings`
3. Pilih tab sesuai kategori pengaturan
4. Edit nilai yang ingin diubah
5. Klik **Simpan Pengaturan**

> **Note:** Baik Superadmin maupun Admin memiliki akses penuh ke semua pengaturan sistem.

### Upload Logo/Gambar

1. Klik tab **Tampilan**
2. Pada field **Logo Sekolah**, klik **Choose File**
3. Pilih gambar (max 2MB, format: JPG, PNG, GIF)
4. Klik **Simpan Pengaturan**
5. Logo akan langsung muncul di seluruh sistem

### Ubah Warna Tema

1. Klik tab **Tampilan**
2. Klik color picker pada **Warna Utama** atau **Warna Sekunder**
3. Pilih warna yang diinginkan
4. Klik **Simpan Pengaturan**
5. Warna akan diterapkan di seluruh sistem

### Reset ke Default

1. Klik tombol **Reset Default** di pojok kanan atas
2. Konfirmasi reset
3. Semua pengaturan akan kembali ke nilai default

### Clear Cache

1. Klik tombol **Clear Cache** di pojok kanan atas
2. Cache akan dibersihkan
3. Perubahan akan langsung terlihat

---

## 💻 Penggunaan di Kode

### Mengakses Setting

```php
// Menggunakan helper function
$appName = setting('app_name');
$schoolName = setting('school_name');
$logo = setting('logo');

// Dengan default value
$duration = setting('default_exam_duration', 90);

// Helper khusus
$appName = app_name();
$schoolName = school_name();
$logoUrl = school_logo();
$primaryColor = primary_color();
```

### Di Blade Template

```blade
<!-- Nama aplikasi -->
<h1>{{ app_name() }}</h1>

<!-- Logo -->
@if(school_logo())
    <img src="{{ school_logo() }}" alt="Logo">
@endif

<!-- Warna tema -->
<style>
    :root {
        --primary-color: {{ primary_color() }};
        --secondary-color: {{ secondary_color() }};
    }
</style>

<!-- Setting lainnya -->
<p>{{ setting('school_address') }}</p>
<p>{{ setting('school_phone') }}</p>
```

### Di Controller

```php
use App\Models\SystemSetting;

class ExamController extends Controller
{
    public function index()
    {
        // Get single setting
        $duration = SystemSetting::get('default_exam_duration', 90);
        
        // Get all settings
        $settings = SystemSetting::getAll();
        
        // Get by group
        $examSettings = SystemSetting::getByGroup('exam');
        
        return view('exam.index', compact('duration', 'settings'));
    }
}
```

### Set Setting Programmatically

```php
use App\Models\SystemSetting;

// Set single setting
SystemSetting::set('app_name', 'CBT SMK Baru');

// Set multiple settings
SystemSetting::set('school_name', 'SMK Negeri 2');
SystemSetting::set('school_address', 'Jl. Baru No. 123');

// Clear cache after update
SystemSetting::clearCache();
```

---

## 🎨 Customization

### Menambah Setting Baru

1. **Via Migration:**

```php
DB::table('system_settings')->insert([
    'key' => 'new_setting',
    'value' => 'default value',
    'type' => 'text', // text, textarea, image, boolean, number, color
    'group' => 'general',
    'label' => 'New Setting',
    'description' => 'Description here',
    'order' => 10,
    'created_at' => now(),
    'updated_at' => now(),
]);
```

2. **Via Tinker:**

```bash
php artisan tinker

>>> SystemSetting::create([
    'key' => 'new_setting',
    'value' => 'default value',
    'type' => 'text',
    'group' => 'general',
    'label' => 'New Setting',
    'description' => 'Description',
    'order' => 10
]);
```

### Menambah Group Baru

Edit `SystemSettingController.php`:

```php
$groups = [
    'general' => 'Pengaturan Umum',
    'appearance' => 'Tampilan',
    'exam' => 'Ujian',
    'email' => 'Email',
    'new_group' => 'Group Baru', // Tambahkan ini
];
```

### Menambah Helper Function

Edit `app/Helpers/SettingHelper.php`:

```php
if (!function_exists('my_custom_setting')) {
    function my_custom_setting()
    {
        return setting('my_setting', 'default');
    }
}
```

---

## 🔧 API Reference

### SystemSetting Model

```php
// Get setting value
SystemSetting::get($key, $default = null)

// Set setting value
SystemSetting::set($key, $value)

// Get all settings
SystemSetting::getAll()

// Get settings by group
SystemSetting::getByGroup($group)

// Clear cache
SystemSetting::clearCache()

// Get typed value
$setting->getTypedValue()
```

### Helper Functions

```php
// General
setting($key, $default = null)
app_name()
school_name()

// Appearance
school_logo()
school_logo_small()
primary_color()
secondary_color()
```

---

## 📊 Database Structure

### Table: system_settings

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| key | string | Unique setting key |
| value | text | Setting value |
| type | string | Input type (text, textarea, image, boolean, number, color) |
| group | string | Setting group (general, appearance, exam, email) |
| label | string | Display label |
| description | text | Setting description |
| order | integer | Display order |
| created_at | timestamp | Created timestamp |
| updated_at | timestamp | Updated timestamp |

---

## 🎯 Best Practices

### 1. Caching

Settings di-cache otomatis untuk performa. Cache akan di-clear otomatis saat update.

```php
// Cache key format
"setting:{key}"           // Single setting
"settings:all"            // All settings
"settings:group:{group}"  // Group settings
```

### 2. Image Upload

- Max size: 2MB
- Format: JPG, PNG, GIF
- Stored in: `storage/app/public/settings/`
- Accessible via: `storage/settings/filename.jpg`

### 3. Validation

Tambahkan validasi di controller jika perlu:

```php
$request->validate([
    'app_name' => 'required|string|max:255',
    'school_email' => 'required|email',
    'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
]);
```

### 4. Security

- Hanya admin yang bisa akses settings
- File upload di-validate
- XSS protection di blade templates
- CSRF protection di forms

---

## 🐛 Troubleshooting

### Setting Tidak Muncul

```bash
# Clear cache
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Reload autoload
composer dump-autoload
```

### Logo Tidak Tampil

```bash
# Create storage link
php artisan storage:link

# Check permissions
chmod -R 775 storage
chown -R www-data:www-data storage
```

### Perubahan Tidak Terlihat

1. Clear browser cache (Ctrl + Shift + R)
2. Clear Laravel cache
3. Check di Incognito mode

### Error Upload Gambar

1. Check max upload size di `php.ini`:
   ```ini
   upload_max_filesize = 20M
   post_max_size = 20M
   ```

2. Check storage permissions:
   ```bash
   chmod -R 775 storage/app/public
   ```

---

## 📝 Examples

### Example 1: Custom Login Page

```blade
<!-- resources/views/auth/login.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Login - {{ app_name() }}</title>
    <style>
        body {
            background: url('{{ setting("login_background") ? asset("storage/" . setting("login_background")) : "" }}');
            background-size: cover;
        }
        .primary-btn {
            background: {{ primary_color() }};
        }
    </style>
</head>
<body>
    <div class="login-box">
        @if(school_logo())
            <img src="{{ school_logo() }}" alt="Logo">
        @endif
        <h1>{{ app_name() }}</h1>
        <p>{{ school_name() }}</p>
        <!-- Login form -->
    </div>
</body>
</html>
```

### Example 2: Dynamic Email Template

```php
// app/Mail/ExamNotification.php
use App\Models\SystemSetting;

class ExamNotification extends Mailable
{
    public function build()
    {
        return $this->from(
                setting('email_from_address'),
                setting('email_from_name')
            )
            ->subject('Notifikasi Ujian - ' . app_name())
            ->view('emails.exam-notification');
    }
}
```

### Example 3: Conditional Anti-Cheat

```javascript
// resources/views/exam/mengerjakan.blade.php
@if(setting('anti_cheat_enabled') == '1')
<script>
    const maxTabSwitch = {{ setting('max_tab_switch', 2) }};
    
    // Anti-cheat code here
</script>
@endif
```

---

## ✅ Checklist Implementasi

```
Setup:
[ ] Migration run
[ ] Composer autoload updated
[ ] Storage link created
[ ] Routes added
[ ] Permissions set

Testing:
[ ] Access settings page
[ ] Upload logo
[ ] Change colors
[ ] Update text settings
[ ] Test cache clearing
[ ] Test reset to default

Integration:
[ ] Update login page
[ ] Update header/footer
[ ] Update email templates
[ ] Update exam settings
[ ] Test all pages
```

---

## 🎉 Conclusion

Fitur System Settings Management memberikan fleksibilitas untuk:

✅ Customize tampilan tanpa edit kode
✅ Branding sekolah (logo, nama, warna)
✅ Konfigurasi ujian dinamis
✅ Email customization
✅ Easy maintenance

**Sistem siap untuk di-customize sesuai kebutuhan sekolah! 🚀**

---

*Last Updated: 2024*
*Version: 1.0*
