# Panduan Cek Jawaban Siswa yang Dianggap Tidak Menjawab

## Masalah
Siswa sudah mengerjakan ujian sampai selesai, tapi di halaman detail jawaban guru (`/ujian/{id}/peserta/{id}/jawaban`) masih dianggap "Tidak menjawab".

## Kemungkinan Penyebab

### 1. Jawaban Tersimpan Sebagai String Kosong
Database menyimpan `jawaban_dipilih = ''` (empty string) bukan `NULL`, sehingga fungsi `empty()` di PHP menganggapnya kosong.

### 2. Jawaban Tidak Tersimpan Sama Sekali
Request AJAX gagal atau ada error saat menyimpan.

### 3. Data Tersimpan Tapi View Salah Baca
Logika pengecekan di view tidak tepat.

## Langkah Debugging

### LANGKAH 1: Cek Browser Console (Saat Siswa Mengerjakan)

1. Buka ujian sebagai siswa
2. Tekan F12 → Tab "Console"
3. Jawab beberapa soal
4. Perhatikan log:

```
Saving answer: {bank_soal_id: 123, jawaban: "A"}
✓ Jawaban tersimpan: 123 {success: true, ...}
```

**Jika ada error:**
```
✗ Save error: Error: HTTP 500
```
→ Ada masalah di backend, cek log Laravel

### LANGKAH 2: Cek Network Tab

1. F12 → Tab "Network"
2. Jawab 1 soal
3. Cari request "save-jawaban"
4. Klik request → Cek:
   - Status: Harus 200 OK
   - Response: `{"success": true, "message": "Jawaban berhasil disimpan", "data": {...}}`
   - Payload: `{bank_soal_id: 123, jawaban: "A"}`

**Jika status bukan 200:**
- 404: Path URL salah
- 419: CSRF token expired
- 500: Error di server, cek log

### LANGKAH 3: Cek Database Langsung

#### Opsi A: Gunakan Script PHP
```bash
php check_jawaban.php
```

Masukkan peserta_ujian_id (misal: 11)

#### Opsi B: Gunakan SQL Manual
```bash
mysql -u root -p
use cbtbm;
source check_database.sql
```

Edit file `check_database.sql`, ganti angka 11 dengan peserta_ujian_id yang sesuai.

#### Opsi C: Query Manual
```sql
-- Cek jawaban peserta ID 11
SELECT 
    id,
    bank_soal_id,
    jawaban_dipilih,
    CASE 
        WHEN jawaban_dipilih IS NULL THEN 'NULL'
        WHEN jawaban_dipilih = '' THEN 'EMPTY STRING'
        ELSE CONCAT('VALUE: ', jawaban_dipilih)
    END as status
FROM jawaban_siswas
WHERE peserta_ujian_id = 11;
```

**Yang Harus Dicek:**
- Apakah ada record? (Jika tidak, jawaban tidak tersimpan)
- Apakah `jawaban_dipilih` berisi nilai (A, B, C, D) atau NULL/empty?
- Berapa banyak yang terisi vs kosong?

### LANGKAH 4: Cek Log Laravel

Buka: `storage/logs/laravel.log`

Cari log terbaru dengan keyword:
```
Showing jawaban page
```

Perhatikan:
```
[2024-xx-xx] local.INFO: Showing jawaban page
{
    "peserta_id": 11,
    "ujian_id": 11,
    "total_jawaban": 10,
    "jawaban_terisi": 10,  <-- Harus > 0 jika siswa sudah jawab
    "jawaban_kosong": 0,
    "sample_jawaban": [
        {
            "id": 123,
            "soal_id": 456,
            "jawaban": "A",  <-- Harus ada nilai
            "is_null": false,
            "is_empty": false,
            "trimmed": "A"
        }
    ]
}
```

**Jika `jawaban_terisi = 0` tapi siswa sudah jawab:**
→ Data tidak tersimpan atau tersimpan sebagai empty string

## Solusi

### Solusi 1: Fix Data yang Sudah Ada

Jika data tersimpan sebagai empty string, jalankan:

```bash
php fix_empty_answers.php
```

Script ini akan:
1. Mencari semua jawaban dengan `jawaban_dipilih = ''`
2. Menampilkan jumlahnya
3. Menawarkan untuk set ke NULL
4. Cek detail untuk peserta tertentu

### Solusi 2: Clear Cache & Restart

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# Restart web server
sudo service apache2 restart
# atau
sudo service nginx restart
```

### Solusi 3: Test Ulang dengan Logging

1. Buka ujian baru sebagai siswa
2. Jawab 2-3 soal
3. Klik "Selesai"
4. Cek console log: Harus ada "✓✓✓ ALL X ANSWERS SAVED SUCCESSFULLY"
5. Cek database: `SELECT * FROM jawaban_siswas WHERE peserta_ujian_id = X`
6. Cek halaman guru: Harus tampil jawaban

### Solusi 4: Perbaikan Manual di Database

Jika data benar-benar hilang, tidak bisa diperbaiki. Siswa harus mengerjakan ulang.

Tapi jika hanya masalah tampilan, coba:

```sql
-- Update view cache (jika ada)
-- Atau refresh data peserta
UPDATE peserta_ujians 
SET updated_at = NOW() 
WHERE id = 11;
```

## Pencegahan

### 1. Validasi di Frontend
Sudah diperbaiki di `mengerjakan.blade.php`:
- Logging detail saat save
- Retry otomatis jika gagal
- Alert jika ada error
- Simpan semua jawaban sebelum submit

### 2. Validasi di Backend
Sudah diperbaiki di `ExamController.php`:
- Validasi input
- Error handling
- Logging detail
- Response yang jelas

### 3. Monitoring
Cek log secara berkala:
```bash
tail -f storage/logs/laravel.log | grep "save-jawaban\|Submitting exam"
```

## FAQ

**Q: Kenapa jawaban tersimpan tapi dianggap kosong?**
A: Kemungkinan tersimpan sebagai empty string `''` bukan NULL. Gunakan `fix_empty_answers.php` untuk perbaiki.

**Q: Apakah siswa harus mengerjakan ulang?**
A: Tidak, jika data ada di database. Cukup perbaiki dengan script atau manual.

**Q: Bagaimana cara mencegah ini terjadi lagi?**
A: Perbaikan sudah dilakukan di kode. Pastikan:
- Siswa tidak refresh halaman saat ujian
- Koneksi internet stabil
- Server tidak overload

**Q: Apakah bisa restore jawaban yang hilang?**
A: Tidak, jika data benar-benar tidak tersimpan di database. Tapi jika ada di database, bisa diperbaiki.

## Kontak Support

Jika masih bermasalah setelah semua langkah:
1. Screenshot console log (F12)
2. Screenshot network tab
3. Export data: `SELECT * FROM jawaban_siswas WHERE peserta_ujian_id = X`
4. Copy log: `storage/logs/laravel.log` (100 baris terakhir)
5. Screenshot halaman guru yang menunjukkan "Tidak menjawab"

## Checklist Debugging

- [ ] Cek browser console saat siswa mengerjakan
- [ ] Cek network tab untuk request save-jawaban
- [ ] Cek database dengan script atau SQL
- [ ] Cek log Laravel
- [ ] Jalankan fix_empty_answers.php jika perlu
- [ ] Clear cache dan restart server
- [ ] Test ulang dengan ujian baru
- [ ] Dokumentasikan hasil untuk referensi

## File Terkait

- `resources/views/exam/mengerjakan.blade.php` - Halaman ujian siswa
- `resources/views/ujian/jawaban.blade.php` - Halaman detail jawaban guru
- `app/Http/Controllers/ExamController.php` - Controller ujian siswa
- `app/Http/Controllers/UjianController.php` - Controller manajemen ujian guru
- `check_jawaban.php` - Script cek jawaban
- `fix_empty_answers.php` - Script perbaiki data
- `check_database.sql` - Query SQL untuk debugging
