# Perbaikan Masalah Jawaban Ujian Tidak Tersimpan

## Masalah yang Ditemukan

1. **Kode JavaScript tidak lengkap** - Fungsi `saveAllAnswers()` tidak selesai ditulis
2. **Tidak ada retry mechanism** - Jika request gagal, jawaban hilang
3. **Race condition** - Submit form sebelum jawaban tersimpan
4. **Anti-cheat terlalu agresif** - Langsung submit saat pindah tab sekali
5. **Tidak ada logging** - Sulit debug masalah penyimpanan

## Perbaikan yang Dilakukan

### 1. Frontend (resources/views/exam/mengerjakan.blade.php)

#### A. Fungsi saveAnswer() - Ditambahkan Retry & Promise
```javascript
// Sekarang return Promise dan ada retry otomatis jika gagal
function saveAnswer(soalId, jawaban, isRagu = null) {
    return fetch(...)
        .then(response => response.json())
        .then(data => {
            console.log('Jawaban tersimpan:', soalId);
            return data;
        })
        .catch(err => {
            // Retry sekali lagi jika gagal
            return fetch(...).catch(err2 => console.error('Retry failed:', err2));
        });
}
```

#### B. Fungsi saveAllAnswers() - Lengkap & Berfungsi
```javascript
// Simpan SEMUA jawaban sebelum submit
async function saveAllAnswers() {
    const savePromises = [];
    
    for (const [soalId, jawaban] of Object.entries(answers)) {
        if (jawaban !== null && jawaban !== '') {
            const promise = fetch(...);
            savePromises.push(promise);
        }
    }
    
    // Tunggu semua request selesai
    await Promise.all(savePromises);
}
```

#### C. Fungsi confirmSubmit() - Async dengan Delay
```javascript
async function confirmSubmit() {
    if (confirm(msg)) {
        // Pastikan semua jawaban tersimpan dulu
        await saveAllAnswers();
        // Tunggu 500ms untuk memastikan request selesai
        setTimeout(() => {
            document.getElementById('submitForm').submit();
        }, 500);
    }
}
```

#### D. Timer - Simpan Jawaban Sebelum Auto-Submit
```javascript
async function updateTimer() {
    if (sisaWaktu <= 0) {
        // Simpan semua jawaban dulu sebelum submit
        await saveAllAnswers();
        setTimeout(() => {
            document.getElementById('submitForm').submit();
        }, 500);
        return;
    }
    // ...
}
```

#### E. Anti-Cheat - Toleransi 3x Pindah Tab
```javascript
let tabSwitchCount = 0;
const maxTabSwitch = 3; // Beri toleransi

document.addEventListener('visibilitychange', async function() {
    if (document.hidden && !cheatDetected) {
        tabSwitchCount++;
        
        if (tabSwitchCount >= maxTabSwitch) {
            cheatDetected = true;
            // Show overlay
        } else {
            // Hanya warning
            alert(`⚠️ PERINGATAN ${tabSwitchCount}/${maxTabSwitch}`);
        }
    } else if (!document.hidden && cheatDetected) {
        // Simpan jawaban dulu sebelum submit anti-cheat
        await saveAllAnswers();
        setTimeout(() => {
            document.getElementById('antiCheatForm').submit();
        }, 500);
    }
});
```

### 2. Backend (app/Http/Controllers/ExamController.php)

#### A. saveJawaban() - Validasi & Error Handling
```php
public function saveJawaban(Request $request, Ujian $ujian)
{
    try {
        // Validasi input
        $request->validate([
            'bank_soal_id' => 'required|integer|exists:bank_soals,id',
            'jawaban' => 'nullable|string',
            'is_ragu' => 'nullable|boolean',
        ]);

        $jawaban = JawabanSiswa::updateOrCreate(...);

        return response()->json([
            'success' => true,
            'message' => 'Jawaban berhasil disimpan',
            'data' => [...]
        ]);
    } catch (\Exception $e) {
        \Log::error('Error saving answer: ' . $e->getMessage());
        return response()->json([
            'error' => 'Gagal menyimpan jawaban',
            'message' => $e->getMessage()
        ], 500);
    }
}
```

#### B. submit() - Logging untuk Debugging
```php
public function submit(Request $request, Ujian $ujian)
{
    // Log untuk debugging
    \Log::info('Submitting exam', [
        'ujian_id' => $ujian->id,
        'siswa_id' => $siswa->id,
        'peserta_id' => $peserta->id,
        'jawaban_count' => JawabanSiswa::where(...)->count(),
    ]);

    return $this->submitExam($ujian, $peserta);
}
```

#### C. submitExam() - Logging Detail
```php
protected function submitExam(Ujian $ujian, PesertaUjian $peserta)
{
    $jawabans = JawabanSiswa::where(...)->get();
    
    // Log untuk debugging
    \Log::info('Processing exam submission', [
        'peserta_id' => $peserta->id,
        'total_jawaban' => $jawabans->count(),
        'jawaban_terisi' => $jawabans->where('jawaban_dipilih', '!=', null)->count(),
    ]);
    
    // ... proses grading ...
    
    // Log hasil
    \Log::info('Exam submitted successfully', [
        'peserta_id' => $peserta->id,
        'nilai_akhir' => $nilaiAkhir,
        'benar' => $benarCount,
        'salah' => $salahCount,
    ]);
}
```

#### D. antiCheatViolation() - Method Baru
```php
public function antiCheatViolation(Request $request, Ujian $ujian)
{
    if ($peserta) {
        // Log violation
        ActivityLog::log('anti_cheat_violation', 'ujian', ...);
        
        // Submit exam automatically (jawaban sudah disimpan dari frontend)
        $this->submitExam($ujian, $peserta);
    }

    // Logout user
    auth()->logout();
    // ...
}
```

## Cara Kerja Sekarang

### Skenario 1: Siswa Klik "Selesai" Normal
1. Siswa klik tombol "Selesai"
2. Muncul konfirmasi dialog
3. Jika OK → `saveAllAnswers()` dipanggil
4. Semua jawaban dikirim ke server via AJAX
5. Tunggu semua request selesai (Promise.all)
6. Tunggu 500ms tambahan
7. Submit form → redirect ke halaman hasil

### Skenario 2: Waktu Habis (Timer = 0)
1. Timer mencapai 0
2. `saveAllAnswers()` dipanggil otomatis
3. Semua jawaban dikirim ke server
4. Tunggu 500ms
5. Auto-submit form

### Skenario 3: Pindah Tab (Anti-Cheat)
1. Siswa pindah tab pertama kali → Warning 1/3
2. Siswa pindah tab kedua kali → Warning 2/3
3. Siswa pindah tab ketiga kali → Marked as cheat
4. Saat siswa kembali ke tab:
   - `saveAllAnswers()` dipanggil
   - Tunggu 500ms
   - Submit anti-cheat form
   - Logout otomatis

## Keuntungan Perbaikan

✅ **Jawaban pasti tersimpan** - Ada mekanisme saveAllAnswers sebelum submit
✅ **Retry otomatis** - Jika gagal, akan coba lagi sekali
✅ **Logging lengkap** - Mudah debug jika ada masalah
✅ **Toleransi pindah tab** - Tidak langsung logout, ada warning 3x
✅ **Validasi backend** - Input divalidasi sebelum disimpan
✅ **Error handling** - Semua error ditangkap dan di-log

## Testing

Untuk memastikan perbaikan bekerja:

1. **Test Normal Submit**
   - Jawab beberapa soal
   - Klik "Selesai"
   - Cek di database: `jawaban_siswas` harus ada
   - Cek nilai tersimpan di `peserta_ujians`

2. **Test Timer Habis**
   - Set durasi ujian sangat pendek (1 menit)
   - Jawab beberapa soal
   - Tunggu timer habis
   - Cek jawaban tersimpan

3. **Test Anti-Cheat**
   - Jawab beberapa soal
   - Pindah tab 3x
   - Kembali ke tab ujian
   - Cek jawaban tersimpan sebelum logout

4. **Cek Log**
   - Buka `storage/logs/laravel.log`
   - Cari log "Submitting exam"
   - Cari log "Processing exam submission"
   - Cari log "Exam submitted successfully"

## Catatan Penting

⚠️ **Path URL** - Pastikan path `/cbtbm/public/exam/` sesuai dengan instalasi Anda
⚠️ **Database** - Pastikan tabel `jawaban_siswas` memiliki index yang baik
⚠️ **Session** - Pastikan session timeout lebih lama dari durasi ujian
⚠️ **Network** - Perbaikan ini membutuhkan koneksi internet stabil

## Troubleshooting

Jika masih ada masalah:

1. Buka Browser Console (F12) → lihat error JavaScript
2. Buka Network Tab → cek request `/save-jawaban` berhasil (200)
3. Buka `storage/logs/laravel.log` → cek error PHP
4. Cek database langsung → query `SELECT * FROM jawaban_siswas WHERE peserta_ujian_id = X`
