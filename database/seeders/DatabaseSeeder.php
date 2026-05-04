<?php

namespace Database\Seeders;

use App\Models\KodeKegiatan;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin
        User::create([
            'name' => 'Administrator',
            'nip' => '000000000000000000',
            'jabatan' => 'Admin Sistem',
            'unit_kerja' => 'UPTD Puskesmas Angkat',
            'email' => 'admin@puskesmas.id',
            'role' => 'super_admin',
            'password' => bcrypt('password'),
        ]);

        // Kepala Puskesmas
        User::create([
            'name' => 'dr. Kepala Puskesmas',
            'nip' => '199001012020011001',
            'jabatan' => 'Kepala Puskesmas',
            'unit_kerja' => 'UPTD Puskesmas Angkat',
            'email' => 'kepala@puskesmas.id',
            'role' => 'kepala',
            'password' => bcrypt('password'),
        ]);

        // Pegawai contoh
        $pegawai = [
            ['name' => 'Ahmad Fauzi', 'nip' => '199201012021011001', 'jabatan' => 'Perawat', 'unit_kerja' => 'Poli Umum'],
            ['name' => 'Siti Aminah', 'nip' => '199301012021012001', 'jabatan' => 'Bidan', 'unit_kerja' => 'KIA'],
            ['name' => 'Budi Santoso', 'nip' => '199401012021011001', 'jabatan' => 'Sanitarian', 'unit_kerja' => 'Kesling'],
            ['name' => 'Dewi Lestari', 'nip' => '199501012021012001', 'jabatan' => 'Nutrisionis', 'unit_kerja' => 'Gizi'],
            ['name' => 'Eko Prasetyo', 'nip' => '199601012021011001', 'jabatan' => 'Promkes', 'unit_kerja' => 'Promosi Kesehatan'],
        ];

        foreach ($pegawai as $p) {
            User::create(array_merge($p, [
                'email' => strtolower(str_replace(' ', '.', $p['name'])) . '@puskesmas.id',
                'role' => 'pegawai',
                'password' => bcrypt('password'),
            ]));
        }

        // Kode Kegiatan
        $kegiatan = [
            ['kode' => 'PTM', 'nama' => 'Penyakit Tidak Menular', 'warna' => '#10B981'],
            ['kode' => 'UKS', 'nama' => 'Usaha Kesehatan Sekolah', 'warna' => '#3B82F6'],
            ['kode' => 'TB', 'nama' => 'Tuberkulosis', 'warna' => '#F59E0B'],
            ['kode' => 'JIWA', 'nama' => 'Kesehatan Jiwa', 'warna' => '#8B5CF6'],
            ['kode' => 'KIA', 'nama' => 'Kesehatan Ibu dan Anak', 'warna' => '#EC4899'],
            ['kode' => 'LANSIA', 'nama' => 'Kesehatan Lansia', 'warna' => '#EF4444'],
            ['kode' => 'SURVEI', 'nama' => 'Surveilans', 'warna' => '#6B7280'],
            ['kode' => 'GIZI', 'nama' => 'Program Gizi', 'warna' => '#14B8A6'],
            ['kode' => 'IMUN', 'nama' => 'Imunisasi', 'warna' => '#F97316'],
            ['kode' => 'KESLING', 'nama' => 'Kesehatan Lingkungan', 'warna' => '#84CC16'],
        ];

        foreach ($kegiatan as $k) {
            KodeKegiatan::create($k);
        }

        // Settings
        Setting::set('jam_masuk', '07:30');
        Setting::set('jam_pulang', '16:00');
        Setting::set('nama_instansi', 'UPTD Puskesmas Angkat');
        Setting::set('alamat_instansi', 'Kec. Angkat, Kabupaten ...');
    }
}
