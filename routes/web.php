<?php

use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ArsipFolderController;
use App\Http\Controllers\ArsipLinkController;
use App\Http\Controllers\ArsipTagController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TidakApelController;
use App\Http\Controllers\KodeKegiatanController;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\PerjalananDinasController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RekapController;
use App\Http\Controllers\RekapManualController;
use App\Http\Controllers\RoleController;
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
| Authenticated Routes (RBAC dinamic via permission middleware)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    // Tidak Apel detail
    Route::get('/tidak-apel', [TidakApelController::class, 'index'])
        ->middleware('permission:dashboard.view')
        ->name('tidak-apel');

    // Profile (semua user login)
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Absensi
    Route::get('/absensi', [AbsensiController::class, 'index'])
        ->middleware('permission:absensi.view')
        ->name('absensi');
    Route::post('/absensi', [AbsensiController::class, 'store'])
        ->middleware('permission:absensi.create')
        ->name('absensi.store');
    Route::delete('/absensi', [AbsensiController::class, 'destroy'])
        ->middleware('permission:absensi.delete')
        ->name('absensi.destroy');

    // Perjalanan Dinas
    Route::get('/perjalanan-dinas', [PerjalananDinasController::class, 'index'])
        ->middleware('permission:perjalanan-dinas.view')
        ->name('perjalanan-dinas');
    Route::get('/perjalanan-dinas/cetak', [PerjalananDinasController::class, 'cetak'])
        ->middleware('permission:perjalanan-dinas.view')
        ->name('perjalanan-dinas.cetak');
    Route::post('/perjalanan-dinas', [PerjalananDinasController::class, 'store'])
        ->middleware('permission:perjalanan-dinas.create')
        ->name('perjalanan-dinas.store');
    Route::delete('/perjalanan-dinas', [PerjalananDinasController::class, 'destroy'])
        ->middleware('permission:perjalanan-dinas.delete')
        ->name('perjalanan-dinas.destroy');
    Route::post('/perjalanan-dinas/blokir', [PerjalananDinasController::class, 'blokir'])
        ->middleware('permission:perjalanan-dinas.blokir')
        ->name('perjalanan-dinas.blokir');
    Route::post('/perjalanan-dinas/blokir/hapus', [PerjalananDinasController::class, 'unblokir'])
        ->middleware('permission:perjalanan-dinas.blokir')
        ->name('perjalanan-dinas.unblokir');
    Route::post('/perjalanan-dinas/blokir/hapus-tanggal', [PerjalananDinasController::class, 'unblokirTanggal'])
        ->middleware('permission:perjalanan-dinas.blokir')
        ->name('perjalanan-dinas.unblokir-tanggal');
    Route::delete('/perjalanan-dinas/blokir', [PerjalananDinasController::class, 'unblokir'])
        ->middleware('permission:perjalanan-dinas.blokir')
        ->name('perjalanan-dinas.unblokir-delete');
    Route::post('/perjalanan-dinas/spj', [PerjalananDinasController::class, 'toggleSpj'])
        ->middleware('permission:perjalanan-dinas.spj')
        ->name('perjalanan-dinas.spj');
    Route::post('/perjalanan-dinas/kepala-keterangan', [PerjalananDinasController::class, 'updateKepalaKeterangan'])
        ->middleware('permission:perjalanan-dinas.kepala-keterangan')
        ->name('perjalanan-dinas.kepala-keterangan');

    // Pegawai Management
    Route::get('/pegawai', [PegawaiController::class, 'index'])
        ->middleware('permission:pegawai.view')
        ->name('pegawai');
    Route::post('/pegawai', [PegawaiController::class, 'store'])
        ->middleware('permission:pegawai.create')
        ->name('pegawai.store');
    Route::get('/pegawai/export', [PegawaiController::class, 'export'])
        ->middleware('permission:pegawai.export')
        ->name('pegawai.export');
    Route::get('/pegawai/template', [PegawaiController::class, 'downloadTemplate'])
        ->middleware('permission:pegawai.import')
        ->name('pegawai.template');
    Route::post('/pegawai/import', [PegawaiController::class, 'import'])
        ->middleware('permission:pegawai.import')
        ->name('pegawai.import');
    Route::put('/pegawai/{id}', [PegawaiController::class, 'update'])
        ->middleware('permission:pegawai.update')
        ->name('pegawai.update');
    Route::delete('/pegawai/{id}', [PegawaiController::class, 'destroy'])
        ->middleware('permission:pegawai.delete')
        ->name('pegawai.destroy');
    Route::post('/pegawai/reorder', [PegawaiController::class, 'reorder'])
        ->middleware('permission:pegawai.update')
        ->name('pegawai.reorder');

    // Master Kegiatan (3 level: Menu → Rincian Menu → Kegiatan)
    Route::get('/kode-kegiatan', [KodeKegiatanController::class, 'index'])
        ->middleware('permission:kode-kegiatan.view')
        ->name('kode-kegiatan');

    // Menu (Level 1)
    Route::post('/kode-kegiatan/menu', [KodeKegiatanController::class, 'storeMenu'])
        ->middleware('permission:kode-kegiatan.create')
        ->name('menu.store');
    Route::put('/kode-kegiatan/menu/{id}', [KodeKegiatanController::class, 'updateMenu'])
        ->middleware('permission:kode-kegiatan.update')
        ->name('menu.update');
    Route::delete('/kode-kegiatan/menu/{id}', [KodeKegiatanController::class, 'destroyMenu'])
        ->middleware('permission:kode-kegiatan.delete')
        ->name('menu.destroy');

    // Rincian Menu (Level 2)
    Route::post('/kode-kegiatan/rincian', [KodeKegiatanController::class, 'storeRincian'])
        ->middleware('permission:kode-kegiatan.create')
        ->name('rincian.store');
    Route::put('/kode-kegiatan/rincian/{id}', [KodeKegiatanController::class, 'updateRincian'])
        ->middleware('permission:kode-kegiatan.update')
        ->name('rincian.update');
    Route::delete('/kode-kegiatan/rincian/{id}', [KodeKegiatanController::class, 'destroyRincian'])
        ->middleware('permission:kode-kegiatan.delete')
        ->name('rincian.destroy');

    // Kegiatan (Level 3)
    Route::post('/kode-kegiatan/kegiatan', [KodeKegiatanController::class, 'storeKegiatan'])
        ->middleware('permission:kode-kegiatan.create')
        ->name('kegiatan.store');
    Route::put('/kode-kegiatan/kegiatan/{id}', [KodeKegiatanController::class, 'updateKegiatan'])
        ->middleware('permission:kode-kegiatan.update')
        ->name('kegiatan.update');
    Route::delete('/kode-kegiatan/kegiatan/{id}', [KodeKegiatanController::class, 'destroyKegiatan'])
        ->middleware('permission:kode-kegiatan.delete')
        ->name('kegiatan.destroy');

    // Lihat pemakai kode (siapa saja yang pakai kode ini)
    Route::get('/kode-kegiatan/kegiatan/{id}/pemakai', [KodeKegiatanController::class, 'pemakai'])
        ->middleware('permission:kode-kegiatan.view')
        ->name('kegiatan.pemakai');

    // Settings
    Route::get('/settings', [SettingController::class, 'index'])
        ->middleware('permission:settings.view')
        ->name('settings');
    Route::post('/settings', [SettingController::class, 'update'])
        ->middleware('permission:settings.update')
        ->name('settings.update');
    Route::post('/settings/jam-kerja', [SettingController::class, 'updateJamKerja'])
        ->middleware('permission:settings.update')
        ->name('settings.jam-kerja.update');
    Route::post('/settings/telegram/test', [SettingController::class, 'testTelegram'])
        ->middleware('permission:settings.telegram')
        ->name('settings.telegram.test');
    Route::post('/settings/telegram/backup', [SettingController::class, 'backupNow'])
        ->middleware('permission:settings.telegram')
        ->name('settings.telegram.backup');

    // Tanggal Libur & Info Tanggal Management
    Route::post('/tanggal-libur', [\App\Http\Controllers\TanggalLiburController::class, 'store'])
        ->middleware('permission:tanggal-libur.create')
        ->name('tanggal-libur.store');
    Route::post('/tanggal-libur/delete', [\App\Http\Controllers\TanggalLiburController::class, 'destroyByTanggal'])
        ->middleware('permission:tanggal-libur.delete')
        ->name('tanggal-libur.delete');
    Route::delete('/tanggal-libur/{id?}', [\App\Http\Controllers\TanggalLiburController::class, 'destroy'])
        ->middleware('permission:tanggal-libur.delete')
        ->name('tanggal-libur.destroy');
    Route::post('/info-tanggal', [\App\Http\Controllers\TanggalLiburController::class, 'storeInfo'])
        ->middleware('permission:tanggal-libur.create')
        ->name('info-tanggal.store');
    Route::put('/info-tanggal', [\App\Http\Controllers\TanggalLiburController::class, 'updateInfo'])
        ->middleware('permission:tanggal-libur.create')
        ->name('info-tanggal.update');
    Route::delete('/info-tanggal', [\App\Http\Controllers\TanggalLiburController::class, 'destroyInfo'])
        ->middleware('permission:tanggal-libur.delete')
        ->name('info-tanggal.destroy');

    // Hasil Absensi (Konversi)
    Route::get('/hasil-absensi', [\App\Http\Controllers\HasilAbsensiController::class, 'index'])
        ->middleware('permission:hasil-absensi.view')
        ->name('hasil-absensi');

    // Rekap TL & PSW (hidden for now)
    Route::get('/rekap', [RekapController::class, 'index'])
        ->middleware('permission:rekap.view')
        ->name('rekap.index');
    Route::get('/rekap/export', [RekapController::class, 'export'])
        ->middleware('permission:rekap.export')
        ->name('rekap.export');
    Route::post('/rekap/config', [RekapController::class, 'updateConfig'])
        ->middleware('permission:rekap.config.update')
        ->name('rekap.config.update');

    // Rekap Absensi (dedicated page)
    Route::get('/rekap-absensi', [RekapController::class, 'absensi'])
        ->middleware('permission:rekap.view')
        ->name('rekap.absensi');
    Route::get('/rekap-absensi/download', [RekapController::class, 'exportExcel'])
        ->middleware('permission:rekap.export')
        ->name('rekap.export-excel');

    // Rekap Manual (upload arsip rekap absen per bulan)
    Route::get('/rekap-manual', [RekapManualController::class, 'index'])
        ->middleware('permission:rekap-manual.view')
        ->name('rekap-manual.index');
    Route::get('/rekap-manual/{id}/view', [RekapManualController::class, 'view'])
        ->middleware('permission:rekap-manual.view')
        ->name('rekap-manual.view');
    Route::get('/rekap-manual/{id}/download', [RekapManualController::class, 'download'])
        ->middleware('permission:rekap-manual.view')
        ->name('rekap-manual.download');
    Route::post('/rekap-manual', [RekapManualController::class, 'store'])
        ->middleware('permission:rekap-manual.create')
        ->name('rekap-manual.store');
    Route::delete('/rekap-manual/{id}', [RekapManualController::class, 'destroy'])
        ->middleware('permission:rekap-manual.delete')
        ->name('rekap-manual.destroy');

    // Surat Izin & Sakit (dokumen pendukung absensi)
    Route::get('/surat-izin', [SuratIzinController::class, 'index'])
        ->middleware('permission:surat-izin.view')
        ->name('surat-izin.index');
    Route::get('/surat-izin/{id}/view', [SuratIzinController::class, 'view'])
        ->middleware('permission:surat-izin.view')
        ->name('surat-izin.view');
    Route::get('/surat-izin/{id}/download', [SuratIzinController::class, 'download'])
        ->middleware('permission:surat-izin.view')
        ->name('surat-izin.download');
    Route::post('/surat-izin', [SuratIzinController::class, 'store'])
        ->middleware('permission:surat-izin.create')
        ->name('surat-izin.store');
    Route::delete('/surat-izin/{id}', [SuratIzinController::class, 'destroy'])
        ->middleware('permission:surat-izin.delete')
        ->name('surat-izin.destroy');

    // Log Aktivitas
    Route::get('/log-aktivitas', [ActivityLogController::class, 'index'])
        ->middleware('permission:log-aktivitas.view')
        ->name('log-aktivitas.index');
    Route::get('/log-aktivitas/{id}', [ActivityLogController::class, 'show'])
        ->middleware('permission:log-aktivitas.view')
        ->name('log-aktivitas.show');
    Route::post('/log-aktivitas/prune', [ActivityLogController::class, 'prune'])
        ->middleware('permission:log-aktivitas.prune')
        ->name('log-aktivitas.prune');

    // Manajemen Role & Permission (RBAC)
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/',                 [RoleController::class, 'index'])
            ->middleware('permission:roles.view')->name('index');
        Route::post('/',                [RoleController::class, 'store'])
            ->middleware('permission:roles.create')->name('store');
        Route::put('/{role}',           [RoleController::class, 'update'])
            ->middleware('permission:roles.update')->name('update');
        Route::delete('/{role}',        [RoleController::class, 'destroy'])
            ->middleware('permission:roles.delete')->name('destroy');
        Route::get('/{role}/permissions',  [RoleController::class, 'permissions'])
            ->middleware('permission:roles.permissions')->name('permissions');
        Route::post('/{role}/permissions', [RoleController::class, 'syncPermissions'])
            ->middleware('permission:roles.permissions')->name('permissions.sync');
    });

    /*
    |--------------------------------------------------------------------------
    | Arsip Link / Bookmark Manager
    |--------------------------------------------------------------------------
    | Library link bersama institusi.
    | View untuk yang punya permission arsip.view; CUD per permission.
    | Track open & toggle favorite hanya butuh arsip.view.
    */
    Route::prefix('arsip')->name('arsip.')->group(function () {

        // View & navigasi
        Route::get('/',                [ArsipFolderController::class, 'index'])
            ->middleware('permission:arsip.view')->name('index');
        Route::get('/folder/{folder}', [ArsipFolderController::class, 'index'])
            ->middleware('permission:arsip.view')->name('folder');
        Route::get('/search',          [ArsipLinkController::class, 'search'])
            ->middleware('permission:arsip.view')->name('search');
        Route::get('/link/{link}/go',  [ArsipLinkController::class, 'go'])
            ->middleware('permission:arsip.view')->name('link.go');

        // Aksi level user
        Route::post('/link/{link}/favorite', [ArsipLinkController::class, 'toggleFavorite'])
            ->middleware('permission:arsip.view')->name('link.favorite');

        // Folder CUD
        Route::post('/folder',            [ArsipFolderController::class, 'store'])
            ->middleware('permission:arsip.create')->name('folder.store');
        Route::put('/folder/{folder}',    [ArsipFolderController::class, 'update'])
            ->middleware('permission:arsip.update')->name('folder.update');
        Route::delete('/folder/{folder}', [ArsipFolderController::class, 'destroy'])
            ->middleware('permission:arsip.delete')->name('folder.destroy');
        Route::post('/folder/move',       [ArsipFolderController::class, 'move'])
            ->middleware('permission:arsip.update')->name('folder.move');

        // Link CUD
        Route::post('/link',                [ArsipLinkController::class, 'store'])
            ->middleware('permission:arsip.create')->name('link.store');
        Route::put('/link/{link}',          [ArsipLinkController::class, 'update'])
            ->middleware('permission:arsip.update')->name('link.update');
        Route::delete('/link/{link}',       [ArsipLinkController::class, 'destroy'])
            ->middleware('permission:arsip.delete')->name('link.destroy');
        Route::post('/link/{link}/pin',     [ArsipLinkController::class, 'togglePin'])
            ->middleware('permission:arsip.update')->name('link.pin');
        Route::post('/link/{link}/refetch', [ArsipLinkController::class, 'refetch'])
            ->middleware('permission:arsip.update')->name('link.refetch');

        // Tag
        Route::delete('/tag/{tag}', [ArsipTagController::class, 'destroy'])
            ->middleware('permission:arsip.delete')->name('tag.destroy');
    });
});
