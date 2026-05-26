<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed roles + permissions + pivot default.
     *
     * Idempotent: aman dijalankan ulang setelah penambahan permission baru.
     * Pivot role bawaan hanya disinkron pada saat pertama kali dibuat
     * (atau saat super_admin), supaya kustomisasi via UI tidak ter-overwrite.
     */
    public function run(): void
    {
        $this->seedRoles();
        $this->seedPermissions();
        $this->seedDefaultPivot();
    }

    protected function seedRoles(): void
    {
        $roles = [
            ['name' => 'super_admin', 'display_name' => 'Super Admin', 'description' => 'Akses penuh seluruh sistem.', 'is_system' => true],
            ['name' => 'kepala',      'display_name' => 'Kepala',      'description' => 'Pimpinan / penanggung jawab.',  'is_system' => true],
            ['name' => 'pegawai',     'display_name' => 'Pegawai',     'description' => 'Pengguna umum.',                  'is_system' => true],
        ];

        foreach ($roles as $r) {
            Role::firstOrCreate(['name' => $r['name']], $r);
        }
    }

    protected function seedPermissions(): void
    {
        $defs = $this->permissionDefinitions();
        $sort = 0;

        foreach ($defs as $group => $items) {
            foreach ($items as $item) {
                Permission::updateOrCreate(
                    ['key' => $item['key']],
                    [
                        'menu'       => $item['menu'],
                        'action'     => $item['action'],
                        'label'      => $item['label'],
                        'group'      => $group,
                        'sort_order' => $sort++,
                    ]
                );
            }
        }
    }

    protected function seedDefaultPivot(): void
    {
        $superAdmin = Role::where('name', 'super_admin')->first();
        $kepala     = Role::where('name', 'kepala')->first();
        $pegawai    = Role::where('name', 'pegawai')->first();

        if (!$superAdmin || !$kepala || !$pegawai) {
            return;
        }

        // Super admin: SELALU diset ke seluruh permission (sumber kebenaran)
        $allIds = Permission::pluck('id')->all();
        $superAdmin->permissions()->sync($allIds);

        // Kepala & Pegawai: hanya seed jika pivot kosong (belum dikustom)
        if ($kepala->permissions()->count() === 0) {
            $kepala->syncPermissionsByKey($this->kepalaDefaultKeys());
        }

        if ($pegawai->permissions()->count() === 0) {
            $pegawai->syncPermissionsByKey($this->pegawaiDefaultKeys());
        }
    }

    /**
     * Daftar lengkap permission, dikelompokkan per group untuk UI.
     */
    protected function permissionDefinitions(): array
    {
        return [
            'Umum' => [
                ['menu' => 'dashboard',     'action' => 'view',   'key' => 'dashboard.view',     'label' => 'Lihat Dashboard'],
            ],

            'Absensi' => [
                ['menu' => 'absensi',       'action' => 'view',   'key' => 'absensi.view',       'label' => 'Lihat Absensi'],
                ['menu' => 'absensi',       'action' => 'create', 'key' => 'absensi.create',     'label' => 'Input Absensi'],
                ['menu' => 'absensi',       'action' => 'delete', 'key' => 'absensi.delete',     'label' => 'Hapus Absensi'],
                ['menu' => 'hasil-absensi', 'action' => 'view',   'key' => 'hasil-absensi.view', 'label' => 'Lihat Hasil Absensi'],
            ],

            'Rekap' => [
                ['menu' => 'rekap',         'action' => 'view',   'key' => 'rekap.view',          'label' => 'Lihat Rekap'],
                ['menu' => 'rekap',         'action' => 'export', 'key' => 'rekap.export',        'label' => 'Download / Export Rekap'],
                ['menu' => 'rekap',         'action' => 'config', 'key' => 'rekap.config.update', 'label' => 'Atur Konfigurasi Rekap'],

                ['menu' => 'rekap-manual',  'action' => 'view',   'key' => 'rekap-manual.view',   'label' => 'Lihat Rekap Manual'],
                ['menu' => 'rekap-manual',  'action' => 'create', 'key' => 'rekap-manual.create', 'label' => 'Upload Rekap Manual'],
                ['menu' => 'rekap-manual',  'action' => 'delete', 'key' => 'rekap-manual.delete', 'label' => 'Hapus Rekap Manual'],
            ],

            'Surat Izin' => [
                ['menu' => 'surat-izin',    'action' => 'view',   'key' => 'surat-izin.view',     'label' => 'Lihat Surat Izin'],
                ['menu' => 'surat-izin',    'action' => 'create', 'key' => 'surat-izin.create',   'label' => 'Upload Surat Izin'],
                ['menu' => 'surat-izin',    'action' => 'delete', 'key' => 'surat-izin.delete',   'label' => 'Hapus Surat Izin'],
            ],

            'Perjalanan Dinas' => [
                ['menu' => 'perjalanan-dinas', 'action' => 'view',              'key' => 'perjalanan-dinas.view',              'label' => 'Lihat Perjalanan Dinas'],
                ['menu' => 'perjalanan-dinas', 'action' => 'create',            'key' => 'perjalanan-dinas.create',            'label' => 'Input Perjalanan Dinas'],
                ['menu' => 'perjalanan-dinas', 'action' => 'delete',            'key' => 'perjalanan-dinas.delete',            'label' => 'Hapus Perjalanan Dinas'],
                ['menu' => 'perjalanan-dinas', 'action' => 'blokir',            'key' => 'perjalanan-dinas.blokir',            'label' => 'Blokir / Buka Tanggal Dinas'],
                ['menu' => 'perjalanan-dinas', 'action' => 'spj',               'key' => 'perjalanan-dinas.spj',               'label' => 'Toggle SPJ'],
                ['menu' => 'perjalanan-dinas', 'action' => 'kepala-keterangan', 'key' => 'perjalanan-dinas.kepala-keterangan', 'label' => 'Atur Keterangan Kepala'],
            ],

            'Arsip' => [
                ['menu' => 'arsip',         'action' => 'view',   'key' => 'arsip.view',   'label' => 'Lihat Arsip Link'],
                ['menu' => 'arsip',         'action' => 'create', 'key' => 'arsip.create', 'label' => 'Tambah Folder/Link Arsip'],
                ['menu' => 'arsip',         'action' => 'update', 'key' => 'arsip.update', 'label' => 'Edit Folder/Link Arsip'],
                ['menu' => 'arsip',         'action' => 'delete', 'key' => 'arsip.delete', 'label' => 'Hapus Folder/Link Arsip'],
            ],

            'Master' => [
                ['menu' => 'pegawai',       'action' => 'view',   'key' => 'pegawai.view',   'label' => 'Lihat Pegawai'],
                ['menu' => 'pegawai',       'action' => 'create', 'key' => 'pegawai.create', 'label' => 'Tambah Pegawai'],
                ['menu' => 'pegawai',       'action' => 'update', 'key' => 'pegawai.update', 'label' => 'Edit Pegawai'],
                ['menu' => 'pegawai',       'action' => 'delete', 'key' => 'pegawai.delete', 'label' => 'Hapus Pegawai'],
                ['menu' => 'pegawai',       'action' => 'import', 'key' => 'pegawai.import', 'label' => 'Import Pegawai'],
                ['menu' => 'pegawai',       'action' => 'export', 'key' => 'pegawai.export', 'label' => 'Export Pegawai'],

                ['menu' => 'kode-kegiatan', 'action' => 'view',   'key' => 'kode-kegiatan.view',   'label' => 'Lihat Kode Kegiatan'],
                ['menu' => 'kode-kegiatan', 'action' => 'create', 'key' => 'kode-kegiatan.create', 'label' => 'Tambah Kode Kegiatan'],
                ['menu' => 'kode-kegiatan', 'action' => 'update', 'key' => 'kode-kegiatan.update', 'label' => 'Edit Kode Kegiatan'],
                ['menu' => 'kode-kegiatan', 'action' => 'delete', 'key' => 'kode-kegiatan.delete', 'label' => 'Hapus Kode Kegiatan'],

                ['menu' => 'tanggal-libur', 'action' => 'create', 'key' => 'tanggal-libur.create', 'label' => 'Tambah Tanggal Libur / Info'],
                ['menu' => 'tanggal-libur', 'action' => 'delete', 'key' => 'tanggal-libur.delete', 'label' => 'Hapus Tanggal Libur / Info'],
            ],

            'Sistem' => [
                ['menu' => 'settings',      'action' => 'view',     'key' => 'settings.view',     'label' => 'Lihat Pengaturan'],
                ['menu' => 'settings',      'action' => 'update',   'key' => 'settings.update',   'label' => 'Ubah Pengaturan'],
                ['menu' => 'settings',      'action' => 'telegram', 'key' => 'settings.telegram', 'label' => 'Test / Backup Telegram'],

                ['menu' => 'log-aktivitas', 'action' => 'view',  'key' => 'log-aktivitas.view',  'label' => 'Lihat Log Aktivitas'],
                ['menu' => 'log-aktivitas', 'action' => 'prune', 'key' => 'log-aktivitas.prune', 'label' => 'Bersihkan Log Aktivitas'],

                ['menu' => 'roles',         'action' => 'view',        'key' => 'roles.view',        'label' => 'Lihat Manajemen Role'],
                ['menu' => 'roles',         'action' => 'create',      'key' => 'roles.create',      'label' => 'Tambah Role'],
                ['menu' => 'roles',         'action' => 'update',      'key' => 'roles.update',      'label' => 'Edit Role'],
                ['menu' => 'roles',         'action' => 'delete',      'key' => 'roles.delete',      'label' => 'Hapus Role'],
                ['menu' => 'roles',         'action' => 'permissions', 'key' => 'roles.permissions', 'label' => 'Atur Permission Role'],
            ],
        ];
    }

    /**
     * Default permission untuk role kepala (mencerminkan akses lama).
     */
    protected function kepalaDefaultKeys(): array
    {
        return [
            'dashboard.view',
            'absensi.view', 'absensi.create', 'absensi.delete',
            'hasil-absensi.view',
            'rekap.view', 'rekap.export',
            'rekap-manual.view', 'rekap-manual.create', 'rekap-manual.delete',
            'surat-izin.view', 'surat-izin.create', 'surat-izin.delete',
            'perjalanan-dinas.view', 'perjalanan-dinas.create', 'perjalanan-dinas.delete',
            'perjalanan-dinas.blokir', 'perjalanan-dinas.spj', 'perjalanan-dinas.kepala-keterangan',
            'arsip.view', 'arsip.create', 'arsip.update', 'arsip.delete',
            'pegawai.view', 'pegawai.create', 'pegawai.update', 'pegawai.delete',
            'pegawai.import', 'pegawai.export',
            'tanggal-libur.create', 'tanggal-libur.delete',
        ];
    }

    /**
     * Default permission untuk role pegawai (mencerminkan akses lama).
     */
    protected function pegawaiDefaultKeys(): array
    {
        return [
            'dashboard.view',
            'absensi.view', 'absensi.create', 'absensi.delete',
            'hasil-absensi.view',
            'rekap.view', 'rekap.export',
            'rekap-manual.view',
            'surat-izin.view',
            'perjalanan-dinas.view', 'perjalanan-dinas.create', 'perjalanan-dinas.delete',
            'arsip.view',
        ];
    }
}
