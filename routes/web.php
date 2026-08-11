<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BankSoalController;
use App\Http\Controllers\UjianController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ManajemenController;
use App\Http\Controllers\ManajemenSiswaController;
use App\Http\Controllers\ImportSiswaController;
use App\Http\Controllers\ImportBankSoalController;
use App\Http\Controllers\KartuPesertaController;
use App\Http\Controllers\StatusPesertaController;
use App\Http\Controllers\GuruController;
use App\Http\Controllers\AntiCheatController;

/* |-------------------------------------------------------------------------- | Web Routes |-------------------------------------------------------------------------- */

// Auth Routes
Route::get('/', [LoginController::class , 'showLoginForm']);
Route::get('/login', [LoginController::class , 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class , 'login'])->middleware('throttle.custom:5,1')->name('login.process');
Route::post('/logout', [LoginController::class , 'logout'])->name('logout');

// Super Admin Routes
Route::middleware(['auth', 'role:superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class , 'superadmin'])->name('dashboard');
    
    // System Settings (Superadmin only)
    Route::get('/settings', [App\Http\Controllers\SystemSettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [App\Http\Controllers\SystemSettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/delete-image', [App\Http\Controllers\SystemSettingController::class, 'deleteImage'])->name('settings.delete-image');
    Route::post('/settings/reset', [App\Http\Controllers\SystemSettingController::class, 'reset'])->name('settings.reset');
    Route::get('/settings/clear-cache', [App\Http\Controllers\SystemSettingController::class, 'clearCache'])->name('settings.clear-cache');
});

// Admin Routes
Route::middleware(['auth', 'role:superadmin,admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class , 'admin'])->name('dashboard');

    // User Management (non-siswa: superadmin, admin, guru)
    Route::resource('users', UserController::class);
    Route::patch('/users/{user}/toggle-active', [UserController::class , 'toggleActive'])->name('users.toggle-active');

    // Manajemen Siswa
    Route::get('/siswa-manage', [ManajemenSiswaController::class , 'index'])->name('siswa.index');
    Route::get('/siswa-manage/create', [ManajemenSiswaController::class , 'create'])->name('siswa.create');
    Route::post('/siswa-manage', [ManajemenSiswaController::class , 'store'])->name('siswa.store');
    Route::get('/siswa-manage/{siswa}/edit', [ManajemenSiswaController::class , 'edit'])->name('siswa.edit');
    Route::put('/siswa-manage/{siswa}', [ManajemenSiswaController::class , 'update'])->name('siswa.update');
    Route::delete('/siswa-manage/{siswa}', [ManajemenSiswaController::class , 'destroy'])->name('siswa.destroy');
    Route::patch('/siswa-manage/{siswa}/toggle', [ManajemenSiswaController::class , 'toggleActive'])->name('siswa.toggle-active');
    Route::get('/siswa-manage/{user}/credential', [ManajemenSiswaController::class , 'showCredential'])->name('siswa.credential');
    Route::post('/siswa-manage/{user}/reset-password', [ManajemenSiswaController::class , 'resetPassword'])->name('siswa.reset-password');

    // Import Siswa
    Route::get('/import-siswa', [ImportSiswaController::class , 'index'])->name('import-siswa.index');
    Route::post('/import-siswa/preview', [ImportSiswaController::class , 'preview'])->name('import-siswa.preview');
    Route::post('/import-siswa/import', [ImportSiswaController::class , 'import'])->name('import-siswa.import');
    Route::get('/import-siswa/result', [ImportSiswaController::class , 'result'])->name('import-siswa.result');
    Route::get('/import-siswa/template', [ImportSiswaController::class , 'downloadTemplate'])->name('import-siswa.template');
    Route::get('/import-siswa/download-credentials', [ImportSiswaController::class , 'downloadCredentials'])->name('import-siswa.download-credentials');

    // Data Guru
    Route::get('/guru', [GuruController::class , 'index'])->name('guru.index');
    Route::post('/guru', [GuruController::class , 'store'])->name('guru.store');
    Route::put('/guru/{guru}', [GuruController::class , 'update'])->name('guru.update');
    Route::delete('/guru/{guru}', [GuruController::class , 'destroy'])->name('guru.destroy');

    // Import Bank Soal
    Route::get('/import-banksoal', [ImportBankSoalController::class , 'index'])->name('import-banksoal.index');
    Route::get('/import-banksoal/template', [ImportBankSoalController::class , 'downloadTemplate'])->name('import-banksoal.template');
    Route::post('/import-banksoal/preview', [ImportBankSoalController::class , 'preview'])->name('import-banksoal.preview');
    Route::post('/import-banksoal/import', [ImportBankSoalController::class , 'import'])->name('import-banksoal.import');
    Route::get('/import-banksoal/result', [ImportBankSoalController::class , 'result'])->name('import-banksoal.result');

    // Jurusan
    Route::get('/jurusan', [ManajemenController::class , 'jurusanIndex'])->name('jurusan.index');
    Route::post('/jurusan', [ManajemenController::class , 'jurusanStore'])->name('jurusan.store');
    Route::put('/jurusan/{jurusan}', [ManajemenController::class , 'jurusanUpdate'])->name('jurusan.update');
    Route::delete('/jurusan/{jurusan}', [ManajemenController::class , 'jurusanDestroy'])->name('jurusan.destroy');

    // Kelas
    Route::get('/kelas', [ManajemenController::class , 'kelasIndex'])->name('kelas.index');
    Route::post('/kelas', [ManajemenController::class , 'kelasStore'])->name('kelas.store');
    Route::put('/kelas/{kelas}', [ManajemenController::class , 'kelasUpdate'])->name('kelas.update');
    Route::delete('/kelas/{kelas}', [ManajemenController::class , 'kelasDestroy'])->name('kelas.destroy');

    // Mapel
    Route::get('/mapel', [ManajemenController::class , 'mapelIndex'])->name('mapel.index');
    Route::post('/mapel', [ManajemenController::class , 'mapelStore'])->name('mapel.store');
    Route::put('/mapel/{mapel}', [ManajemenController::class , 'mapelUpdate'])->name('mapel.update');
    Route::delete('/mapel/{mapel}', [ManajemenController::class , 'mapelDestroy'])->name('mapel.destroy');

    // Sesi Ujian
    Route::get('/sesi', [ManajemenController::class , 'sesiIndex'])->name('sesi.index');
    Route::post('/sesi', [ManajemenController::class , 'sesiStore'])->name('sesi.store');
    Route::put('/sesi/{sesi}', [ManajemenController::class , 'sesiUpdate'])->name('sesi.update');
    Route::delete('/sesi/{sesi}', [ManajemenController::class , 'sesiDestroy'])->name('sesi.destroy');

    // Anti-Cheat Log
    Route::get('/anti-cheat', [AntiCheatController::class , 'index'])->name('anti-cheat.index');

    // System Settings
    Route::get('/settings', [App\Http\Controllers\SystemSettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [App\Http\Controllers\SystemSettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/delete-image', [App\Http\Controllers\SystemSettingController::class, 'deleteImage'])->name('settings.delete-image');
    Route::post('/settings/reset', [App\Http\Controllers\SystemSettingController::class, 'reset'])->name('settings.reset');
    Route::get('/settings/clear-cache', [App\Http\Controllers\SystemSettingController::class, 'clearCache'])->name('settings.clear-cache');
});

// Guru Routes
Route::middleware(['auth', 'role:superadmin,admin,guru'])->prefix('guru')->name('guru.')->group(function () {
    Route::get('/dashboard', [DashboardController::class , 'guru'])->name('dashboard');
    Route::get('/profil', [GuruController::class , 'profil'])->name('profil');
    Route::put('/profil', [GuruController::class , 'updateProfil'])->name('profil.update');
    Route::put('/profil/password', [GuruController::class , 'updatePassword'])->name('profil.password');
});

// Bank Soal Routes (Admin + Guru)
Route::middleware(['auth', 'role:superadmin,admin,guru'])->group(function () {
    Route::resource('banksoal', BankSoalController::class);
    Route::post('banksoal/bulk-destroy', [BankSoalController::class , 'bulkDestroy'])->name('banksoal.bulk-destroy');
});

// Ujian Routes (Admin only)
Route::middleware(['auth', 'role:superadmin,admin'])->group(function () {
    Route::resource('ujian', UjianController::class);
    Route::patch('/ujian/{ujian}/publish', [UjianController::class , 'publish'])->name('ujian.publish');
    Route::get('/ujian/{ujian}/hasil', [UjianController::class , 'hasil'])->name('ujian.hasil');
    Route::get('/ujian/{ujian}/cetak-nilai', [UjianController::class , 'cetakNilai'])->name('ujian.cetak-nilai');
    Route::get('/ujian/{ujian}/print-nilai', [UjianController::class , 'printNilai'])->name('ujian.print-nilai');
    Route::get('/ujian/{ujian}/monitoring', [UjianController::class , 'monitoring'])->name('ujian.monitoring');

    // Jawaban peserta
    Route::get('/ujian/{ujian}/peserta/{peserta}/jawaban', [UjianController::class , 'showJawaban'])->name('ujian.peserta.jawaban');
    Route::post('/ujian/{ujian}/peserta/{peserta}/nilai', [UjianController::class , 'updateNilai'])->name('ujian.peserta.nilai');
});

// Kartu Peserta Routes (Admin only)
Route::middleware(['auth', 'role:superadmin,admin'])->group(function () {
    Route::get('/kartu-peserta', [KartuPesertaController::class , 'index'])->name('kartu-peserta.index');
    Route::post('/kartu-peserta/settings', [KartuPesertaController::class , 'saveSettings'])->name('kartu-peserta.save-settings');
    Route::get('/kartu-peserta/kelas-by-jurusan', [KartuPesertaController::class , 'kelasByJurusan'])->name('kartu-peserta.kelas-by-jurusan');
    Route::get('/kartu-peserta/print-by-kelas', [KartuPesertaController::class , 'printByKelas'])->name('kartu-peserta.print-by-kelas');
    Route::get('/kartu-peserta/{ujian}/preview', [KartuPesertaController::class , 'preview'])->name('kartu-peserta.preview');
    Route::get('/kartu-peserta/{ujian}/print', [KartuPesertaController::class , 'print'])->name('kartu-peserta.print');
});

// Status Peserta Routes (Admin only)
Route::middleware(['auth', 'role:superadmin,admin'])->group(function () {
    Route::get('/status-peserta', [StatusPesertaController::class , 'index'])->name('status-peserta.index');
    Route::get('/status-peserta/{ujian}', [StatusPesertaController::class , 'show'])->name('status-peserta.show');
});

// Siswa Routes
Route::middleware(['auth', 'role:siswa'])->prefix('siswa')->name('siswa.')->group(function () {
    Route::get('/dashboard', [DashboardController::class , 'siswa'])->name('dashboard');
});

// Exam Routes (Siswa)
Route::middleware(['auth', 'role:siswa'])->prefix('exam')->name('exam.')->group(function () {
    Route::get('/{ujian}/start', [ExamController::class , 'start'])->name('start');
    Route::post('/{ujian}/verify-token', [ExamController::class , 'verifyToken'])->middleware('throttle.custom:10,1')->name('verify-token');
    Route::get('/{ujian}/mengerjakan', [ExamController::class , 'mengerjakan'])->name('mengerjakan');
    Route::post('/{ujian}/save-jawaban', [ExamController::class , 'saveJawaban'])->middleware('throttle.custom:120,1')->name('save-jawaban');
    Route::post('/{ujian}/submit', [ExamController::class , 'submit'])->name('submit');
    Route::get('/{ujian}/result', [ExamController::class , 'result'])->name('result');
    Route::post('/{ujian}/anti-cheat', [ExamController::class , 'antiCheatViolation'])->middleware('throttle.custom:30,1')->name('anti-cheat');
});
