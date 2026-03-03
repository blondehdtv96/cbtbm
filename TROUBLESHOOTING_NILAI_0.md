# Troubleshooting: Nilai 0.00 Padahal Jawaban Sudah Diisi

## Langkah-Langkah Debugging

### 1. Cek Browser Console (PENTING!)

Buka ujian, jawab beberapa soal, lalu:
1. Tekan F12 untuk buka Developer Tools
2. Klik tab "Console"
3. Perhatikan log saat menjawab soal:
   ```
   Saving answer: {bank_soal_id: 123, jawaban: "A"}
   ✓ Jawaban tersimpan: 123
   ```
4. Saat klik "Selesai", perhatikan:
   ```
   === SAVING ALL ANSWERS ===
   Total answers in memory: 10
   Answers object: {123: "A", 124: "B", ...}
   Saving soal 123: {bank_soal_id: 123, jawaban: "A"}
   ✓ Saved 1/10: 123
   ...
   ✓✓✓ ALL 10 ANSWERS SAVED SUCCESSFULLY ✓✓✓
   ```

**Jika ada error di console, catat dan perbaiki!**

### 2. Cek Network Tab

1. Buka tab "Network" di Developer Tools
2. Jawab 1 soal
3. Cari request ke `/save-jawaban`
4. Klik request tersebut
5. Cek:
   - Status: Harus 200 OK
   - Response: Harus `{"success": true, ...}`
   - Request Payload: Harus ada `bank_soal_id` dan `jawaban`

**Jika status bukan 200, ada masalah di backend!**

### 3. Cek Database Langsung

Jalankan script checker:
```bash
php check_jawaban.php
```

Atau query manual:
```sql
-- Cek peserta terakhir
SELECT * FROM peserta_ujians ORDER BY id DESC LIMIT 1;

-- Cek jawaban (ganti 123 dengan peserta_ujian_id)
SELECT 
    js.id,
    js.bank_soal_id,
    js.jawaban_dipilih,
    js.is_correct,
    js.nilai,
    bs.pertanyaan
FROM jawaban_siswas js
LEFT JOIN bank_soals bs ON js.bank_soal_id = bs.id
WHERE js.peserta_ujian_id = 123;
```

**Yang harus dicek:**
- Apakah ada record di `jawaban_siswas`?
- Apakah `jawaban_dipilih` terisi atau NULL?
- Apakah `is_correct` dan `nilai` sudah diupdate?

### 4. Cek Log Laravel

Buka file: `storage/logs/laravel.log`

Cari log terbaru dengan keyword:
```
Submitting exam
Processing exam submission
Checking answer
Exam submitted successfully
```

**Perhatikan:**
```
[2024-xx-xx] local.INFO: Processing exam submission
{
    "peserta_id": 123,
    "ujian_id": 45,
    "total_jawaban": 10,
    "jawaban_terisi": 10,  <-- Harus sama dengan total_jawaban
    "jawaban_detail": [...]
}
```

```
[2024-xx-xx] local.INFO: Checking answer
{
    "soal_id": 789,
    "jawaban_siswa": "A",
    "jawaban_benar": "A",
    "is_match": true  <-- Harus true jika benar
}
```

```
[2024-xx-xx] local.INFO: Exam submitted successfully
{
    "nilai_akhir": 80.00,  <-- Harus > 0 jika ada jawaban benar
    "benar": 8,
    "salah": 2,
    "kosong": 0,
    "total_bobot": 100,
    "total_nilai": 80
}
```

## Kemungkinan Masalah & Solusi

### Masalah 1: Jawaban Tidak Tersimpan ke Database

**Gejala:**
- Console log: "✓ Jawaban tersimpan"
- Database: Record tidak ada atau `jawaban_dipilih` NULL

**Penyebab:**
- Request gagal tapi error tidak terlihat
- CSRF token invalid
- Session expired

**Solusi:**
```javascript
// Tambahkan di console browser untuk test manual
fetch('/cbtbm/public/exam/45/save-jawaban', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Accept': 'application/json',
    },
    body: JSON.stringify({
        bank_soal_id: 123,
        jawaban: 'A'
    })
})
.then(r => r.json())
.then(d => console.log('Response:', d))
.catch(e => console.error('Error:', e));
```

### Masalah 2: Jawaban Tersimpan Tapi Tidak Dinilai

**Gejala:**
- Database: `jawaban_dipilih` terisi
- Database: `is_correct` masih NULL, `nilai` = 0

**Penyebab:**
- Tidak ada opsi jawaban yang ditandai `is_correct = true`
- Tipe soal bukan 'pg'
- Relasi `bankSoal` atau `opsiJawabans` tidak load

**Solusi:**
```sql
-- Cek opsi jawaban benar
SELECT bs.id, bs.pertanyaan, oj.opsi_label, oj.is_correct
FROM bank_soals bs
LEFT JOIN opsi_jawabans oj ON bs.id = oj.bank_soal_id
WHERE bs.id = 123;

-- Harus ada minimal 1 row dengan is_correct = 1
```

### Masalah 3: Jawaban Dinilai Tapi Nilai Akhir 0

**Gejala:**
- Database: `is_correct` = 1, `nilai` terisi
- Database: `peserta_ujians.nilai` = 0.00

**Penyebab:**
- `totalBobot` = 0 (soal tidak punya bobot_nilai)
- Perhitungan salah

**Solusi:**
```sql
-- Cek bobot soal
SELECT id, pertanyaan, bobot_nilai FROM bank_soals WHERE id IN (123, 124, ...);

-- Bobot harus > 0
```

### Masalah 4: Path URL Salah

**Gejala:**
- Network tab: 404 Not Found
- Console: "Save error"

**Penyebab:**
- Path `/cbtbm/public/exam/` tidak sesuai instalasi

**Solusi:**
Ubah di `mengerjakan.blade.php`:
```javascript
// Jika instalasi di root
fetch(`/exam/${ujianId}/save-jawaban`, ...)

// Jika instalasi di subfolder
fetch(`/cbtbm/public/exam/${ujianId}/save-jawaban`, ...)

// Atau gunakan Laravel helper
fetch(`{{ url('exam') }}/${ujianId}/save-jawaban`, ...)
```

### Masalah 5: Timeout / Koneksi Lambat

**Gejala:**
- Beberapa jawaban tersimpan, beberapa tidak
- Console: "Save error" atau "Retry failed"

**Solusi:**
1. Tingkatkan timeout di `config/database.php`:
```php
'mysql' => [
    'options' => [
        PDO::ATTR_TIMEOUT => 30,
    ],
],
```

2. Tingkatkan `max_execution_time` di `php.ini`:
```ini
max_execution_time = 300
```

## Quick Fix

Jika masih bermasalah, coba ini:

### 1. Clear Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### 2. Regenerate Autoload
```bash
composer dump-autoload
```

### 3. Restart Server
```bash
# Jika pakai Apache
sudo service apache2 restart

# Jika pakai Nginx
sudo service nginx restart
sudo service php8.1-fpm restart
```

### 4. Test Manual Submit

Buat route test di `routes/web.php`:
```php
Route::get('/test-submit/{ujian}', function($ujianId) {
    $ujian = \App\Models\Ujian::findOrFail($ujianId);
    $peserta = \App\Models\PesertaUjian::where('ujian_id', $ujianId)
        ->where('status', 'sedang')
        ->first();
    
    if (!$peserta) {
        return 'Peserta tidak ditemukan';
    }
    
    $controller = new \App\Http\Controllers\ExamController();
    return $controller->submitExam($ujian, $peserta);
});
```

Akses: `http://localhost/cbtbm/public/test-submit/45`

## Kontak Support

Jika masih bermasalah setelah semua langkah di atas:
1. Screenshot console log
2. Screenshot network tab
3. Copy log dari `storage/logs/laravel.log`
4. Export data dari database (peserta_ujians & jawaban_siswas)
5. Kirim ke developer
