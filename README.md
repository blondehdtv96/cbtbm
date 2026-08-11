# 🎓 Sistem CBT (Computer Based Test) SMK

![Laravel](https://img.shields.io/badge/Laravel-10.x-red?style=flat-square&logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.1+-blue?style=flat-square&logo=php)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-orange?style=flat-square&logo=mysql)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)

Sistem Computer Based Test (CBT) berbasis web untuk Sekolah Menengah Kejuruan (SMK) yang dibangun dengan Laravel 10. Sistem ini menyediakan platform ujian online yang aman, modern, dan mudah digunakan dengan fitur anti-cheat yang canggih.

---

## 📋 Daftar Isi

- [Fitur Utama](#-fitur-utama)
- [Teknologi](#-teknologi)
- [Persyaratan Sistem](#-persyaratan-sistem)
- [Instalasi](#-instalasi)
- [Konfigurasi](#-konfigurasi)
- [Struktur Database](#-struktur-database)
- [Panduan Penggunaan](#-panduan-penggunaan)
- [Fitur Anti-Cheat](#-fitur-anti-cheat)
- [API Endpoints](#-api-endpoints)
- [Troubleshooting](#-troubleshooting)
- [Kontribusi](#-kontribusi)
- [Skalabilitas & Deployment Produksi](#-skalabilitas--deployment-produksi-500-concurrent-users)
- [Lisensi](#-lisensi)

---

## ✨ Fitur Utama

### 🔐 Multi-Role Authentication
- **Super Admin**: Akses penuh ke seluruh sistem
- **Admin**: Manajemen ujian, siswa, dan bank soal
- **Guru**: Membuat dan mengelola bank soal
- **Siswa**: Mengikuti ujian online

### 📚 Manajemen Bank Soal
- ✅ Tipe soal: Pilihan Ganda (PG), PG Kompleks, Essay
- ✅ Import soal dari Excel (bulk upload)
- ✅ Tingkat kesulitan: Mudah, Sedang, Sulit
- ✅ Bobot nilai per soal
- ✅ Pembahasan soal
- ✅ Gambar pendukung soal
- ✅ Kategori dan tag soal
- ✅ Statistik penggunaan soal

### 📝 Manajemen Ujian
- ✅ Penjadwalan ujian (tanggal & waktu mulai/selesai)
- ✅ Durasi ujian (menit)
- ✅ Token akses ujian (5 digit)
- ✅ Pengacakan soal (random/urut)
- ✅ Pengacakan opsi jawaban
- ✅ Tampilkan/sembunyikan nilai
- ✅ Tampilkan/sembunyikan pembahasan
- ✅ Assign ujian ke kelas tertentu
- ✅ Monitoring real-time peserta ujian
- ✅ Status peserta: Belum, Sedang, Selesai

### 👨‍🎓 Manajemen Siswa
- ✅ CRUD siswa manual
- ✅ Import siswa dari Excel (bulk)
- ✅ Generate username & password otomatis
- ✅ Download kredensial siswa (PDF)
- ✅ Cetak kartu peserta ujian
- ✅ Reset password siswa
- ✅ Aktivasi/deaktivasi akun

### 📊 Penilaian & Laporan
- ✅ Auto-grading untuk soal pilihan ganda
- ✅ Manual grading untuk essay
- ✅ Skala nilai 0-100
- ✅ Statistik hasil ujian
- ✅ Export nilai ke Excel/PDF
- ✅ Cetak daftar nilai
- ✅ Riwayat ujian siswa
- ✅ Analisis jawaban per soal

### 🛡️ Sistem Anti-Cheat
- ✅ Deteksi tab switching (Alt+Tab)
- ✅ Deteksi window blur
- ✅ Blokir right-click
- ✅ Blokir copy/paste
- ✅ Blokir text selection
- ✅ Blokir keyboard shortcuts (F12, Ctrl+Shift+I, dll)
- ✅ Deteksi DevTools
- ✅ Watermark nama siswa
- ✅ Warning system (2x peringatan)
- ✅ Auto-submit pada pelanggaran
- ✅ Activity logging
- ✅ Logout otomatis

### 🎨 User Interface
- ✅ Modern & responsive design
- ✅ Mobile-friendly
- ✅ Dark mode support
- ✅ Smooth animations
- ✅ Real-time timer
- ✅ Progress indicator
- ✅ Navigasi soal yang intuitif
- ✅ Tandai soal ragu-ragu
- ✅ Auto-save jawaban (AJAX)

### 📱 Fitur Tambahan
- ✅ Manajemen Jurusan
- ✅ Manajemen Kelas
- ✅ Manajemen Mata Pelajaran
- ✅ Manajemen Sesi Ujian
- ✅ Activity Log System
- ✅ Settings Management
- ✅ Backup & Restore

---

## 🛠️ Teknologi

### Backend
- **Framework**: Laravel 10.x
- **PHP**: 8.1+
- **Database**: MySQL 8.0+
- **Authentication**: Laravel Sanctum
- **Excel Processing**: PhpSpreadsheet 5.4

### Frontend
- **CSS Framework**: Bootstrap 5 + Custom CSS
- **Icons**: Bootstrap Icons
- **Fonts**: Inter, Poppins (Google Fonts)
- **JavaScript**: Vanilla JS (ES6+)
- **AJAX**: Fetch API

### Tools & Libraries
- **Composer**: Dependency management
- **NPM**: Frontend package management
- **Vite**: Asset bundling
- **Laravel Tinker**: REPL
- **Laravel Pint**: Code styling

---

## 💻 Persyaratan Sistem

### Minimum Requirements
```
- PHP >= 8.1
- MySQL >= 8.0 atau MariaDB >= 10.3
- Composer >= 2.0
- Node.js >= 16.x
- NPM >= 8.x
- Web Server (Apache/Nginx)
- PHP Extensions:
  - BCMath
  - Ctype
  - Fileinfo
  - JSON
  - Mbstring
  - OpenSSL
  - PDO
  - Tokenizer
  - XML
  - GD atau Imagick
  - Zip
```

### Recommended Requirements
```
- PHP 8.2+
- MySQL 8.0+
- 2GB RAM minimum
- 1GB disk space
- SSL Certificate (untuk production)
```

---

## 📦 Instalasi

### 1. Clone Repository
```bash
git clone https://github.com/username/cbt-smk.git
cd cbt-smk
```

### 2. Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

### 3. Environment Setup
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Database Configuration
Edit file `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cbt_smk
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 5. Database Migration & Seeding
```bash
# Create database
mysql -u root -p
CREATE DATABASE cbt_smk;
exit;

# Run migrations
php artisan migrate

# Seed initial data (optional)
php artisan db:seed
```

### 6. Storage Link
```bash
php artisan storage:link
```

### 7. Build Assets
```bash
# Development
npm run dev

# Production
npm run build
```

### 8. Run Application
```bash
# Development server
php artisan serve

# Access at: http://127.0.0.1:8000
```

---

## ⚙️ Konfigurasi

### Default Admin Account
Setelah seeding, gunakan kredensial berikut:
```
Email: admin@cbt.com
Password: password
```

### File Upload Configuration
Edit `config/filesystems.php` untuk konfigurasi storage:
```php
'public' => [
    'driver' => 'local',
    'root' => storage_path('app/public'),
    'url' => env('APP_URL').'/storage',
    'visibility' => 'public',
],
```

### Session Configuration
Edit `.env` untuk konfigurasi session:
```env
SESSION_DRIVER=file
SESSION_LIFETIME=120  # dalam menit
```

> Untuk production dengan banyak siswa mengerjakan ujian bersamaan, `SESSION_DRIVER=file` sebaiknya diganti `redis` — lihat [Skalabilitas & Deployment Produksi](#-skalabilitas--deployment-produksi-500-concurrent-users).

### Cache Configuration
```bash
# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🗄️ Struktur Database

### Tabel Utama

#### users
```sql
- id (PK)
- name
- email (unique)
- password
- role (enum: superadmin, admin, guru, siswa)
- is_active (boolean)
- timestamps
```

#### siswa
```sql
- id (PK)
- user_id (FK)
- nisn (unique)
- nis (unique)
- kelas_id (FK)
- jenis_kelamin
- tanggal_lahir
- alamat
- no_hp
- foto
- timestamps
```

#### bank_soals
```sql
- id (PK)
- mapel_id (FK)
- pertanyaan (text)
- tipe_soal (enum: pg, pg_kompleks, essay)
- bobot_nilai (decimal)
- gambar_soal
- digunakan_count
- timestamps
```

#### opsi_jawabans
```sql
- id (PK)
- bank_soal_id (FK)
- opsi_label (A, B, C, D, E)
- isi_opsi (text)
- is_correct (boolean)
- timestamps
```

#### ujians
```sql
- id (PK)
- nama_ujian
- mapel_id (FK)
- tanggal_mulai (datetime)
- tanggal_selesai
- durasi_menit
- jumlah_soal
- token (5 digit)
- metode_soal (enum: random, urut)
- acak_opsi (boolean)
- tampilkan_nilai (boolean)
- tampilkan_pembahasan (boolean)
- status (enum: draft, publish, berlangsung, selesai)
- timestamps
```

#### peserta_ujians
```sql
- id (PK)
- ujian_id (FK)
- siswa_id (FK)
- status (enum: belum, sedang, selesai)
- waktu_mulai
- waktu_selesai
- nilai (decimal)
- soal_order (json)
- timestamps
```

#### jawaban_siswas
```sql
- id (PK)
- peserta_ujian_id (FK)
- bank_soal_id (FK)
- jawaban_dipilih
- is_correct (boolean)
- is_ragu (boolean)
- nilai (decimal)
- timestamps
```

### Relasi Database
```
users (1) -> (1) siswa
users (1) -> (1) guru
kelas (1) -> (*) siswa
jurusan (1) -> (*) kelas
mapel (1) -> (*) bank_soals
mapel (1) -> (*) ujians
bank_soal (1) -> (*) opsi_jawabans
ujian (*) <-> (*) bank_soals (pivot)
ujian (*) <-> (*) kelas (pivot)
ujian (1) -> (*) peserta_ujians
siswa (1) -> (*) peserta_ujians
peserta_ujian (1) -> (*) jawaban_siswas
bank_soal (1) -> (*) jawaban_siswas
```

---

## 📖 Panduan Penggunaan

### Untuk Admin

#### 1. Membuat Ujian Baru
```
1. Login sebagai Admin
2. Menu "Ujian" → "Buat Ujian Baru"
3. Isi form:
   - Nama Ujian
   - Mata Pelajaran
   - Tanggal & Waktu
   - Durasi (menit)
   - Token (5 digit)
4. Pilih Bank Soal
5. Assign ke Kelas
6. Konfigurasi:
   - Acak Soal: Ya/Tidak
   - Acak Opsi: Ya/Tidak
   - Tampilkan Nilai: Ya/Tidak
   - Tampilkan Pembahasan: Ya/Tidak
7. Simpan & Publish
```

#### 2. Import Siswa dari Excel
```
1. Menu "Siswa" → "Import Siswa"
2. Download Template Excel
3. Isi data siswa di template
4. Upload file Excel
5. Preview data
6. Konfirmasi import
7. Download kredensial siswa (PDF)
```

#### 3. Monitoring Ujian
```
1. Menu "Ujian" → Pilih ujian
2. Klik "Monitoring"
3. Lihat status real-time:
   - Siswa yang sedang mengerjakan
   - Siswa yang sudah selesai
   - Progress per siswa
4. Refresh otomatis setiap 30 detik
```

#### 4. Melihat Hasil Ujian
```
1. Menu "Ujian" → Pilih ujian
2. Klik "Hasil"
3. Lihat daftar nilai siswa
4. Klik nama siswa untuk detail jawaban
5. Export ke Excel/PDF
6. Cetak daftar nilai
```

### Untuk Guru

#### 1. Membuat Bank Soal
```
1. Login sebagai Guru
2. Menu "Bank Soal" → "Tambah Soal"
3. Isi form:
   - Mata Pelajaran
   - Pertanyaan
   - Tipe Soal (PG/Essay)
   - Tingkat Kesulitan
   - Bobot Nilai
4. Untuk PG: Tambah opsi A, B, C, D, E
5. Tandai jawaban yang benar
6. Isi pembahasan (opsional)
7. Upload gambar (opsional)
8. Simpan
```

#### 2. Import Soal dari Excel
```
1. Menu "Bank Soal" → "Import Soal"
2. Download Template Excel
3. Isi soal di template
4. Upload file Excel
5. Preview data
6. Konfirmasi import
```

### Untuk Siswa

#### 1. Mengikuti Ujian
```
1. Login dengan username & password
2. Dashboard → Lihat ujian yang tersedia
3. Klik "Mulai Ujian"
4. Masukkan Token Ujian (5 digit)
5. Klik "Mulai"
6. Kerjakan soal:
   - Pilih jawaban
   - Tandai ragu-ragu (opsional)
   - Navigasi dengan tombol atau grid
7. Klik "Selesai" untuk submit
8. Konfirmasi submit
9. Lihat hasil (jika diizinkan)
```

#### 2. Tips Mengerjakan Ujian
```
✅ Pastikan koneksi internet stabil
✅ Gunakan browser modern (Chrome/Firefox/Edge)
✅ Jangan refresh halaman
✅ Jangan pindah tab/window (anti-cheat aktif)
✅ Jawaban tersimpan otomatis
✅ Perhatikan timer
✅ Tandai soal ragu-ragu untuk review
```

---

## 🛡️ Fitur Anti-Cheat

### Mekanisme Keamanan

#### 1. Tab Switch Detection
```javascript
// Deteksi perpindahan tab
- Warning 1: Alert peringatan
- Warning 2: Alert peringatan terakhir
- Warning 3: Overlay merah + auto-submit dalam 10 detik
```

#### 2. Pencegahan Aksi
```javascript
✅ Right-click disabled
✅ Copy/Paste disabled
✅ Text selection disabled
✅ F12 (DevTools) blocked
✅ Ctrl+Shift+I blocked
✅ Ctrl+U (View Source) blocked
✅ Ctrl+C/X/A blocked
```

#### 3. Watermark System
```javascript
// Nama siswa ditampilkan sebagai watermark
- Posisi: Diagonal di seluruh layar
- Opacity: 4%
- Tidak bisa dihapus
- Untuk identifikasi screenshot
```

#### 4. Activity Logging
```php
// Semua aktivitas dicatat:
- Login/Logout
- Mulai ujian
- Submit ujian
- Pelanggaran anti-cheat
- Tab switch count
- Timestamp lengkap
```

### Konfigurasi Anti-Cheat

#### Menonaktifkan untuk Debugging
Edit `resources/views/exam/mengerjakan.blade.php`:
```javascript
// Ubah baris ini:
console.log('Anti-cheat: ENABLED');

// Menjadi:
console.log('Anti-cheat: DISABLED for debugging');

// Dan comment semua kode anti-cheat
```

#### Mengaktifkan Kembali
```javascript
// Hapus comment pada kode anti-cheat
// Ubah console.log menjadi:
console.log('Anti-cheat: ENABLED');
```

---

## 🔌 API Endpoints

### Authentication
```http
POST   /login              # Login
POST   /logout             # Logout
```

### Exam (Siswa)
```http
GET    /exam/{ujian}/start              # Halaman token
POST   /exam/{ujian}/verify-token       # Verifikasi token
GET    /exam/{ujian}/mengerjakan        # Halaman ujian
POST   /exam/{ujian}/save-jawaban       # Save jawaban (AJAX)
POST   /exam/{ujian}/submit             # Submit ujian
GET    /exam/{ujian}/result             # Hasil ujian
POST   /exam/{ujian}/anti-cheat         # Log pelanggaran
```

### Admin Routes
```http
GET    /admin/dashboard                 # Dashboard admin
GET    /admin/siswa-manage              # Manajemen siswa
POST   /admin/import-siswa/import       # Import siswa
GET    /admin/kartu-peserta             # Kartu peserta
```

### Ujian Management
```http
GET    /ujian                           # List ujian
POST   /ujian                           # Create ujian
GET    /ujian/{ujian}/edit              # Edit ujian
PUT    /ujian/{ujian}                   # Update ujian
DELETE /ujian/{ujian}                   # Delete ujian
PATCH  /ujian/{ujian}/publish           # Publish ujian
GET    /ujian/{ujian}/hasil             # Hasil ujian
GET    /ujian/{ujian}/monitoring        # Monitoring
```

### Bank Soal
```http
GET    /banksoal                        # List soal
POST   /banksoal                        # Create soal
GET    /banksoal/{soal}/edit            # Edit soal
PUT    /banksoal/{soal}                 # Update soal
DELETE /banksoal/{soal}                 # Delete soal
POST   /banksoal/bulk-destroy           # Bulk delete
```

---

## 🐛 Troubleshooting

### Masalah Umum

#### 1. Jawaban Tidak Tersimpan (NULL)
**Gejala**: Setelah submit, nilai 0 dan jawaban NULL di database

**Solusi**:
```bash
# 1. Clear browser cache
Ctrl + Shift + Delete → Clear all

# 2. Test di Incognito mode
Ctrl + Shift + N

# 3. Cek console log (F12)
Harus ada: [SAVE] Response status: 200 OK

# 4. Cek database
SELECT * FROM jawaban_siswas 
WHERE peserta_ujian_id = [ID] 
ORDER BY id DESC;

# 5. Jalankan script perbaikan
php check_jawaban.php
```

#### 2. Error 404 pada Save Jawaban
**Gejala**: POST /cbtbm/public/exam/13/save-jawaban 404

**Penyebab**: APP_URL tidak sesuai dengan URL akses

**Solusi**:
```env
# Edit .env
APP_URL=http://127.0.0.1:8000

# Atau gunakan relative URL (sudah diperbaiki di v3.0)
```

#### 3. Error 419 Page Expired
**Gejala**: Error 419 saat submit atau save jawaban

**Penyebab**: CSRF token expired atau tidak valid

**Solusi**:
```bash
# 1. Clear cache
php artisan cache:clear
php artisan config:clear

# 2. Cek session lifetime di .env
SESSION_LIFETIME=120

# 3. Pastikan meta CSRF token ada
<meta name="csrf-token" content="{{ csrf_token() }}">
```

#### 4. JavaScript Syntax Error
**Gejala**: Uncaught SyntaxError atau function not defined

**Solusi**:
```bash
# 1. Hard refresh
Ctrl + Shift + R

# 2. Clear browser cache
Ctrl + Shift + Delete

# 3. Test di Incognito
Ctrl + Shift + N

# 4. Cek versi script di console
Harus ada: === CBT EXAM SYSTEM v3.0 ===
```

#### 5. Anti-Cheat Terlalu Sensitif
**Gejala**: Auto-submit terlalu cepat

**Solusi**:
```javascript
// Edit maxTabSwitch di mengerjakan.blade.php
const maxTabSwitch = 3; // Ubah dari 2 ke 3 atau lebih
```

#### 6. Import Excel Gagal
**Gejala**: Error saat import siswa/soal

**Solusi**:
```bash
# 1. Pastikan format Excel sesuai template
# 2. Cek ekstensi file (.xlsx)
# 3. Cek ukuran file (max 2MB)
# 4. Cek permission folder storage
chmod -R 775 storage
chown -R www-data:www-data storage
```

### Database Issues

#### Reset Database
```bash
php artisan migrate:fresh --seed
```

#### Backup Database
```bash
mysqldump -u root -p cbt_smk > backup.sql
```

#### Restore Database
```bash
mysql -u root -p cbt_smk < backup.sql
```

### Permission Issues
```bash
# Linux/Mac
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Windows (run as Administrator)
icacls storage /grant Users:F /T
icacls bootstrap\cache /grant Users:F /T
```

### Clear All Cache
```bash
php artisan optimize:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
composer dump-autoload
```

---

## 🤝 Kontribusi

Kontribusi sangat diterima! Silakan ikuti langkah berikut:

1. Fork repository
2. Buat branch fitur (`git checkout -b feature/AmazingFeature`)
3. Commit perubahan (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buat Pull Request

### Coding Standards
- Follow PSR-12 coding standard
- Write meaningful commit messages
- Add comments for complex logic
- Update documentation
- Write tests for new features

---

## 📝 Changelog

### Version 3.1 (Current) — Skalabilitas 500+ Concurrent Users
- ✅ Autosave jawaban jadi 1 query upsert (sebelumnya 2 query + logging berat tiap request)
- ✅ Cache soal+opsi ujian (Redis) dengan urutan per-siswa yang benar untuk ujian acak
- ✅ Grading saat submit ujian dibungkus transaction + bulk update (dari puluhan query/siswa jadi segelintir)
- ✅ Cache warm-up otomatis terjadwal tiap 15 menit
- ✅ Rate limiting di endpoint login, verify-token, save-jawaban, anti-cheat
- ✅ Import soal Excel besar (>150 baris) diproses via queue worker di background
- ✅ Perbaikan bug: query kolom `is_published`/`tanggal_ujian` yang tidak ada di skema, koneksi Redis `queue` yang belum terkonfigurasi
- ✅ Konfigurasi deployment produksi lengkap (lihat [Skalabilitas & Deployment Produksi](#-skalabilitas--deployment-produksi-500-concurrent-users))

### Version 3.0
- ✅ Anti-cheat system enabled
- ✅ Fixed URL routing (relative paths)
- ✅ Improved auto-save mechanism
- ✅ Enhanced error handling
- ✅ Modern UI redesign
- ✅ Better mobile responsiveness

### Version 2.0
- ✅ Fixed jawaban tidak tersimpan
- ✅ Added comprehensive logging
- ✅ Retry mechanism for AJAX
- ✅ Cache prevention meta tags
- ✅ Debugging tools added

### Version 1.0
- ✅ Initial release
- ✅ Basic CBT functionality
- ✅ Multi-role authentication
- ✅ Bank soal management
- ✅ Ujian management
- ✅ Auto-grading system

---

## 📄 Lisensi

Proyek ini dilisensikan di bawah [MIT License](LICENSE).

```
MIT License

Copyright (c) 2024 CBT SMK

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

---

## 👥 Tim Pengembang

- **Developer**: [Your Name]
- **Email**: [your.email@example.com]
- **Website**: [https://yourwebsite.com]

---

## 🙏 Acknowledgments

- Laravel Framework
- Bootstrap Team
- Bootstrap Icons
- Google Fonts
- PhpSpreadsheet
- All contributors

---

## 🚀 Skalabilitas & Deployment Produksi (500+ Concurrent Users)

Sistem ini disiapkan untuk menangani ±500 siswa mengerjakan ujian secara bersamaan. Target deployment produksi adalah **VPS Linux (Nginx + PHP-FPM + MySQL + Redis)** — bukan XAMPP. Bagian ini mendokumentasikan semua konfigurasi terkait dan cara memakainya; semua file konfigurasi yang disebut di sini ada di root repository.

### Ringkasan arsitektur

| Komponen | Default (dev/XAMPP) | Production | Kenapa |
|---|---|---|---|
| Cache | `file` | **Redis** (DB 1) | Cache soal+opsi ujian dibaca berulang oleh ratusan siswa sekaligus; file cache jadi bottleneck I/O disk |
| Session | `file` | **Redis** (DB 2) | File session dengan ratusan siswa aktif bersamaan menimbulkan lock/I/O disk berlebih |
| Queue | `database` | **Redis** (DB 3) | Untuk job background (lihat [Queue Worker](#queue-worker-supervisor)) |
| Web server | Apache (XAMPP) | **Nginx + PHP-FPM** | PHP-FPM bisa di-scale jumlah worker-nya, lebih efisien untuk request bervolume tinggi dibanding Apache mod_php |

Database Redis sengaja dipisah per keperluan (`REDIS_DB=0` default/unused, `REDIS_CACHE_DB=1`, `REDIS_SESSION_DB=2`, `REDIS_QUEUE_DB=3`) supaya cache, session, dan queue tidak saling menimpa atau ter-evict oleh `maxmemory-policy` satu sama lain. Konfigurasi koneksinya ada di `config/database.php` (blok `redis`).

### Apa yang disimpan synchronous vs lewat queue worker

- **Autosave jawaban ujian** (`ExamController::saveJawaban`) — **tetap synchronous** langsung ke MySQL (1 query upsert). Ini keputusan sadar: endpoint ini paling sering dipanggil (tiap beberapa detik oleh tiap siswa), tapi satu query upsert amat ringan bagi MySQL, dan menaruhnya di queue justru berisiko jawaban terakhir siswa belum sempat diproses worker saat mereka klik Submit.
- **Import soal Excel** (`ImportBankSoalController::import`) — file dengan **>150 baris valid** diproses di background lewat `ImportBankSoalJob` (redis queue), supaya request HTTP admin tidak menunggu lama / berisiko timeout. File kecil (kasus umum) tetap diproses instan seperti biasa.
- **Cache warm-up** (`php artisan cache:warmup`) — dijadwalkan otomatis tiap 15 menit lewat Laravel Scheduler (`app/Console/Kernel.php`) supaya cache ujian yang akan/sedang berlangsung selalu hangat.

### File konfigurasi (root repository)

| File | Fungsi | Taruh di |
|---|---|---|
| `.env.production` | Template environment produksi (Redis untuk cache/session/queue, `APP_DEBUG=false`, dll) | `cp .env.production .env` di server |
| `mysql-optimization.cnf` | Tuning MySQL untuk 500+ koneksi (buffer pool, connection limit, slow query log) | `/etc/mysql/conf.d/` |
| `redis.conf` | Tuning Redis (maxmemory, persistence, dll) | `/etc/redis/redis.conf` |
| `php-fpm-optimization.conf` | Pool PHP-FPM (150 child process, OPcache, resource limit) | `/etc/php/8.x/fpm/pool.d/www.conf` |
| `nginx-optimization.conf` | Virtual host Nginx (rate limiting, FastCGI cache, gzip) | `/etc/nginx/nginx.conf` |
| `supervisor-queue-worker.conf` | Menjaga `queue:work` tetap jalan sebagai service | `/etc/supervisor/conf.d/` |
| `install-optimization.sh` | Script bantu instalasi paket-paket di atas | dijalankan manual di server |
| `load-test.sh` | Load testing dengan Apache Bench / Siege | dijalankan manual untuk uji kapasitas |

> Semua file di atas berisi placeholder (`/var/www/cbt-smk`, `cbt.yourschool.com`, `www-data`, dll) yang **wajib disesuaikan** dengan path/domain/user server Anda sebelum dipakai.

### Langkah deploy ke VPS

```bash
# 1. Setup aplikasi
git clone <repo-url> /var/www/cbt-smk && cd /var/www/cbt-smk
composer install --no-dev --optimize-autoloader
cp .env.production .env
php artisan key:generate
# Edit .env: isi DB_PASSWORD, REDIS_PASSWORD, APP_URL sesuai domain

# 2. Install & konfigurasi MySQL, Redis, Nginx, PHP-FPM
sudo cp mysql-optimization.cnf /etc/mysql/conf.d/optimization.cnf && sudo systemctl restart mysql
sudo cp redis.conf /etc/redis/redis.conf && sudo systemctl restart redis-server
sudo cp php-fpm-optimization.conf /etc/php/8.2/fpm/pool.d/www.conf && sudo systemctl restart php8.2-fpm
sudo cp nginx-optimization.conf /etc/nginx/nginx.conf && sudo nginx -t && sudo systemctl restart nginx

# 3. Migrasi & optimasi Laravel
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Queue worker (Supervisor)
sudo cp supervisor-queue-worker.conf /etc/supervisor/conf.d/cbt-queue-worker.conf
sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl start cbt-queue-worker:*

# 5. Scheduler (untuk cache:warmup otomatis) — tambahkan ke crontab:
# * * * * * cd /var/www/cbt-smk && php artisan schedule:run >> /dev/null 2>&1
```

### Queue Worker (Supervisor)

`supervisor-queue-worker.conf` menjalankan `php artisan queue:work redis --queue=default,low` dengan 4 process paralel, auto-restart bila crash. Cek status worker:
```bash
sudo supervisorctl status cbt-queue-worker:*
php artisan queue:failed          # lihat job yang gagal
php artisan queue:retry all       # retry semua job gagal
```

### Rate Limiting

Middleware `App\Http\Middleware\ThrottleRequests` (alias `throttle.custom`) aktif di endpoint yang rawan disalahgunakan/dibanjiri, per kombinasi user+IP:

| Route | Limit |
|---|---|
| `POST /login` | 5x / menit |
| `POST /exam/{ujian}/verify-token` | 10x / menit |
| `POST /exam/{ujian}/save-jawaban` | 120x / menit |
| `POST /exam/{ujian}/anti-cheat` | 30x / menit |

Nginx (`nginx-optimization.conf`) juga punya rate limiting di layer web server sebagai lapisan pertahanan tambahan (`limit_req_zone` untuk `login` dan `save`).

### Monitoring

```bash
# Live metrics: koneksi DB, memory Redis, panjang antrian, jumlah siswa aktif
php artisan system:monitor --interval=60
```

### Load Testing

```bash
chmod +x load-test.sh
./load-test.sh

# Manual:
ab -n 1000 -c 100 http://127.0.0.1:8000/
siege -c 500 -t 60S http://127.0.0.1:8000/
```

### Catatan penting

- Development lokal (XAMPP) **tidak perlu** Redis/OPcache — `.env` biasa (`CACHE_DRIVER=file`, dll) sudah cukup untuk development. Redis/PHP-FPM/Nginx hanya untuk server produksi.
- Kalau `QUEUE_CONNECTION=redis` dipakai tanpa Redis server jalan/terkonfigurasi, dispatch job akan error koneksi — cek `config/database.php` blok `redis.queue` sudah ada isinya (bukan hanya `default`/`cache`) sebelum mengaktifkan Redis queue.

---

## 📞 Support

Jika Anda memiliki pertanyaan atau membutuhkan bantuan:

- 📧 Email: support@cbt-smk.com
- 💬 Discord: [Join our server]
- 📖 Documentation: [Read the docs]
- 🐛 Issues: [Report a bug]

---

## 🔗 Links

- [Demo](https://demo.cbt-smk.com)
- [Documentation](https://docs.cbt-smk.com)
- [GitHub](https://github.com/username/cbt-smk)
- [Website](https://cbt-smk.com)

---

<div align="center">

**Made with ❤️ for Indonesian Education**

⭐ Star us on GitHub — it helps!

[Report Bug](https://github.com/username/cbt-smk/issues) · [Request Feature](https://github.com/username/cbt-smk/issues)

</div>
