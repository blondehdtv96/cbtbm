# Debug: Jawaban Tersimpan NULL di Database

## Masalah
Di database tabel `jawaban_siswas`, kolom `jawaban_dipilih` berisi `NULL` padahal siswa sudah menjawab.

## Langkah Debugging

### STEP 1: Cek Browser Console (WAJIB!)

1. Buka ujian sebagai siswa
2. Tekan F12 → Tab "Console"
3. Jawab 1 soal (pilih A, B, C, atau D)
4. Perhatikan log yang muncul:

**LOG YANG DIHARAPKAN:**
```
[SELECT] Soal 38: A
[SAVE] Preparing to save: {bank_soal_id: 38, jawaban: "A"}
[SAVE] URL: http://127.0.0.1:8000/exam/11/save-jawaban
[SAVE] CSRF Token: Present
[SAVE] Response status: 200 OK
[SAVE] ✓ Success: 38 {success: true, message: "...", data: {...}}
[SAVE] ✓ Verified - jawaban_dipilih: "A"
[SELECT SUCCESS] Soal 38 saved
```

**JIKA ADA ERROR:**

Error 1: CSRF Token MISSING
```
[SAVE] CSRF Token: MISSING!
```
→ Token tidak ada, cek meta tag di HTML

Error 2: HTTP 404
```
[SAVE] Response status: 404 Not Found
```
→ URL route salah

Error 3: HTTP 419
```
[SAVE] Response status: 419 Page Expired
```
→ CSRF token expired, session habis

Error 4: HTTP 500
```
[SAVE] Response status: 500 Internal Server Error
```
→ Error di backend, cek log Laravel

Error 5: Network Error
```
[SAVE] ✗ Error: Failed to fetch
```
→ Koneksi terputus atau server down

### STEP 2: Cek Network Tab

1. F12 → Tab "Network"
2. Jawab 1 soal
3. Cari request "save-jawaban"
4. Klik request tersebut
5. Cek tab:

**Headers Tab:**
- Request URL: Harus benar
- Request Method: POST
- Status Code: 200 OK

**Payload Tab:**
```json
{
  "bank_soal_id": 38,
  "jawaban": "A"
}
```
→ Harus ada nilai "A", "B", "C", atau "D"
→ TIDAK BOLEH null atau ""

**Response Tab:**
```json
{
  "success": true,
  "message": "Jawaban berhasil disimpan",
  "data": {
    "id": 131,
    "bank_soal_id": 38,
    "jawaban_dipilih": "A",  ← HARUS ADA NILAI
    "is_ragu": false,
    "verified": true
  }
}
```

**JIKA Response berbeda:**

Response 1: jawaban_dipilih null
```json
{
  "data": {
    "jawaban_dipilih": null  ← MASALAH!
  }
}
```
→ Backend menerima null, cek request payload

Response 2: Error response
```json
{
  "error": "Sesi tidak valid"
}
```
→ Peserta tidak ditemukan atau status bukan 'sedang'

Response 3: Validation error
```json
{
  "error": "Validasi gagal",
  "errors": {
    "bank_soal_id": ["..."]
  }
}
```
→ Data tidak valid

### STEP 3: Cek Log Laravel

Buka: `storage/logs/laravel.log`

Cari log terbaru (paling bawah):

**LOG YANG DIHARAPKAN:**
```
[2024-xx-xx] local.INFO: Save jawaban request
{
    "peserta_id": 15,
    "bank_soal_id": 38,
    "jawaban": "A",  ← HARUS ADA NILAI
    "is_ragu": null
}

[2024-xx-xx] local.INFO: Jawaban saved successfully
{
    "id": 131,
    "peserta_id": 15,
    "bank_soal_id": 38,
    "jawaban_dipilih": "A",  ← HARUS ADA NILAI
    "is_null": false,
    "is_empty": false
}
```

**JIKA LOG BERBEDA:**

Log 1: jawaban null
```
"jawaban": null  ← MASALAH!
```
→ Frontend mengirim null, cek JavaScript

Log 2: Empty jawaban warning
```
[local.WARNING] Empty jawaban received
```
→ Jawaban kosong diterima backend

Log 3: Peserta not found
```
[local.WARNING] Save jawaban: Peserta not found or not active
```
→ Peserta tidak ada atau status bukan 'sedang'

Log 4: Validation error
```
[local.ERROR] Validation error saving answer
```
→ Data tidak valid

Log 5: Exception error
```
[local.ERROR] Error saving answer
```
→ Ada error di backend

### STEP 4: Cek Database Real-Time

Buka 2 terminal:

**Terminal 1: Monitor Log**
```bash
tail -f storage/logs/laravel.log | grep "Save jawaban"
```

**Terminal 2: Monitor Database**
```bash
mysql -u root -p
use cbtbm;

-- Ganti 15 dengan peserta_ujian_id
SELECT id, bank_soal_id, jawaban_dipilih, updated_at 
FROM jawaban_siswas 
WHERE peserta_ujian_id = 15 
ORDER BY updated_at DESC 
LIMIT 5;
```

Refresh query setiap kali siswa jawab soal.

**YANG HARUS TERJADI:**
1. Siswa klik jawaban A
2. Console log: [SAVE] Success
3. Laravel log: Jawaban saved successfully
4. Database: jawaban_dipilih = "A"

**JIKA TIDAK TERJADI:**
→ Ada masalah di salah satu step

## Kemungkinan Penyebab & Solusi

### Penyebab 1: JavaScript Tidak Jalan

**Gejala:**
- Tidak ada log di console saat klik jawaban
- Tidak ada request di network tab

**Penyebab:**
- JavaScript error
- Function tidak terpanggil

**Solusi:**
1. Cek console untuk error JavaScript
2. Pastikan tidak ada syntax error
3. Cek apakah `selectOption()` terpanggil

### Penyebab 2: Request Tidak Sampai Backend

**Gejala:**
- Ada log [SAVE] di console
- Tidak ada request di network tab
- Tidak ada log di Laravel

**Penyebab:**
- Fetch gagal sebelum kirim
- URL salah
- CORS issue

**Solusi:**
1. Cek URL di console log
2. Pastikan URL benar
3. Test manual dengan curl:
```bash
curl -X POST http://127.0.0.1:8000/exam/11/save-jawaban \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: xxx" \
  -d '{"bank_soal_id":38,"jawaban":"A"}'
```

### Penyebab 3: Backend Menerima Null

**Gejala:**
- Request sampai backend (ada di log)
- Laravel log: "jawaban": null
- Database: jawaban_dipilih = NULL

**Penyebab:**
- Frontend mengirim null
- JSON parsing error
- Request body kosong

**Solusi:**
1. Cek payload di network tab
2. Pastikan `jawaban: "A"` bukan `jawaban: null`
3. Cek JavaScript:
```javascript
// SALAH
answers[soalId] = null;
saveAnswer(soalId, null);

// BENAR
answers[soalId] = label;  // "A", "B", "C", "D"
saveAnswer(soalId, label);
```

### Penyebab 4: Database Constraint

**Gejala:**
- Backend log: saved successfully
- Database: masih NULL

**Penyebab:**
- Database trigger
- Default value NULL
- Constraint issue

**Solusi:**
```sql
-- Cek struktur tabel
DESCRIBE jawaban_siswas;

-- Cek constraint
SHOW CREATE TABLE jawaban_siswas;

-- Test insert manual
INSERT INTO jawaban_siswas 
(peserta_ujian_id, bank_soal_id, jawaban_dipilih, created_at, updated_at) 
VALUES 
(15, 38, 'A', NOW(), NOW());

-- Cek apakah tersimpan
SELECT * FROM jawaban_siswas WHERE id = LAST_INSERT_ID();
```

### Penyebab 5: Cache Issue

**Gejala:**
- Semua log OK
- Database OK
- Tapi halaman guru masih tampil NULL

**Penyebab:**
- View cache
- Query cache
- Browser cache

**Solusi:**
```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear

# Restart server
sudo service apache2 restart
```

## Quick Test

### Test 1: Manual Save via Tinker
```bash
php artisan tinker
```

```php
$peserta = \App\Models\PesertaUjian::find(15);
$jawaban = \App\Models\JawabanSiswa::updateOrCreate(
    ['peserta_ujian_id' => 15, 'bank_soal_id' => 38],
    ['jawaban_dipilih' => 'A']
);
$jawaban->refresh();
echo "Saved: " . $jawaban->jawaban_dipilih;  // Harus: "A"
```

### Test 2: Manual Request via Browser Console
```javascript
fetch('/exam/11/save-jawaban', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Accept': 'application/json',
    },
    body: JSON.stringify({
        bank_soal_id: 38,
        jawaban: 'A'
    })
})
.then(r => r.json())
.then(d => console.log('Result:', d))
.catch(e => console.error('Error:', e));
```

### Test 3: Check Existing Data
```bash
php check_jawaban.php
```

Masukkan peserta_ujian_id: 15

## Monitoring Real-Time

Buka 3 terminal:

**Terminal 1: Laravel Log**
```bash
tail -f storage/logs/laravel.log
```

**Terminal 2: Database Monitor**
```bash
watch -n 1 'mysql -u root -p -e "SELECT id, bank_soal_id, jawaban_dipilih FROM cbtbm.jawaban_siswas WHERE peserta_ujian_id = 15 ORDER BY id DESC LIMIT 5"'
```

**Terminal 3: Network Monitor**
```bash
# Jika pakai Linux
sudo tcpdump -i any port 8000 -A | grep -i "save-jawaban"
```

## Kesimpulan

Jika setelah semua langkah masih NULL:
1. Screenshot console log
2. Screenshot network tab (request & response)
3. Copy log Laravel (50 baris terakhir)
4. Export data database
5. Kirim untuk analisis lebih lanjut

## File Terkait
- `resources/views/exam/mengerjakan.blade.php` - Frontend
- `app/Http/Controllers/ExamController.php` - Backend
- `app/Models/JawabanSiswa.php` - Model
- `storage/logs/laravel.log` - Log file
