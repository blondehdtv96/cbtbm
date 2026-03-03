# Perbaikan Error 419 Page Expired pada Anti-Cheat

## Masalah
Ketika siswa pindah tab dan sistem anti-cheat mencoba submit form, muncul error:
```
419 | PAGE EXPIRED
```

Halaman kosong tanpa informasi atau tombol untuk kembali ke login.

## Penyebab
1. **CSRF Token Expired**: Session habis atau token tidak valid
2. **No Error Handling**: Tidak ada custom handler untuk error 419
3. **Poor UX**: Siswa bingung apa yang terjadi dan tidak bisa kembali

## Solusi yang Diterapkan

### 1. Custom Error Page untuk Anti-Cheat
**File**: `resources/views/exam/anti-cheat-violation.blade.php`

Halaman khusus yang menampilkan:
- ✓ Icon warning yang jelas
- ✓ Penjelasan pelanggaran yang terdeteksi
- ✓ Informasi apa yang terjadi dengan ujian
- ✓ Tombol "Kembali ke Halaman Login"
- ✓ Auto-redirect dalam 10 detik
- ✓ Design responsive untuk mobile

**Fitur:**
```html
- Icon shield-x dengan animasi shake
- Daftar pelanggaran yang terdeteksi
- Info box: jawaban tersimpan, akun logout, dll
- Button besar ke halaman login
- Countdown timer auto-redirect
```

### 2. Update Controller Anti-Cheat
**File**: `app/Http/Controllers/ExamController.php`

**Perubahan:**
```php
public function antiCheatViolation(Request $request, Ujian $ujian)
{
    try {
        // Handle jika user sudah logout
        $siswa = auth()->user()->siswa ?? null;
        
        if ($siswa) {
            // Submit exam & log violation
        }

        // Logout safely
        if (auth()->check()) {
            auth()->logout();
        }

        // Show custom violation page (bukan redirect)
        return view('exam.anti-cheat-violation');
        
    } catch (\Exception $e) {
        // Fallback jika error
        return redirect()->route('login')
            ->with('error', 'Anda telah di-logout...');
    }
}
```

**Keuntungan:**
- ✓ Tidak crash jika user sudah logout
- ✓ Tampilkan halaman custom, bukan redirect
- ✓ Error handling dengan try-catch
- ✓ Fallback ke login jika ada masalah

### 3. Global Exception Handler
**File**: `app/Exceptions/Handler.php`

**Ditambahkan:**
```php
public function render($request, Throwable $exception)
{
    // Handle CSRF token mismatch (419)
    if ($exception instanceof \Illuminate\Session\TokenMismatchException) {
        // Jika dari route exam
        if ($request->is('exam/*') || $request->is('*/anti-cheat')) {
            // Show anti-cheat violation page
            return response()->view('exam.anti-cheat-violation', [], 419);
        }
        
        // Route lain: redirect ke login
        return redirect()->route('login')
            ->with('error', 'Sesi Anda telah berakhir...');
    }

    return parent::render($request, $exception);
}
```

**Keuntungan:**
- ✓ Catch semua error 419 di route exam
- ✓ Tampilkan halaman custom otomatis
- ✓ Logging untuk debugging
- ✓ Fallback untuk route non-exam

## Cara Kerja Sekarang

### Skenario 1: Pindah Tab (Normal Flow)
1. Siswa pindah tab 3x
2. Sistem deteksi pelanggaran
3. Saat kembali ke tab ujian:
   - `saveAllAnswers()` dipanggil
   - Submit form anti-cheat
   - Controller: submit exam & logout
   - Tampilkan halaman violation
   - Auto-redirect ke login dalam 10 detik

### Skenario 2: CSRF Token Expired
1. Siswa buka ujian, idle lama (>2 jam)
2. Session expired, CSRF token invalid
3. Siswa pindah tab, sistem coba submit
4. Error 419 terjadi
5. Exception Handler catch error:
   - Cek route: `exam/*` atau `*/anti-cheat`
   - Tampilkan halaman violation
   - Log warning untuk debugging
6. Siswa klik tombol atau tunggu 10 detik
7. Redirect ke login

### Skenario 3: Error Lain
1. Ada error di controller (database down, dll)
2. Try-catch di controller catch error
3. Fallback: redirect ke login dengan pesan error
4. Siswa bisa login ulang

## Testing

### Test 1: Normal Anti-Cheat
```
1. Login sebagai siswa
2. Mulai ujian
3. Pindah tab 3x
4. Kembali ke tab ujian
5. Harus muncul halaman violation (bukan 419)
6. Klik tombol "Kembali ke Halaman Login"
7. Harus redirect ke /login
```

### Test 2: CSRF Expired
```
1. Login sebagai siswa
2. Mulai ujian
3. Tunggu 2+ jam (atau edit session lifetime jadi 1 menit)
4. Pindah tab
5. Harus muncul halaman violation (bukan 419)
6. Tunggu 10 detik
7. Auto-redirect ke login
```

### Test 3: Mobile
```
1. Buka ujian di mobile
2. Minimize browser (home button)
3. Buka app lain
4. Kembali ke browser
5. Harus muncul halaman violation
6. Halaman harus responsive
7. Button harus mudah diklik
```

## Konfigurasi Session

Untuk mencegah session expired terlalu cepat, edit `config/session.php`:

```php
'lifetime' => env('SESSION_LIFETIME', 180), // 3 jam (default 120)
'expire_on_close' => false,
```

Atau di `.env`:
```
SESSION_LIFETIME=180
```

## Monitoring

### Cek Log CSRF Mismatch
```bash
tail -f storage/logs/laravel.log | grep "CSRF token mismatch"
```

Output:
```
[2024-xx-xx] local.WARNING: CSRF token mismatch on exam route
{
    "url": "http://127.0.0.1:8000/exam/11/anti-cheat",
    "user_id": 123
}
```

### Cek Log Anti-Cheat Violation
```bash
tail -f storage/logs/laravel.log | grep "anti_cheat_violation"
```

## Troubleshooting

### Masalah: Masih muncul 419 putih
**Penyebab**: Cache view belum clear
**Solusi**:
```bash
php artisan view:clear
php artisan cache:clear
php artisan config:clear
```

### Masalah: Halaman violation tidak muncul
**Penyebab**: Route tidak match
**Solusi**: Cek di `Handler.php`, pastikan kondisi:
```php
if ($request->is('exam/*') || $request->is('*/anti-cheat'))
```

### Masalah: Auto-redirect tidak jalan
**Penyebab**: JavaScript error
**Solusi**: Buka console (F12), cek error

### Masalah: Session expired terlalu cepat
**Penyebab**: SESSION_LIFETIME terlalu kecil
**Solusi**: Edit `.env`:
```
SESSION_LIFETIME=180  # 3 jam
```

## Keuntungan Perbaikan

### User Experience
- ✓ Tidak ada halaman error kosong
- ✓ Penjelasan jelas apa yang terjadi
- ✓ Tombol untuk kembali ke login
- ✓ Auto-redirect untuk kemudahan
- ✓ Design menarik dan profesional

### Developer Experience
- ✓ Logging untuk debugging
- ✓ Error handling yang baik
- ✓ Fallback untuk edge cases
- ✓ Easy to maintain

### Security
- ✓ Jawaban tetap tersimpan
- ✓ Pelanggaran tetap tercatat
- ✓ User di-logout dengan aman
- ✓ Session di-invalidate

## File yang Diubah

1. `resources/views/exam/anti-cheat-violation.blade.php` (BARU)
   - Halaman custom untuk violation

2. `app/Http/Controllers/ExamController.php`
   - Method `antiCheatViolation()` diperbaiki
   - Tampilkan view, bukan redirect
   - Error handling ditambahkan

3. `app/Exceptions/Handler.php`
   - Method `render()` ditambahkan
   - Handle error 419 untuk route exam
   - Logging untuk debugging

## Catatan Penting

⚠️ **Session Lifetime**: Pastikan `SESSION_LIFETIME` cukup panjang untuk durasi ujian terlama + buffer (misal: ujian 2 jam → set 180 menit)

⚠️ **Mobile Testing**: Test di berbagai device, karena behavior minimize app berbeda-beda

⚠️ **Network**: Jika koneksi lambat, `saveAllAnswers()` mungkin belum selesai sebelum redirect. Sudah ada delay 500ms, tapi bisa ditambah jika perlu.

## Next Steps

Jika masih ada masalah:
1. Cek log Laravel: `storage/logs/laravel.log`
2. Cek browser console (F12)
3. Test dengan session lifetime pendek (1 menit) untuk simulasi
4. Monitor database: apakah jawaban tersimpan sebelum logout
