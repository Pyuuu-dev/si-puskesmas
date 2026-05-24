<?php

use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ArsipFolderController;
use App\Http\Controllers\ArsipLinkController;
use App\Http\Controllers\ArsipTagController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KodeKegiatanController;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\PerjalananDinasController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RekapController;
use App\Http\Controllers\RekapManualController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SuratIzinController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', fn () => redirect()->route('dashboard'));

// Public calendar (no auth required)
Route::get('/kalender', [\App\Http\Controllers\PublicCalendarController::class, 'index'])->name('public.calendar');
Route::get('/perjalanan-dinas-publik', [\App\Http\Controllers\PublicCalendarController::class, 'dinas'])->name('public.dinas');

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
    Route::post('/perjalanan-dinas/blokir', [PerjalananDinasController::class, 'blokir'])
        ->middleware('role:super_admin,kepala')
        ->name('perjalanan-dinas.blokir');
    Route::post('/perjalanan-dinas/blokir/hapus', [PerjalananDinasController::class, 'unblokir'])
        ->middleware('role:super_admin,kepala')
        ->name('perjalanan-dinas.unblokir');
    Route::post('/perjalanan-dinas/blokir/hapus-tanggal', [PerjalananDinasController::class, 'unblokirTanggal'])
        ->middleware('role:super_admin,kepala')
        ->name('perjalanan-dinas.unblokir-tanggal');
    Route::delete('/perjalanan-dinas/blokir', [PerjalananDinasController::class, 'unblokir'])
        ->middleware('role:super_admin,kepala')
        ->name('perjalanan-dinas.unblokir-delete');
    Route::post('/perjalanan-dinas/spj', [PerjalananDinasController::class, 'toggleSpj'])
        ->middleware('role:super_admin,kepala')
        ->name('perjalanan-dinas.spj');
    Route::post('/perjalanan-dinas/kepala-keterangan', [PerjalananDinasController::class, 'updateKepalaKeterangan'])
        ->middleware('role:super_admin,kepala')
        ->name('perjalanan-dinas.kepala-keterangan');

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

        // Lihat pemakai kode (siapa saja yang pakai kode ini)
        Route::get('/kode-kegiatan/kegiatan/{id}/pemakai', [KodeKegiatanController::class, 'pemakai'])->name('kegiatan.pemakai');
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
    Route::post('/tanggal-libur/delete', [\App\Http\Controllers\TanggalLiburController::class, 'destroyByTanggal'])
        ->middleware('role:super_admin,kepala')
        ->name('tanggal-libur.delete');
    Route::delete('/tanggal-libur/{id?}', [\App\Http\Controllers\TanggalLiburController::class, 'destroy'])
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

    // Rekap TL & PSW (hidden for now)
    Route::get('/rekap', [RekapController::class, 'index'])
        ->name('rekap.index');
    Route::get('/rekap/export', [RekapController::class, 'export'])
        ->name('rekap.export');
    Route::post('/rekap/config', [RekapController::class, 'updateConfig'])
        ->middleware('role:super_admin')
        ->name('rekap.config.update');

    // Rekap Absensi (dedicated page)
    Route::get('/rekap-absensi', [RekapController::class, 'absensi'])
        ->name('rekap.absensi');
    Route::get('/rekap-absensi/download', [RekapController::class, 'exportExcel'])
        ->name('rekap.export-excel');

    // Rekap Manual (upload arsip rekap absen per bulan)
    Route::get('/rekap-manual', [RekapManualController::class, 'index'])
        ->name('rekap-manual.index');
    Route::get('/rekap-manual/{id}/view', [RekapManualController::class, 'view'])
        ->name('rekap-manual.view');
    Route::get('/rekap-manual/{id}/download', [RekapManualController::class, 'download'])
        ->name('rekap-manual.download');
    Route::post('/rekap-manual', [RekapManualController::class, 'store'])
        ->middleware('role:super_admin,kepala')
        ->name('rekap-manual.store');
    Route::delete('/rekap-manual/{id}', [RekapManualController::class, 'destroy'])
        ->middleware('role:super_admin,kepala')
        ->name('rekap-manual.destroy');

    // Surat Izin & Sakit (dokumen pendukung absensi)
    Route::get('/surat-izin', [SuratIzinController::class, 'index'])
        ->name('surat-izin.index');
    Route::get('/surat-izin/{id}/view', [SuratIzinController::class, 'view'])
        ->name('surat-izin.view');
    Route::get('/surat-izin/{id}/download', [SuratIzinController::class, 'download'])
        ->name('surat-izin.download');
    Route::post('/surat-izin', [SuratIzinController::class, 'store'])
        ->middleware('role:super_admin,kepala')
        ->name('surat-izin.store');
    Route::delete('/surat-izin/{id}', [SuratIzinController::class, 'destroy'])
        ->middleware('role:super_admin,kepala')
        ->name('surat-izin.destroy');

    // Log Aktivitas (super_admin only)
    Route::get('/log-aktivitas', [ActivityLogController::class, 'index'])
        ->middleware('role:super_admin')
        ->name('log-aktivitas.index');
    Route::get('/log-aktivitas/{id}', [ActivityLogController::class, 'show'])
        ->middleware('role:super_admin')
        ->name('log-aktivitas.show');
    Route::post('/log-aktivitas/prune', [ActivityLogController::class, 'prune'])
        ->middleware('role:super_admin')
        ->name('log-aktivitas.prune');

    /*
    |--------------------------------------------------------------------------
    | Arsip Link / Bookmark Manager
    |--------------------------------------------------------------------------
    | Library link bersama institusi. View untuk semua user login;
    | CUD untuk super_admin & kepala. Track open boleh untuk semua.
    */
    Route::prefix('arsip')->name('arsip.')->group(function () {

        // View & navigasi (semua user login)
        Route::get('/',                [ArsipFolderController::class, 'index'])->name('index');
        Route::get('/folder/{folder}', [ArsipFolderController::class, 'index'])->name('folder');
        Route::get('/search',          [ArsipLinkController::class, 'search'])->name('search');
        Route::get('/link/{link}/go',  [ArsipLinkController::class, 'go'])->name('link.go');

        // Aksi level user (toggle favorite untuk semua)
        Route::post('/link/{link}/favorite', [ArsipLinkController::class, 'toggleFavorite'])
            ->name('link.favorite');

        // Admin-only (super_admin / kepala)
        Route::middleware('role:super_admin,kepala')->group(function () {
            // Folder
            Route::post('/folder',            [ArsipFolderController::class, 'store'])->name('folder.store');
            Route::put('/folder/{folder}',    [ArsipFolderController::class, 'update'])->name('folder.update');
            Route::delete('/folder/{folder}', [ArsipFolderController::class, 'destroy'])->name('folder.destroy');
            Route::post('/folder/move',       [ArsipFolderController::class, 'move'])->name('folder.move');

            // Link
            Route::post('/link',                [ArsipLinkController::class, 'store'])->name('link.store');
            Route::put('/link/{link}',          [ArsipLinkController::class, 'update'])->name('link.update');
            Route::delete('/link/{link}',       [ArsipLinkController::class, 'destroy'])->name('link.destroy');
            Route::post('/link/{link}/pin',     [ArsipLinkController::class, 'togglePin'])->name('link.pin');
            Route::post('/link/{link}/refetch', [ArsipLinkController::class, 'refetch'])->name('link.refetch');

            // Tag
            Route::delete('/tag/{tag}', [ArsipTagController::class, 'destroy'])->name('tag.destroy');
        });
    });
});
