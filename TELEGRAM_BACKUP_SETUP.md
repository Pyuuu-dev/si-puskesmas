# Telegram Backup Bot Setup

## Cara Setup Telegram Bot untuk Backup Database

### 1. Buat Bot Telegram Baru

1. Buka Telegram dan cari `@BotFather`
2. Kirim perintah `/newbot`
3. Ikuti instruksi untuk memberi nama bot Anda
4. Setelah selesai, Anda akan mendapatkan **Bot Token** (contoh: `123456789:ABCdefGHIjklMNOpqrsTUVwxyz`)
5. Simpan token ini dengan aman

### 2. Dapatkan Chat ID

Ada beberapa cara untuk mendapatkan Chat ID:

#### Cara 1: Menggunakan Bot GetID
1. Cari bot `@userinfobot` di Telegram
2. Kirim perintah `/start`
3. Bot akan mengirimkan informasi Anda termasuk **Chat ID**

#### Cara 2: Menggunakan API Telegram
1. Kirim pesan ke bot yang baru Anda buat
2. Buka browser dan akses: `https://api.telegram.org/bot<BOT_TOKEN>/getUpdates`
3. Ganti `<BOT_TOKEN>` dengan token bot Anda
4. Cari field `"chat":{"id":123456789}` - angka tersebut adalah Chat ID Anda

### 3. Konfigurasi di Laravel

1. Buka file `.env` di root project
2. Tambahkan konfigurasi berikut:

```env
TELEGRAM_BOT_TOKEN=123456789:ABCdefGHIjklMNOpqrsTUVwxyz
TELEGRAM_CHAT_ID=123456789
```

Ganti dengan Bot Token dan Chat ID yang Anda dapatkan.

### 4. Test Backup Manual

Jalankan perintah berikut untuk test backup:

```bash
php artisan backup:telegram
```

Jika berhasil, Anda akan menerima file backup database di Telegram.

### 5. Setup Cron Job untuk Backup Otomatis

Backup otomatis akan berjalan 3x sehari (08:00, 14:00, 20:00 WITA).

Tambahkan cron job berikut di server:

```bash
* * * * * cd /var/www/puskesmas && php artisan schedule:run >> /dev/null 2>&1
```

Atau jika menggunakan crontab:

```bash
crontab -e
```

Lalu tambahkan baris:

```
* * * * * cd /var/www/puskesmas && php artisan schedule:run >> /dev/null 2>&1
```

### 6. Verifikasi Schedule

Untuk melihat daftar scheduled tasks:

```bash
php artisan schedule:list
```

### 7. Test Schedule Secara Manual

Untuk menjalankan semua scheduled tasks secara manual:

```bash
php artisan schedule:run
```

## Troubleshooting

### Bot tidak mengirim backup

1. Pastikan Bot Token dan Chat ID sudah benar di file `.env`
2. Pastikan bot sudah di-start dengan mengirim `/start` ke bot
3. Cek log Laravel di `storage/logs/laravel.log`
4. Pastikan file database ada di `database/database.sqlite`

### Cron job tidak berjalan

1. Pastikan cron job sudah ditambahkan dengan benar
2. Cek apakah cron service berjalan: `sudo systemctl status cron`
3. Cek log cron: `grep CRON /var/log/syslog`

### Permission error

Pastikan folder `storage/app/backups` memiliki permission yang benar:

```bash
chmod -R 775 storage/app/backups
chown -R www-data:www-data storage/app/backups
```

## Fitur

- ✅ Backup otomatis 3x sehari (08:00, 14:00, 20:00 WITA)
- ✅ Backup manual dengan command `php artisan backup:telegram`
- ✅ File backup dikirim langsung ke Telegram
- ✅ Backup file diberi timestamp untuk identifikasi
- ✅ Automatic cleanup setelah backup dikirim

## Keamanan

⚠️ **PENTING**: 
- Jangan share Bot Token ke orang lain
- Jangan commit file `.env` ke git
- Pastikan hanya Anda yang memiliki akses ke bot
- Backup file berisi data sensitif, jaga kerahasiaan chat Telegram Anda
