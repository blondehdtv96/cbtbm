# Cara Mengubah Nama Aplikasi dan Pengaturan Sistem

## ✅ SUDAH DIPERBAIKI!

Fitur pengaturan sistem sudah diperbaiki dan sekarang berfungsi dengan baik. Nama aplikasi yang Anda ubah akan langsung diterapkan di seluruh sistem.

## Cara Mengubah Nama Aplikasi

### 1. Akses Halaman Pengaturan
Buka browser dan akses:
```
http://127.0.0.1:8000/superadmin/settings
```
atau
```
http://127.0.0.1:8000/admin/settings
```

### 2. Tab Pengaturan Umum
Di tab "Pengaturan Umum", Anda dapat mengubah:
- **Nama Aplikasi**: Nama yang ditampilkan di seluruh sistem
- **Tagline Aplikasi**: Slogan atau tagline aplikasi
- **Deskripsi Aplikasi**: Deskripsi lengkap aplikasi
- **Nama Sekolah**: Nama sekolah lengkap
- **Alamat Sekolah**: Alamat lengkap sekolah
- **Telepon Sekolah**: Nomor telepon sekolah
- **Email Sekolah**: Email resmi sekolah
- **Website Sekolah**: URL website sekolah

### 3. Simpan Perubahan
Klik tombol "Simpan Pengaturan" di bagian bawah form.

### 4. Refresh Halaman
Setelah menyimpan, refresh halaman browser (F5 atau Ctrl+R) untuk melihat perubahan.

## Dimana Nama Aplikasi Ditampilkan?

Nama aplikasi yang Anda ubah akan muncul di:

### 1. Sidebar
- Logo dan nama aplikasi di header sidebar
- Tagline di bawah nama aplikasi

### 2. Halaman Login
- Judul halaman (tab browser)
- Branding di sisi kiri halaman login
- Footer halaman login

### 3. Halaman Ujian
- Judul halaman saat siswa mengerjakan ujian
- Halaman hasil ujian

### 4. Halaman Anti-Cheat Violation
- Judul halaman pelanggaran

### 5. Cetak Kredensial
- Header pada print kredensial siswa
- Footer pada print kredensial
- Print hasil import siswa

## Pengaturan Lainnya

### Tab Tampilan
- **Logo Sekolah**: Upload logo yang ditampilkan di header
- **Logo Kecil**: Logo kecil untuk favicon
- **Warna Utama**: Warna tema utama aplikasi
- **Warna Sekunder**: Warna tema sekunder
- **Background Login**: Gambar background halaman login
- **Teks Footer**: Teks yang ditampilkan di footer
- **Tampilkan "Powered by"**: Tampilkan credit di footer

### Tab Ujian
- **Durasi Ujian Default**: Durasi default untuk ujian baru (menit)
- **Auto Submit**: Otomatis submit ujian saat waktu habis
- **Tampilkan Hasil Langsung**: Tampilkan hasil ujian setelah submit
- **Anti-Cheat**: Aktifkan sistem anti-cheat
- **Maksimal Pindah Tab**: Jumlah maksimal pindah tab sebelum auto-submit

### Tab Email
- **Nama Pengirim Email**: Nama yang muncul sebagai pengirim email
- **Email Pengirim**: Alamat email pengirim

## Status Saat Ini

✅ **Nama Aplikasi Saat Ini**: CBT SMK BINA MANDIRI
✅ **Nama Sekolah Saat Ini**: SMK Bina Mandiri Bekasi

Pengaturan ini sudah tersimpan dan akan diterapkan di seluruh sistem!

## Troubleshooting

### Perubahan Tidak Muncul?

1. **Clear Cache**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   ```

2. **Refresh Browser**
   - Tekan F5 atau Ctrl+R
   - Atau gunakan Hard Refresh: Ctrl+Shift+R

3. **Clear Browser Cache**
   - Buka Incognito/Private mode
   - Atau clear browser cache

### Error Saat Menyimpan?

Jalankan script perbaikan:
```bash
php fix-settings.php
```

## File Yang Dimodifikasi

Berikut file yang sudah diupdate untuk menggunakan nama aplikasi dinamis:

1. `resources/views/layouts/app.blade.php` - Layout utama
2. `resources/views/auth/login.blade.php` - Halaman login
3. `resources/views/exam/mengerjakan.blade.php` - Halaman ujian
4. `resources/views/exam/result.blade.php` - Halaman hasil
5. `resources/views/exam/anti-cheat-violation.blade.php` - Halaman pelanggaran
6. `resources/views/admin/siswa/credential.blade.php` - Print kredensial siswa
7. `resources/views/admin/users/credential.blade.php` - Print kredensial user
8. `resources/views/admin/import-siswa/result.blade.php` - Print hasil import

## Helper Functions

Anda dapat menggunakan helper functions ini di view Blade:

```php
{{ app_name() }}              // Nama aplikasi
{{ school_name() }}           // Nama sekolah
{{ school_logo() }}           // URL logo sekolah
{{ school_logo_small() }}     // URL logo kecil
{{ primary_color() }}         // Warna utama
{{ secondary_color() }}       // Warna sekunder
{{ setting('key', 'default') }} // Get setting by key
```

## Contoh Penggunaan

```blade
<title>{{ app_name() }} - Sistem Ujian Online</title>

<h1>Selamat Datang di {{ app_name() }}</h1>

<p>{{ setting('app_description') }}</p>

@if(school_logo())
    <img src="{{ school_logo() }}" alt="Logo">
@endif
```

## Kesimpulan

✅ Fitur pengaturan sistem sudah berfungsi dengan baik
✅ Nama aplikasi dapat diubah melalui halaman settings
✅ Perubahan langsung diterapkan di seluruh sistem
✅ Semua view sudah menggunakan helper functions dinamis

Selamat menggunakan sistem CBT yang sudah dipersonalisasi! 🎉
