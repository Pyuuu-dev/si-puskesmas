# SI Puskesmas — Sistem Informasi Puskesmas

Sistem Informasi Manajemen Puskesmas untuk mengelola absensi pegawai, perjalanan dinas, rekap kehadiran, dan kegiatan harian.

## 🚀 Fitur Utama

### 1. Manajemen Pegawai
- ✅ CRUD pegawai dengan data lengkap (NIP, pangkat, jabatan, status kepegawaian)
- ✅ Pencarian live dengan debounce 400ms
- ✅ Pagination (20 per halaman)
- ✅ Sorting manual via field `urutan`
- ✅ Status kepegawaian: PNS, PPPK, PPPK Paruh Waktu, PTT, Lainnya
- ✅ Role-based access dinamic via permission middleware (lihat modul Manajemen Role)

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
- ✅ **Filter pegawai search-based** + multi-select chips (Alpine reactive)
- ✅ **Row Kepala** di matriks dinas dengan info absensi (izin/sakit/cuti/dll) + edit keterangan inline
- ✅ **Snapshot tarif per hari** per record (data lama tetap pakai tarif saat dibuat)
- ✅ Kelola lokasi posyandu per tanggal

### 8. Halaman Publik Perjalanan Dinas
- ✅ Akses tanpa login: `/perjalanan-dinas-publik`
- ✅ **Matrix view**: per pegawai per tanggal (mengikuti tampilan halaman admin, tanpa edit)
- ✅ Summary cards: total dinas, pegawai sudah/belum dinas, tanggal terisi
- ✅ Sel kode kegiatan dengan warna menu + indikator SPJ sudah diperiksa
- ✅ Sel ketidakhadiran (izin/sakit/cuti/dinas luar/dll) dari data absensi
- ✅ Sel hitam = sel dinas yang diblokir admin (per orang/per tanggal)
- ✅ Row khusus Kepala dengan status absensi
- ✅ **Tanggal tidak tersedia**: hari Minggu + hari libur nasional (dengan keterangan)
- ✅ Daftar pegawai belum dinas + rekap per pegawai

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
- ✅ **Tarif perjalanan dinas per orang per hari** (default Rp 80.000, dipakai sebagai pengurang otomatis pagu kegiatan)
- ✅ Konfigurasi Telegram bot (token, chat ID)
- ✅ Jadwal backup otomatis (3 waktu konfigurabel)

### 12. Telegram Backup
- ✅ Backup database otomatis 3x sehari (waktu konfigurabel via pengaturan)
- ✅ Backup manual: `php artisan backup:telegram`
- ✅ File backup dikirim langsung ke Telegram
- ✅ Cleanup otomatis setelah backup terkirim

### 13. Surat Izin
- ✅ Manajemen surat izin pegawai (sakit, izin, cuti, dll)
- ✅ Upload file surat (PDF/gambar) dengan preview
- ✅ Indikator surat di tabel absensi: ikon dokumen pada tanggal yang punya surat
- ✅ Filter pegawai, status, rentang tanggal
- ✅ Edit & hapus dengan validasi role

### 14. Rekap Manual
- ✅ Input rekap manual ketidakhadiran per pegawai per bulan
- ✅ Kolom: izin, sakit, cuti, dinas luar, ijin belajar, tanpa keterangan
- ✅ Sistem **poin ketidakhadiran** dengan kalkulasi otomatis
- ✅ Filter bulan/tahun, edit inline, simpan batch

### 15. Log Aktivitas (Audit Trail)
- ✅ Pencatatan otomatis aktivitas user ke tabel `activity_logs`
- ✅ **Auth events**: login, logout, login gagal, lockout (via Laravel event listener)
- ✅ **CRUD events** di seluruh modul: pegawai, kode kegiatan, settings, absensi, perjalanan dinas, tanggal libur, rekap manual, surat izin, rekap
- ✅ Halaman `/log-aktivitas` (super_admin only) dengan filter tanggal, user, modul, event, keyword
- ✅ Modal detail JSON untuk inspeksi payload
- ✅ Tombol manual bersihkan log lama dengan threshold konfigurabel
- ✅ Command `activity-log:prune` dijadwalkan harian jam 02:00 (retention default 180 hari)

### 16. Arsip Link / Bookmark Manager
- ✅ Library link bersama institusi (akses untuk semua user login)
- ✅ **Folder hierarki** (parent-child) dengan tree sidebar
- ✅ **Tag** per link (multi-tag, filter by tag)
- ✅ **Favorit per user** + **pin** (admin only) untuk tampil di halaman home
- ✅ **Search** lintas folder dengan omnibar
- ✅ **Drag-drop** folder & link (SortableJS) untuk reordering/move
- ✅ **Custom icon preset** + auto-fetch metadata (title, favicon, deskripsi) saat tambah link
- ✅ **Track open**: setiap klik link tercatat (popularitas)
- ✅ **Dark mode** scoped per halaman arsip
- ✅ Akses CUD: super_admin & kepala. View + favorite + track: semua user login

### 17. Tracking Anggaran Perjalanan Dinas
- ✅ **Tarif per hari** (default Rp 80.000) dapat diubah via pengaturan
- ✅ Snapshot tarif per record saat create (data lama tetap pakai tarif saat dibuat — preserve histori)
- ✅ **Badge pagu/terpakai/sisa/over** per kegiatan, rincian menu, dan menu di halaman Kode Kegiatan
- ✅ Modal "Lihat Pemakai Kode": kartu pagu tahunan dengan persentase terpakai + breakdown per pegawai dengan subtotal
- ✅ Akumulasi terpakai dihitung per tahun anggaran berjalan

### 18. SPJ Perjalanan Dinas
- ✅ Tracking checklist SPJ per record dinas (sudah diperiksa / belum)
- ✅ Catatan SPJ per record (max 255 karakter)
- ✅ Audit: `spj_checked_by` (user id) + `spj_checked_at` (timestamp)
- ✅ Status SPJ tampil di matriks admin dan halaman publik (indikator visual)
- ✅ Toggle periksa/batal periksa via modal (super_admin & kepala only)

### 19. Manajemen Role & Permission (RBAC Dinamis)
- ✅ Tabel `roles`, `permissions`, `role_permission` dengan seeder lengkap
- ✅ Role default: `super_admin`, `kepala`, `pegawai` (dapat dikelola lewat UI `/roles`)
- ✅ Tambah/edit/hapus role custom (super_admin only)
- ✅ Halaman edit permission per role dengan grouping per modul (centang batch)
- ✅ Middleware `permission:<key>` di semua route sensitif (menggantikan `role:`)
- ✅ Helper di `User`: `hasPermission()`, `hasAnyPermission()` dengan caching per request
- ✅ Blade gate `@can('<key>')` aktif via `Gate::before` global
- ✅ Sidebar otomatis menyembunyikan menu yang tidak diizinkan untuk user tersebut
- ✅ Super admin selalu lolos semua permission check

### 20. Dashboard Analitik
- ✅ Statistik kehadiran bulan berjalan (H/I/S/CB/CT/DL/IB/TK)
- ✅ **Top 5 Tidak Apel** — chart bar pegawai dengan TA terbanyak (pagi vs siang)
- ✅ **Progress Anggaran Dinas per Menu BOK** — bar pagu/terpakai/sisa per menu
- ✅ Aktivitas terbaru (audit trail singkat)
- ✅ Empty state ramah ketika belum ada pelanggaran/dinas

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
| `perjalanan_dinas` | Perjalanan dinas per pegawai per tanggal + tarif_per_hari + spj_* (audit) |
| `jam_kerja` | Jam kerja + konversi per hari (Senin–Sabtu) |
| `tanggal_libur` | Hari libur nasional + keterangan |
| `info_tanggal` | Info lokasi posyandu per tanggal |
| `kode_kegiatan` | Kode kegiatan BOK |
| `menu_kegiatan` | Menu kegiatan (dengan warna) |
| `rincian_menu` | Rincian menu per kegiatan |
| `kegiatan` | Kegiatan dengan kode dan anggaran |
| `settings` | Key-value konfigurasi sistem |
| `rekap_config` | Konfigurasi TL/PSW (hidden, belum dirilis) |
| `dinas_blokir` | Sel dinas yang diblokir admin (per orang/per tanggal) |
| `surat_izin` | Surat izin pegawai dengan file lampiran |
| `rekap_manual` | Rekap manual ketidakhadiran per pegawai per bulan |
| `activity_logs` | Log aktivitas user (audit trail) |
| `arsip_folders` | Folder hierarki untuk modul Arsip Link |
| `arsip_links` | Link bookmark dengan icon preset, pin, favorit, count open |
| `arsip_tags` | Tag link bookmark |
| `arsip_link_tag` | Pivot link ↔ tag (many-to-many) |
| `roles` | Daftar role dinamis (name, label, deskripsi) |
| `permissions` | Daftar permission key per modul |
| `role_permission` | Pivot role ↔ permission (many-to-many) |

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

# Bersihkan log aktivitas lama (retention default 180 hari)
php artisan activity-log:prune

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

### v2.5 (Mei 2026)
- **RBAC dinamis (Manajemen Role & Permission)**: tabel `roles`, `permissions`, `role_permission` + seeder; halaman `/roles` untuk CRUD role + assign permission per modul
- **Permission middleware** (`permission:<key>`) menggantikan `role:` di semua route sensitif (dashboard, absensi, perjalanan dinas, pegawai, kode kegiatan, settings, log aktivitas, arsip, surat izin, rekap manual)
- **Helper user**: `hasPermission()` dan `hasAnyPermission()` dengan caching per request, blade `@can('<key>')` aktif via `Gate::before`
- **Sidebar adaptif**: menu otomatis disembunyikan jika user tidak punya permission terkait
- **Dashboard analitik**: chart Top 5 Tidak Apel (TA pagi vs siang) + Progress Anggaran Dinas per Menu BOK + aktivitas terbaru
- **Fix sidebar tidak bisa di-scroll**: `<aside>` jadi flex container vertikal, scrollbar custom tipis di area nav saat menu melebihi tinggi layar

### v2.4 (Mei 2026)
- **Modul Arsip Link**: bookmark manager dengan folder hierarki, tag, favorit per user, pin admin, search, drag-drop, dark mode
- **Tracking anggaran perjalanan dinas**: tarif konfigurabel via pengaturan (default Rp 80.000), snapshot per record, badge pagu/terpakai/sisa di Kode Kegiatan
- **Modal pemakai kode kegiatan diperluas**: kartu pagu tahunan dengan persentase terpakai + breakdown per pegawai dengan subtotal
- **SPJ perjalanan dinas**: tracking checklist SPJ per record dengan catatan + audit (`spj_checked_by`, `spj_checked_at`)
- **Filter pegawai search-based**: dropdown multi-select dengan search + chips reactive (ganti dari checkbox biasa)
- **Row Kepala di matriks dinas**: info absensi kepala dengan edit keterangan inline
- **Halaman publik dinas redesign**: matrix view per pegawai per tanggal (mengikuti halaman admin) + indikator SPJ + sel ketidakhadiran/blokir

### v2.3 (Mei 2026)
- **Modul Surat Izin**: kelola surat izin pegawai (sakit, izin, cuti) dengan upload file (PDF/gambar) + preview, filter pegawai/status/tanggal
- **Indikator surat di tabel absensi**: ikon dokumen pada tanggal yang punya surat izin
- **Modul Rekap Manual**: input rekap ketidakhadiran per pegawai per bulan dengan sistem poin otomatis
- **Log Aktivitas (Audit Trail)**: pencatatan otomatis aktivitas user (auth + CRUD seluruh modul) ke tabel `activity_logs`
  - Halaman `/log-aktivitas` (super_admin only) dengan filter tanggal, user, modul, event, keyword + modal detail JSON
  - Command `activity-log:prune` dijadwalkan harian jam 02:00 (retention konfigurabel via setting `activity_log_retention_days`)
  - Listener `LogAuthenticationActivity` untuk login, logout, failed login, lockout

### v2.2 (Mei 2026)
- **Konversi jam TA di Excel rekap**: status TA (Tidak Apel) di sheet APEL PAGI & APEL SIANG sekarang menampilkan jam yang sudah dikonversi (sama seperti H), bukan jam raw fingerprint
- **Input absensi fleksibel**: saat status Hadir, user tidak lagi wajib mengisi kedua jam (Apel Pagi & Apel Siang). Cukup isi salah satu, slot yang kosong tetap tersimpan dengan jam null
- **Validasi minimal**: minimal salah satu jam apel wajib diisi saat status Hadir (guard di frontend & backend)

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
