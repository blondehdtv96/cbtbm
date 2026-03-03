# Cara Mengaktifkan/Menonaktifkan Anti-Cheat

## Status Saat Ini
✓ Anti-cheat DINONAKTIFKAN untuk debugging

## Fitur yang Dinonaktifkan

1. **Tab Switch Detection** - Tidak ada warning saat pindah tab
2. **Right-click Prevention** - Bisa klik kanan
3. **Copy Prevention** - Bisa copy text
4. **Keyboard Shortcuts** - F12, Ctrl+C, dll bisa digunakan
5. **Back Navigation Prevention** - Bisa tekan tombol back
6. **Drag Prevention** - Bisa drag element

## Cara Mengaktifkan Kembali

### File: `resources/views/exam/mengerjakan.blade.php`

### Langkah 1: Aktifkan CSS Overlay

Cari baris (sekitar line 45):
```css
/* Anti-cheat blur overlay - DISABLED FOR DEBUGGING */
.cheat-overlay {
    display: none !important; /* Force hide for debugging */
```

Ubah menjadi:
```css
/* Anti-cheat blur overlay */
.cheat-overlay {
    display: none;
```

### Langkah 2: Aktifkan JavaScript

Cari baris (sekitar line 690):
```javascript
// =============================================
// ANTI-CHEAT SYSTEM - TEMPORARILY DISABLED FOR DEBUGGING
// =============================================

console.log('⚠️ ANTI-CHEAT SYSTEM DISABLED FOR DEBUGGING');

/*
// Tab switch / Home screen detection
```

**Hapus:**
- Baris `console.log('⚠️ ANTI-CHEAT SYSTEM DISABLED FOR DEBUGGING');`
- Tanda `/*` di awal comment
- Tanda `*/` di akhir comment (sekitar line 750)

**Hasil akhir:**
```javascript
// =============================================
// ANTI-CHEAT SYSTEM
// =============================================

// Tab switch / Home screen detection
let tabSwitchCount = 0;
const maxTabSwitch = 3;

document.addEventListener('visibilitychange', async function() {
    // ... kode anti-cheat ...
});

// 3. Prevent right-click
document.addEventListener('contextmenu', (e) => e.preventDefault());

// 4. Prevent copy
document.addEventListener('copy', (e) => e.preventDefault());

// ... dst ...
```

### Langkah 3: Clear Cache

```bash
php artisan view:clear
php artisan cache:clear
```

### Langkah 4: Test

1. Buka ujian
2. Cek console: Tidak ada log "ANTI-CHEAT SYSTEM DISABLED"
3. Pindah tab 3x
4. Harus muncul warning dan overlay

## Cara Menonaktifkan (Untuk Debugging)

### Langkah 1: Nonaktifkan CSS

Tambahkan `!important` di CSS:
```css
.cheat-overlay {
    display: none !important; /* Force hide for debugging */
```

### Langkah 2: Comment JavaScript

Tambahkan `/*` sebelum kode anti-cheat dan `*/` setelahnya:
```javascript
console.log('⚠️ ANTI-CHEAT SYSTEM DISABLED FOR DEBUGGING');

/*
// Tab switch / Home screen detection
let tabSwitchCount = 0;
// ... semua kode anti-cheat ...
*/
```

### Langkah 3: Clear Cache

```bash
php artisan view:clear
php artisan cache:clear
```

## Konfigurasi Anti-Cheat

### Ubah Toleransi Pindah Tab

Cari baris:
```javascript
const maxTabSwitch = 3; // Beri toleransi 3x pindah tab
```

Ubah angka 3 sesuai kebutuhan:
- `1` = Langsung logout saat pindah tab pertama kali
- `3` = Toleransi 3x (default)
- `5` = Toleransi 5x (lebih longgar)
- `999` = Hampir tidak ada batasan (untuk testing)

### Nonaktifkan Fitur Tertentu

Untuk menonaktifkan fitur tertentu saja, comment bagian yang diinginkan:

**Nonaktifkan Right-click Prevention:**
```javascript
// document.addEventListener('contextmenu', (e) => e.preventDefault());
```

**Nonaktifkan Copy Prevention:**
```javascript
// document.addEventListener('copy', (e) => e.preventDefault());
```

**Nonaktifkan Keyboard Shortcuts:**
```javascript
/*
document.addEventListener('keydown', function(e) {
    // ... kode block keyboard ...
});
*/
```

**Nonaktifkan Back Navigation:**
```javascript
/*
history.pushState(null, null, location.href);
window.onpopstate = function() {
    history.go(1);
};
*/
```

## Testing Anti-Cheat

### Test 1: Tab Switch
1. Buka ujian
2. Pindah ke tab lain
3. Harus muncul alert warning
4. Pindah 3x total
5. Harus muncul overlay merah
6. Kembali ke tab ujian
7. Harus auto-submit dan logout

### Test 2: Right-click
1. Buka ujian
2. Klik kanan di halaman
3. Menu context tidak boleh muncul

### Test 3: Copy
1. Buka ujian
2. Select text soal
3. Tekan Ctrl+C
4. Tidak boleh bisa copy

### Test 4: F12
1. Buka ujian
2. Tekan F12
3. DevTools tidak boleh terbuka

### Test 5: Back Button
1. Buka ujian
2. Tekan tombol back browser
3. Tidak boleh keluar dari halaman ujian

## Monitoring Anti-Cheat

### Cek Log Violation

```bash
tail -f storage/logs/laravel.log | grep "anti_cheat_violation"
```

### Cek Database

```sql
SELECT * FROM activity_logs 
WHERE action = 'anti_cheat_violation' 
ORDER BY created_at DESC 
LIMIT 10;
```

### Cek Halaman Admin

Buka: `/admin/anti-cheat`

Akan menampilkan semua pelanggaran yang tercatat.

## Troubleshooting

### Anti-cheat tidak jalan setelah diaktifkan

**Penyebab:** Cache view belum clear

**Solusi:**
```bash
php artisan view:clear
php artisan cache:clear
php artisan config:clear

# Restart browser
# Hard refresh: Ctrl+Shift+R
```

### Overlay tidak muncul

**Penyebab:** CSS masih ada `!important`

**Solusi:** Hapus `!important` dari CSS

### Warning tidak muncul

**Penyebab:** JavaScript masih di-comment

**Solusi:** Uncomment kode JavaScript

### F12 masih bisa dibuka

**Penyebab:** Browser sudah buka DevTools sebelum ujian dimulai

**Solusi:** Close DevTools, refresh halaman

## Rekomendasi

### Untuk Production (Ujian Sebenarnya)
- ✓ Aktifkan semua fitur anti-cheat
- ✓ Set `maxTabSwitch = 1` atau `2` (ketat)
- ✓ Monitor log secara real-time
- ✓ Briefing siswa tentang aturan

### Untuk Development/Testing
- ✓ Nonaktifkan anti-cheat
- ✓ Atau set `maxTabSwitch = 999` (longgar)
- ✓ Bisa buka DevTools untuk debugging
- ✓ Bisa copy-paste untuk testing

### Untuk Demo/Training
- ✓ Set `maxTabSwitch = 5` (longgar)
- ✓ Aktifkan warning tapi tidak auto-logout
- ✓ Biarkan siswa familiar dengan sistem

## Catatan Penting

⚠️ **Jangan lupa aktifkan kembali sebelum ujian sebenarnya!**

⚠️ **Test anti-cheat di berbagai browser:**
- Chrome
- Firefox
- Edge
- Safari (Mac)
- Mobile browsers

⚠️ **Backup file sebelum edit:**
```bash
cp resources/views/exam/mengerjakan.blade.php resources/views/exam/mengerjakan.blade.php.backup
```

⚠️ **Dokumentasikan perubahan:**
- Catat tanggal nonaktifkan
- Catat alasan
- Catat kapan harus diaktifkan kembali

## Checklist Sebelum Ujian

- [ ] Anti-cheat sudah diaktifkan
- [ ] Cache sudah di-clear
- [ ] Test di browser siswa
- [ ] Test di mobile
- [ ] Monitoring log siap
- [ ] Briefing siswa sudah dilakukan
- [ ] Backup database sudah dibuat
- [ ] Koneksi internet stabil
- [ ] Server tidak overload

## File Terkait

- `resources/views/exam/mengerjakan.blade.php` - Main file
- `app/Http/Controllers/ExamController.php` - Backend handler
- `resources/views/exam/anti-cheat-violation.blade.php` - Violation page
- `app/Exceptions/Handler.php` - Error handler
- `storage/logs/laravel.log` - Log file
