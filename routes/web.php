<?php

use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KodeKegiatanController;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\PerjalananDinasController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', fn () => redirect()->route('dashboard'));

// Public calendar (no auth required)
Route::get('/kalender', [\App\Http\Controllers\PublicCalendarController::class, 'index'])->name('public.calendar');

Route::get('/login', [AuthController::class, 'showLogin'])
    ->middleware('guest')
    ->name('login');

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('guest')
    ->name('login.post');

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Absensi
    Route::get('/absensi', [AbsensiController::class, 'index'])
        ->name('absensi');
    Route::post('/absensi', [AbsensiController::class, 'store'])
        ->name('absensi.store');
    Route::delete('/absensi', [AbsensiController::class, 'destroy'])
        ->name('absensi.destroy');

    // Perjalanan Dinas
    Route::get('/perjalanan-dinas', [PerjalananDinasController::class, 'index'])
        ->name('perjalanan-dinas');
    Route::post('/perjalanan-dinas', [PerjalananDinasController::class, 'store'])
        ->name('perjalanan-dinas.store');
    Route::delete('/perjalanan-dinas', [PerjalananDinasController::class, 'destroy'])
        ->name('perjalanan-dinas.destroy');

    // Pegawai Management
    Route::get('/pegawai', [PegawaiController::class, 'index'])
        ->middleware('role:super_admin,kepala')
        ->name('pegawai');
    Route::post('/pegawai', [PegawaiController::class, 'store'])
        ->middleware('role:super_admin,kepala')
        ->name('pegawai.store');
    Route::get('/pegawai/export', [PegawaiController::class, 'export'])
        ->middleware('role:super_admin,kepala')
        ->name('pegawai.export');
    Route::get('/pegawai/template', [PegawaiController::class, 'downloadTemplate'])
        ->middleware('role:super_admin,kepala')
        ->name('pegawai.template');
    Route::post('/pegawai/import', [PegawaiController::class, 'import'])
        ->middleware('role:super_admin,kepala')
        ->name('pegawai.import');
    Route::put('/pegawai/{id}', [PegawaiController::class, 'update'])
        ->middleware('role:super_admin,kepala')
        ->name('pegawai.update');
    Route::delete('/pegawai/{id}', [PegawaiController::class, 'destroy'])
        ->middleware('role:super_admin,kepala')
        ->name('pegawai.destroy');
    Route::post('/pegawai/reorder', [PegawaiController::class, 'reorder'])
        ->middleware('role:super_admin,kepala')
        ->name('pegawai.reorder');

    // Master Kegiatan (3 level: Menu → Rincian Menu → Kegiatan)
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/kode-kegiatan', [KodeKegiatanController::class, 'index'])->name('kode-kegiatan');

        // Menu (Level 1)
        Route::post('/kode-kegiatan/menu', [KodeKegiatanController::class, 'storeMenu'])->name('menu.store');
        Route::put('/kode-kegiatan/menu/{id}', [KodeKegiatanController::class, 'updateMenu'])->name('menu.update');
        Route::delete('/kode-kegiatan/menu/{id}', [KodeKegiatanController::class, 'destroyMenu'])->name('menu.destroy');

        // Rincian Menu (Level 2)
        Route::post('/kode-kegiatan/rincian', [KodeKegiatanController::class, 'storeRincian'])->name('rincian.store');
        Route::put('/kode-kegiatan/rincian/{id}', [KodeKegiatanController::class, 'updateRincian'])->name('rincian.update');
        Route::delete('/kode-kegiatan/rincian/{id}', [KodeKegiatanController::class, 'destroyRincian'])->name('rincian.destroy');

        // Kegiatan (Level 3)
        Route::post('/kode-kegiatan/kegiatan', [KodeKegiatanController::class, 'storeKegiatan'])->name('kegiatan.store');
        Route::put('/kode-kegiatan/kegiatan/{id}', [KodeKegiatanController::class, 'updateKegiatan'])->name('kegiatan.update');
        Route::delete('/kode-kegiatan/kegiatan/{id}', [KodeKegiatanController::class, 'destroyKegiatan'])->name('kegiatan.destroy');
    });

    // Settings
    Route::get('/settings', [SettingController::class, 'index'])
        ->middleware('role:super_admin')
        ->name('settings');
    Route::post('/settings', [SettingController::class, 'update'])
        ->middleware('role:super_admin')
        ->name('settings.update');
    Route::post('/settings/jam-kerja', [SettingController::class, 'updateJamKerja'])
        ->middleware('role:super_admin')
        ->name('settings.jam-kerja.update');
    Route::post('/settings/telegram/test', [SettingController::class, 'testTelegram'])
        ->middleware('role:super_admin')
        ->name('settings.telegram.test');
    Route::post('/settings/telegram/backup', [SettingController::class, 'backupNow'])
        ->middleware('role:super_admin')
        ->name('settings.telegram.backup');

    // Tanggal Libur & Info Tanggal Management
    Route::post('/tanggal-libur', [\App\Http\Controllers\TanggalLiburController::class, 'store'])
        ->middleware('role:super_admin,kepala')
        ->name('tanggal-libur.store');
    Route::delete('/tanggal-libur', [\App\Http\Controllers\TanggalLiburController::class, 'destroy'])
        ->middleware('role:super_admin,kepala')
        ->name('tanggal-libur.destroy');
    Route::post('/info-tanggal', [\App\Http\Controllers\TanggalLiburController::class, 'storeInfo'])
        ->middleware('role:super_admin,kepala')
        ->name('info-tanggal.store');
    Route::delete('/info-tanggal', [\App\Http\Controllers\TanggalLiburController::class, 'destroyInfo'])
        ->middleware('role:super_admin,kepala')
        ->name('info-tanggal.destroy');

    // Hasil Absensi (Konversi)
    Route::get('/hasil-absensi', [\App\Http\Controllers\HasilAbsensiController::class, 'index'])
        ->name('hasil-absensi');
});
