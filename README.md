# SI Puskesmas — Sistem Informasi Puskesmas

Sistem Informasi Manajemen Puskesmas untuk mengelola absensi pegawai, perjalanan dinas, rekap kehadiran, dan kegiatan harian.

## 🚀 Fitur Utama

### 1. Manajemen Pegawai
- ✅ CRUD pegawai dengan data lengkap (NIP, pangkat, jabatan, status kepegawaian)
- ✅ Pencarian live dengan debounce 400ms
- ✅ Pagination (20 per halaman)
- ✅ Sorting manual via field `urutan`
- ✅ Status kepegawaian: PNS, PPPK, PPPK Paruh Waktu, PTT, Lainnya
- ✅ Role-based access (super_admin, kepala, pegawai)

### 2. Absensi Harian
- ✅ **Input 1 klik per hari per pegawai** (bukan per slot)
- ✅ Status kehadiran: Hadir, Izin, Sakit, Cuti Bersalin, Cuti Tahunan, Dinas Luar, Ijin Belajar, Tidak Hadir
- ✅ Jika Hadir → pilih **Apel Pagi** dan **Apel Siang** (Apel / Tidak Apel + jam)
- ✅ **Field jam bisa paste** dari spreadsheet (auto-format: `7:50:00 AM` → `07:50`, `1430` → `14:30`, dll)
- ✅ Validasi format jam dengan error visual (border merah + pesan)
- ✅ Tidak Apel (TA) disimpan dengan `keterangan = 'tidak_apel'` di slot yang bersangkutan
- ✅ Tampilan sel: `H` (hijau) = hadir apel, `TA` (abu-abu) = tidak apel + jam
- ✅ Scroll position dipertahankan setelah simpan/hapus (tidak kembali ke atas)
- ✅ Kelola hari libur: tambah, edit, hapus dengan tabel friendly
- ✅ Admin (super_admin) tidak muncul di daftar absensi

### 3. Hasil Absensi (Konversi)
- ✅ Tampilan jam masuk dan jam pulang setelah konversi
- ✅ Konversi berbeda untuk penempatan Induk dan Desa
- ✅ Tampilan TA: jam raw + label TA + jam konversi (3 baris)
- ✅ Filter berdasarkan penempatan
- ✅ Sticky header untuk navigasi mudah

### 4. Rekap Absensi
- ✅ Tabel rekap kehadiran: H, I, S, CB, CT, DL, IB, TH, Total
- ✅ Total = jumlah hari kerja yang ada data (dari slot pagi, tidak double count)
- ✅ Download Excel dengan 4 sheet:
  - **APEL PAGI** — status + jam konversi masuk, kolom rekap: H, TA, I, S, CB, CT, DL, IB, TK
  - **APEL SIANG** — status + jam konversi pulang, kolom rekap: H, TA, I, S, CB, CT, DL, IB, TK
  - **KEHADIRAN HARIAN** — kode status saja (H/I/S/CB/CT/DL/IB/TK)
  - **REKAP** — poin sakit + poin TA per pegawai

### 5. Sistem Poin Kedisiplinan (Sheet REKAP)
- ✅ **Poin Sakit**: sakit > 3 hari → (total - 3) poin. Contoh: 4 sakit = 1 poin, 5 sakit = 2 poin
- ✅ **Poin TA**: floor(total TA / 7). Contoh: 7 TA = 1 poin, 14 TA = 2 poin
- ✅ Kolom: SAKIT (HARI) | POIN SAKIT | TA PAGI | TA SIANG | TOTAL TA | POIN TA
- ✅ Warna otomatis: hijau (0 poin) → kuning → orange → merah

### 6. Kode Kegiatan BOK
- ✅ CRUD kegiatan dengan kode + nama + warna
- ✅ Hierarki: Menu → Rincian Menu → Kegiatan
- ✅ **Lihat Pemakai Kode**: modal dengan filter bulan/tahun, list pegawai + tanggal yang pakai kode tersebut

### 7. Perjalanan Dinas
- ✅ Matriks perjalanan dinas per pegawai per tanggal
- ✅ Filter pegawai (checkbox dropdown)
- ✅ Integrasi kode kegiatan BOK
- ✅ **Dropdown kode kegiatan**: tampil 2 baris (kode + nama lengkap) + search bidirectional
- ✅ **Konfirmasi hapus dinas** sebelum eksekusi
- ✅ **Blokir sel** (admin only): blokir per orang atau seluruh tanggal dengan keterangan
- ✅ **Buka blokir**: per orang atau seluruh tanggal sekaligus
- ✅ Sel hitam = tidak tersedia (diblokir admin)
- ✅ Tombol shortcut "Versi Publik" → buka halaman publik di tab baru
- ✅ Kelola lokasi posyandu per tanggal

### 8. Halaman Publik Perjalanan Dinas
- ✅ Akses tanpa login: `/perjalanan-dinas-publik`
- ✅ Summary cards: total dinas, pegawai sudah/belum dinas, tanggal terisi
- ✅ **Tanggal tidak tersedia**: hari Minggu + hari libur nasional (dengan keterangan)
- ✅ **Tabel ketersediaan**: semua tanggal dengan status Tersedia / Terisi / Libur / Minggu
- ✅ Sel "Terisi" menampilkan nama pegawai + kode kegiatan
- ✅ Kartu pegawai sudah dinas + tanggal-tanggal mereka dinas
- ✅ Daftar pegawai belum dinas
- ✅ Rekap lengkap per pegawai dengan badge tanggal

### 9. Kalender Publik
- ✅ Tampilan kalender kegiatan bulanan
- ✅ Info lokasi posyandu per tanggal
- ✅ Informasi tanggal libur
- ✅ Akses publik tanpa login: `/kalender`

### 10. Profil Pengguna
- ✅ Edit nama dan email
- ✅ Ganti password
- ✅ **Upload foto profil dengan crop/zoom**:
  - Pilih foto → modal crop muncul
  - Drag untuk geser, scroll untuk zoom, tombol rotate 90°
  - Preview sebelum disimpan (badge "Belum disimpan")
  - Hapus foto profil
- ✅ Foto disimpan di `storage/app/public/profile-photos/`

### 11. Pengaturan Sistem
- ✅ Nama instansi (tampil di sidebar, login, header)
- ✅ **Nama sistem / subtitle login** (dapat diubah via pengaturan)
- ✅ Alamat, telepon, email instansi
- ✅ Upload logo instansi
- ✅ Konfigurasi jam kerja per hari (Senin–Sabtu)
- ✅ Konversi jam masuk/pulang per penempatan (Induk/Desa)
- ✅ Konfigurasi Telegram bot (token, chat ID)
- ✅ Jadwal backup otomatis (3 waktu konfigurabel)

### 12. Telegram Backup
- ✅ Backup database otomatis 3x sehari (waktu konfigurabel via pengaturan)
- ✅ Backup manual: `php artisan backup:telegram`
- ✅ File backup dikirim langsung ke Telegram
- ✅ Cleanup otomatis setelah backup terkirim

---

## 🛠️ Tech Stack

| Layer | Teknologi |
|---|---|
| Framework | Laravel 12 |
| PHP | 8.2+ |
| Database | SQLite |
| CSS | Tailwind CSS 4 |
| JS | Alpine.js 3 |
| Excel | PhpSpreadsheet 5.7 |
| Foto Crop | Cropper.js 1.6 (CDN) |
| Icons | Heroicons |

---

## 📋 Requirements

- PHP 8.2+ dengan ekstensi: `gd`, `sqlite3`, `mbstring`, `xml`, `zip`
- Composer
- Node.js & NPM
- SQLite3

---

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

### 3. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:
```env
APP_NAME="SI Puskesmas"
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite
```

### 4. Database Setup

```bash
touch database/database.sqlite
php artisan migrate --seed
```

### 5. Storage Link

```bash
php artisan storage:link
```

### 6. Build Assets

```bash
npm run build
# atau untuk development:
npm run dev
```

### 7. Jalankan Server

```bash
php artisan serve
```

Akses di `http://localhost:8000`

---

## 👤 Default Login

| Role | Email | Password |
|---|---|---|
| Super Admin | admin@puskesmas.id | password |

⚠️ **Segera ubah password default setelah login pertama!**

---

## 📁 Struktur Database

### Tabel Utama

| Tabel | Deskripsi |
|---|---|
| `users` | Data pegawai + role + urutan + status_kepegawaian |
| `absensi` | Absensi harian (slot: pagi/sore, keterangan: tidak_apel) |
| `perjalanan_dinas` | Perjalanan dinas per pegawai per tanggal |
| `jam_kerja` | Jam kerja + konversi per hari (Senin–Sabtu) |
| `tanggal_libur` | Hari libur nasional + keterangan |
| `info_tanggal` | Info lokasi posyandu per tanggal |
| `kode_kegiatan` | Kode kegiatan BOK |
| `menu_kegiatan` | Menu kegiatan (dengan warna) |
| `rincian_menu` | Rincian menu per kegiatan |
| `kegiatan` | Kegiatan dengan kode dan anggaran |
| `settings` | Key-value konfigurasi sistem |
| `rekap_config` | Konfigurasi TL/PSW (hidden, belum dirilis) |

### Struktur Absensi

```
absensi
├── user_id
├── tanggal
├── slot: pagi | sore
├── status: hadir | izin | sakit | cuti_bersalin | cuti_tahunan | dinas_luar | ijin_belajar | alfa
├── jam (nullable)
└── keterangan: null | 'tidak_apel'
```

**Logika input per hari:**
- Status non-hadir → 2 record (pagi + sore) dengan jam null
- Status hadir → 2 record dengan jam masing-masing, keterangan `tidak_apel` jika tidak apel

---

## 🎨 Kode Warna Status

| Kode | Status | Warna |
|---|---|---|
| H | Hadir (Apel) | Hijau |
| TA | Tidak Apel | Abu-abu |
| I | Izin | Kuning |
| S | Sakit | Orange |
| CB | Cuti Bersalin | Pink |
| CT | Cuti Tahunan | Pink |
| DL | Dinas Luar | Biru muda |
| IB | Ijin Belajar | Ungu |
| TK/TH | Tanpa Keterangan | Merah |

---

## 📝 Command Artisan

```bash
# Backup database ke Telegram
php artisan backup:telegram

# Lihat scheduled tasks
php artisan schedule:list

# Jalankan scheduled tasks manual
php artisan schedule:run
```

---

## ⚙️ Cron Setup (Production)

```bash
# Edit crontab
crontab -e

# Tambahkan baris ini:
* * * * * cd /var/www/puskesmas && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🔐 Keamanan

- Password hashing dengan bcrypt
- CSRF protection pada semua form
- Role-based middleware (`role:super_admin`, `role:super_admin,kepala`)
- Input validation di semua controller
- SQL injection protection via Eloquent ORM
- XSS protection via Blade templating
- File upload validation (type + size)

---

## 🌐 Routes Publik (Tanpa Login)

| URL | Deskripsi |
|---|---|
| `/kalender` | Kalender kegiatan bulanan |
| `/perjalanan-dinas-publik` | Info perjalanan dinas + ketersediaan tanggal |

---

## 🚫 Exclusion Admin

Role `super_admin` tidak muncul di:
- Daftar absensi
- Hasil absensi
- Rekap absensi
- Perjalanan dinas
- Kalender publik

---

## 📊 Sorting Pegawai

Diurutkan berdasarkan field `urutan` (ascending), lalu nama (A-Z):

| Urutan | Jabatan |
|---|---|
| 1 | Kepala Puskesmas |
| 2–5 | Dokter, Bidan, Perawat, dll |
| 99 | Default (belum diatur) |

---

## 🔄 Changelog

### v2.1 (Mei 2026)
- **Captcha + Rate Limiting** di halaman login (math captcha + max 5 percobaan/menit)
- **Session lifetime** diperpanjang ke 7 hari + remember cookie 5 tahun
- **Foto profil** tampil di sidebar dan topbar (auto sync setelah simpan)
- **Nama sistem** dapat diubah via pengaturan
- **Fix scroll tabel absensi**: posisi tabel dipertahankan setelah simpan/hapus (tidak kembali ke atas)
- **Field jam input absensi**: bisa **paste dari spreadsheet** dengan auto-format (terima `7:50:00 AM`, `7:50 AM`, `0750`, `1430`, dll → otomatis jadi `HH:MM`)
- **Rekap kehadiran**: H mencakup semua hadir termasuk TA (tidak double count)
- **Sheet Excel apel**: tambah kolom **TA** terpisah
- **Halaman dinas publik**: redesign warna kontras tinggi + tombol shortcut "Versi Publik" di halaman admin
- **Blokir sel perjalanan dinas**: admin bisa blokir sel per orang/per tanggal dengan keterangan
- **Buka blokir**: per orang atau seluruh tanggal sekaligus
- **Lihat pemakai kode kegiatan**: modal dengan filter bulan/tahun, list pegawai + tanggal pakai kode
- **Konfirmasi hapus dinas**: dialog `confirm()` sebelum hapus
- **Dropdown kode kegiatan**: tampil 2 baris (kode + nama kegiatan) + search di kode/nama
- **Kelola hari libur**: tambah/edit/hapus dengan UI tabel friendly
- **Profile foto**: crop/zoom sebelum simpan (Cropper.js)
- **Excel REKAP**: poin sakit + poin TA terpisah dengan warna kolom
- **Halaman publik dinas**: tabel ketersediaan tanggal + status (Tersedia/Terisi/Libur/Minggu)

### v2.0 (April 2026)
- **Redesign alur input absensi**: 1 modal per hari (bukan per slot)
- **Apel Pagi & Apel Siang**: pilihan Apel / Tidak Apel dengan jam
- **Kode TA** (Tidak Apel) di tabel, hasil absensi, dan Excel export
- **Sheet REKAP** dengan sistem poin (sakit > 3 hari, TA kelipatan 7)
- **Halaman publik perjalanan dinas**

### v1.0 (April 2026)
- Fitur dasar: absensi, perjalanan dinas, rekap, kalender publik
- Telegram backup otomatis
- Export Excel rekap absensi

---

## 👨‍💻 Developer

Developed by [Pyuuu-dev](https://github.com/Pyuuu-dev)

## 📄 License

MIT License

---

**Built with ❤️ using Laravel 12 + Tailwind CSS 4 + Alpine.js 3**
