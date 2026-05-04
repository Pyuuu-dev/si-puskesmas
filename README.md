# SI Puskesmas - Sistem Informasi Puskesmas

Sistem Informasi Manajemen Puskesmas untuk mengelola absensi pegawai, perjalanan dinas, dan kegiatan harian.

## 🚀 Fitur Utama

### 1. Manajemen Pegawai
- ✅ CRUD pegawai dengan data lengkap (NIP, pangkat, jabatan, dll)
- ✅ Import/Export data pegawai via CSV
- ✅ Pencarian dan pagination
- ✅ Sorting otomatis berdasarkan jabatan (Kepala → Dokter → Bidan → Perawat → Medis → PTT BLUD)
- ✅ Role-based access (Super Admin, Kepala, Pegawai)

### 2. Absensi
- ✅ Absensi harian dengan 3 slot waktu (Pagi, Siang, Sore)
- ✅ Status: Hadir, Izin, Sakit, Cuti, Dinas, Alpa
- ✅ Konversi waktu otomatis (WIT/WITA/WIB)
- ✅ Keterangan tambahan per absensi
- ✅ Admin (super_admin) tidak muncul di daftar absensi

### 3. Perjalanan Dinas
- ✅ Pencatatan perjalanan dinas pegawai
- ✅ Integrasi dengan kode kegiatan
- ✅ Tracking lokasi dan keterangan lengkap
- ✅ Filter dan sorting berdasarkan urutan jabatan
- ✅ Admin (super_admin) tidak muncul di daftar dinas

### 4. Hasil Absensi
- ✅ Rekap absensi per tanggal
- ✅ Tampilan waktu asli dan waktu terkonversi
- ✅ Export data hasil absensi
- ✅ Filter berdasarkan tanggal
- ✅ Sorting berdasarkan urutan jabatan

### 5. Kode Kegiatan
- ✅ Manajemen menu kegiatan (dengan warna)
- ✅ Rincian menu per kegiatan
- ✅ Kegiatan dengan kode dan anggaran
- ✅ Struktur hierarki: Menu → Rincian → Kegiatan

### 6. Kalender Publik
- ✅ Tampilan kalender kegiatan bulanan
- ✅ Info lokasi posyandu per tanggal
- ✅ Badge kegiatan dengan warna
- ✅ Informasi tanggal libur
- ✅ **Ringkasan kehadiran**: Jumlah pegawai yang sudah/belum absen per hari
- ✅ Akses publik tanpa login

### 7. Telegram Backup Bot
- ✅ Backup database otomatis 3x sehari (08:00, 14:00, 20:00 WITA)
- ✅ Backup manual via command `php artisan backup:telegram`
- ✅ File backup dikirim langsung ke Telegram
- ✅ Timestamp otomatis pada nama file
- ✅ Cleanup otomatis setelah backup terkirim

### 8. Pengaturan
- ✅ Konfigurasi nama instansi
- ✅ Pengaturan jam kerja (3 slot)
- ✅ Manajemen tanggal libur
- ✅ Info tanggal (lokasi posyandu, dll)

## 🛠️ Tech Stack

- **Framework**: Laravel 12
- **PHP**: 8.2+
- **Database**: SQLite
- **Frontend**: Tailwind CSS 4, Alpine.js 3
- **Icons**: Heroicons

## 📋 Requirements

- PHP 8.2 atau lebih tinggi
- Composer
- Node.js & NPM
- SQLite3

## 🔧 Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/Pyuuu-dev/si-puskesmas.git
cd si-puskesmas
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Setup Environment

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Setup Database

```bash
touch database/database.sqlite
php artisan migrate --seed
```

### 5. Build Assets

```bash
npm run build
# atau untuk development
npm run dev
```

### 6. Setup Telegram Bot (Opsional)

Lihat panduan lengkap di [TELEGRAM_BACKUP_SETUP.md](TELEGRAM_BACKUP_SETUP.md)

```env
TELEGRAM_BOT_TOKEN=your_bot_token_here
TELEGRAM_CHAT_ID=your_chat_id_here
```

### 7. Setup Cron Job (untuk backup otomatis)

```bash
* * * * * cd /path/to/si-puskesmas && php artisan schedule:run >> /dev/null 2>&1
```

### 8. Jalankan Server

```bash
php artisan serve
```

Akses aplikasi di `http://localhost:8000`

## 👤 Default Login

Setelah seeding, gunakan kredensial berikut:

- **Email**: admin@puskesmas.id
- **Password**: password

⚠️ **PENTING**: Segera ubah password default setelah login pertama kali!

## 📁 Struktur Database

### Users
- Data pegawai lengkap dengan NIP, pangkat, jabatan
- Field `urutan` untuk sorting otomatis
- Role: super_admin, kepala, pegawai

### Absensi
- 3 slot waktu per hari
- Multiple status (hadir, izin, sakit, cuti, dinas, alpa)
- Jam dan keterangan per absensi

### Perjalanan Dinas
- Relasi ke user dan kegiatan
- Lokasi dan keterangan lengkap

### Kegiatan
- Hierarki: MenuKegiatan → RincianMenu → Kegiatan
- Kode kegiatan dan anggaran

### Settings
- Key-value storage untuk konfigurasi
- Jam kerja (3 slot)

### Tanggal Libur
- Tanggal dan keterangan libur

### Info Tanggal
- Multiple info per tanggal (lokasi posyandu, dll)

## 🎨 Fitur UI/UX

- Responsive design (mobile-friendly)
- Toast notifications (5 detik)
- Modal dialogs dengan Alpine.js
- Color-coded badges untuk status
- Sorting dan filtering otomatis
- Pagination dengan info jumlah data

## 📝 Command Artisan

```bash
# Backup database ke Telegram
php artisan backup:telegram

# Lihat daftar scheduled tasks
php artisan schedule:list

# Jalankan scheduled tasks secara manual
php artisan schedule:run
```

## 🔐 Keamanan

- Password hashing dengan bcrypt
- CSRF protection
- Role-based middleware
- Input validation
- SQL injection protection (Eloquent ORM)
- XSS protection

## 📊 Sorting Pegawai

Pegawai diurutkan berdasarkan field `urutan`:
- 1 = Kepala Puskesmas
- 2 = Dokter
- 3 = Bidan
- 4 = Perawat
- 5 = Tenaga Medis
- 10 = PTT BLUD
- 99 = Lainnya

Jika urutan sama, diurutkan berdasarkan nama (A-Z).

## 🚫 Exclusion Admin

Admin dengan role `super_admin` tidak muncul di:
- Daftar absensi
- Daftar perjalanan dinas
- Hasil absensi
- Ringkasan kehadiran di kalender publik

## 🌐 Public Routes

- `/public/calendar` - Kalender kegiatan publik (tanpa login)

## 🤝 Contributing

Contributions, issues, and feature requests are welcome!

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 👨‍💻 Developer

Developed by Pyuuu-dev

## 📞 Support

Untuk pertanyaan atau bantuan, silakan buat issue di GitHub repository.

---

**Built with ❤️ using Laravel**
