# SULTAN BANTEN (Sistem Informasi Monitoring & Pelaporan UPT)

Aplikasi Monitoring dan Pelaporan Terpadu Unit Pelaksana Teknis (UPT) Pemasyarakatan Kantor Wilayah Banten berbasis Laravel.

---

## 📋 Daftar Isi
1. [Prasyarat Sistem](#-prasyarat-sistem)
2. [Panduan Deploy ke Shared Hosting (cPanel)](#-panduan-deploy-ke-shared-hosting-cpanel)
   - [Metode 1: Struktur Aman (Rekomendasi)](#metode-1-struktur-aman-di-luar-public_html-rekomendasi)
   - [Metode 2: Document Root Subdomain / Addon Domain](#metode-2-document-root-ke-folder-public)
   - [Setup Database di cPanel](#setup-database-di-cpanel)
   - [Setup Symlink Storage di cPanel](#setup-symlink-storage-di-cpanel)
   - [Setup Cron Job Scheduler di cPanel](#setup-cron-job-scheduler-di-cpanel)
3. [Panduan Deploy ke VPS Linux (Ubuntu / Debian)](#-panduan-deploy-ke-vps-linux-ubuntu--debian)
   - [1. Update Sistem & Install Kebutuhan](#1-update-sistem--install-kebutuhan)
   - [2. Konfigurasi MySQL Database](#2-konfigurasi-mysql-database)
   - [3. Clone Repository & Setup Environment](#3-clone-repository--setup-environment)
   - [4. Set Permission & Storage Link](#4-set-permission--storage-link)
   - [5. Konfigurasi Web Server (Nginx)](#5-konfigurasi-web-server-nginx)
   - [6. Pasang SSL Gratis (Let's Encrypt Certbot)](#6-pasang-ssl-gratis-lets-encrypt-certbot)
   - [7. Setup Crontab Scheduler](#7-setup-crontab-scheduler)
4. [Optimasi Production & Pemeliharaan](#-optimasi-production--pemeliharaan)
5. [Troubleshooting & Solusi Error](#-troubleshooting--solusi-error)

---

## ⚙️ Prasyarat Sistem

Pastikan hosting atau VPS Anda telah memenuhi persyaratan teknis berikut:
- **PHP**: Versi **8.2**, **8.3**, atau **8.4**
- **Ekstensi PHP Wajib**:
  - `BCMath`, `Ctype`, `cURL`, `DOM`, `Fileinfo`, `Filter`, `GD`, `Hash`, `Intl`, `JSON`, `Mbstring`, `OpenSSL`, `PCRE`, `PDO`, `PDO_MySQL`, `Session`, `Tokenizer`, `XML`, `ZIP`
- **Database**: MySQL 8.0+ atau MariaDB 10.4+
- **Composer**: Versi 2.x
- **Web Server**: Apache (dengan `mod_rewrite`) atau Nginx

---

## 🌐 Panduan Deploy ke Shared Hosting (cPanel)

Pada shared hosting cPanel, folder web publik utama umumnya adalah `public_html`. **Jangan pernah mengekstrak seluruh file Laravel langsung ke dalam `public_html` tanpa pemisahan**, karena file sensitif seperti `.env` bisa berisiko terakses publik.

### Metode 1: Struktur Aman di Luar `public_html` (Rekomendasi)

Struktur direktori yang akan dibuat di home akun cPanel (`/home/username/`):
```text
/home/username/
├── sultan-banten/        <-- Folder Core Laravel (di luar web root)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   └── .env
└── public_html/          <-- Isi dari folder public/ bawaan Laravel
    ├── assets/
    ├── uploads/
    ├── index.php
    └── .htaccess
```

#### Langkah-langkah:
1. **Compress Project Lokal**:
   - Di komputer lokal, compress project menjadi file `.zip` (kecualikan folder `/vendor` dan `.git` agar ukuran zip kecil).
2. **Upload & Ekstrak**:
   - Buka **cPanel** > **File Manager**.
   - Masuk ke root direktori pengguna (`/home/username/`), buat folder baru bernama `sultan-banten`.
   - Upload file zip ke dalam folder `sultan-banten` dan ekstrak di sana.
3. **Pindahkan Folder Public**:
   - Masuk ke folder `/home/username/sultan-banten/public/`.
   - Pindahkan seluruh isi folder tersebut ke dalam `/home/username/public_html/`.
4. **Sesuaikan Path pada `public_html/index.php`**:
   - Edit file `/home/username/public_html/index.php` menggunakan File Manager Editor.
   - Ubah baris pemanggilan bootstrap berikut:
     ```php
     // Cari baris ini:
     require __DIR__.'/../vendor/autoload.php';
     $app = require_once __DIR__.'/../bootstrap/app.php';

     // Ganti menjadi:
     require __DIR__.'/../sultan-banten/vendor/autoload.php';
     $app = require_once __DIR__.'/../sultan-banten/bootstrap/app.php';
     ```
5. **Install Vendor Dependency via SSH / Terminal cPanel**:
   - Buka menu **Terminal** di cPanel:
     ```bash
     cd ~/sultan-banten
     composer install --optimize-autoloader --no-dev
     ```
   *(Jika cPanel tidak memiliki akses SSH, Anda dapat meng-compress folder `vendor` dari lokal lalu meng-uploadnya).*

---

### Metode 2: Document Root ke Folder `public`

Jika Anda menggunakan **Addon Domain** atau **Subdomain** (atau paket cPanel yang mengizinkan perubahan Document Root domain utama):
1. Masuk ke cPanel > **Domains**.
2. Klik domain Anda dan arahkan **Document Root** langsung ke:
   ```text
   /home/username/sultan-banten/public
   ```
3. Upload seluruh project ke `/home/username/sultan-banten/`. Dengan cara ini, Anda tidak perlu memodifikasi `index.php`.

---

### Setup Database di cPanel

1. Buka cPanel > **MySQL® Database Wizard**.
2. Buat database baru, misalnya: `username_sultan`.
3. Buat user database baru dan buat password yang kuat.
4. Berikan hak akses penuh (**ALL PRIVILEGES**) kepada user tersebut untuk database yang dibuat.
5. Edit file `.env` di dalam folder core project (`sultan-banten/.env`):
   ```env
   APP_NAME="Sultan Banten"
   APP_ENV=production
   APP_KEY=base64:GENERATE_KEY_ANDA_DISINI
   APP_DEBUG=false
   APP_URL=https://domainanda.com

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=username_sultan
   DB_USERNAME=username_userdb
   DB_PASSWORD=password_userdb

   CRON_SECRET_KEY=buat_token_acak_aman_disini
   API_SECRET_KEY=buat_token_acak_aman_disini
   ```
6. Jalankan migrasi database via Terminal cPanel:
   ```bash
   cd ~/sultan-banten
   php artisan migrate --force
   ```

---

### Setup Symlink Storage di cPanel

Karena file unggahan profil/instansi disimpan di storage publik:
- **Jika memiliki akses Terminal cPanel**:
  ```bash
  cd ~/sultan-banten
  php artisan storage:link
  ```
- **Jika tanpa akses Terminal cPanel**:
  Buat route sementara di `routes/web.php`:
  ```php
  Route::get('/symlink-storage', function () {
      \Illuminate\Support\Facades\Artisan::call('storage:link');
      return 'Storage link berhasil dibuat!';
  });
  ```
  Akses URL `https://domainanda.com/symlink-storage` sekali di browser, lalu hapus route tersebut.

---

### Setup Cron Job Scheduler di cPanel

Sistem Sultan Banten dilengkapi dengan otomatisasi laporan terjadwal & pengingat WhatsApp (`wa:cron run`).
1. Buka cPanel > **Cron Jobs**.
2. Pada bagian **Common Settings**, pilih **Once Per Minute (* * * * *)**.
3. Pada kolom **Command**, masukkan:
   ```bash
   /usr/local/bin/php /home/username/sultan-banten/artisan schedule:run >> /dev/null 2>&1
   ```
   *(Sesuaikan path binary PHP sesuai versi cPanel Anda, misalnya `/usr/bin/php` atau `/usr/local/ea-php83/root/usr/bin/php`).*

---

## 🐧 Panduan Deploy ke VPS Linux (Ubuntu / Debian)

Panduan ini menggunakan stack modern: **Ubuntu 22.04/24.04 LTS**, **Nginx**, **PHP 8.3-FPM**, dan **MySQL 8.0**.

### 1. Update Sistem & Install Kebutuhan

```bash
# Update sistem
sudo apt update && sudo apt upgrade -y

# Tambahkan repository PHP Sury (jika belum ada)
sudo apt install -y software-properties-common ca-certificates lsb-release apt-transport-https
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install Nginx, MySQL, PHP 8.3 & ekstensi wajib
sudo apt install -y nginx mysql-server git unzip curl \
php8.3-fpm php8.3-mysql php8.3-common php8.3-bcmath php8.3-mbstring \
php8.3-xml php8.3-curl php8.3-gd php8.3-zip php8.3-intl php8.3-cli

# Install Composer global
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

---

### 2. Konfigurasi MySQL Database

```bash
sudo mysql
```

Di dalam shell MySQL:
```sql
CREATE DATABASE sultan_banten CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sultan_user'@'localhost' IDENTIFIED BY 'PasswordSangatKuat_123!';
GRANT ALL PRIVILEGES ON sultan_banten.* TO 'sultan_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

### 3. Clone Repository & Setup Environment

```bash
# Masuk ke direktori web root
cd /var/www

# Clone repository
sudo git clone https://github.com/wibi89-sys/sultan-banten-laravel.git sultan-banten
cd sultan-banten

# Salin konfigurasi environment
sudo cp .env.example .env

# Install dependency produksi
sudo composer install --optimize-autoloader --no-dev
```

Edit file `.env`:
```bash
sudo nano .env
```
Sesuaikan:
```env
APP_NAME="Sultan Banten"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://sultanbanten.domainanda.go.id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sultan_banten
DB_USERNAME=sultan_user
DB_PASSWORD=PasswordSangatKuat_123!

CRON_SECRET_KEY=masukkan_kunci_acak_rahasia_32_karakter
API_SECRET_KEY=masukkan_api_key_rahasia_32_karakter
```

Generate application key & jalankan migrasi database:
```bash
php artisan key:generate
php artisan migrate --force
```

---

### 4. Set Permission & Storage Link

Web server (`www-data`) harus memiliki hak tulis untuk folder `storage` dan `bootstrap/cache`:

```bash
# Ubah kepemilikan folder
sudo chown -R www-data:www-data /var/www/sultan-banten

# Berikan izin tulis direktori
sudo chmod -R 775 /var/www/sultan-banten/storage
sudo chmod -R 775 /var/www/sultan-banten/bootstrap/cache

# Buat symbolic link storage
php artisan storage:link
```

---

### 5. Konfigurasi Web Server (Nginx)

Buat file virtual host Nginx:
```bash
sudo nano /etc/nginx/sites-available/sultan-banten
```

Salin konfigurasi berikut:
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name sultanbanten.domainanda.go.id; # Ganti dengan domain Anda
    root /var/www/sultan-banten/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    index index.php index.html;
    charset utf-8;

    # Maksimum ukuran upload file foto/dokumen (sesuaikan kebutuhan)
    client_max_body_size 20M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Blokir akses ke file tersembunyi seperti .env dan .git
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Aktifkan konfigurasi dan restart Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/sultan-banten /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

### 6. Pasang SSL Gratis (Let's Encrypt Certbot)

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d sultanbanten.domainanda.go.id
```
Pilih opsi **Redirect HTTP to HTTPS** secara otomatis.

---

### 7. Setup Crontab Scheduler

Agar fitur laporan otomatis WhatsApp (`wa:cron run`) berjalan tepat waktu:

```bash
sudo crontab -e -u www-data
```
Tambahkan baris berikut di akhir file:
```bash
* * * * * cd /var/www/sultan-banten && php artisan schedule:run >> /dev/null 2>&1
```

---

## ⚡ Optimasi Production & Pemeliharaan

Jalankan perintah cache kompilasi berikut untuk performa maksimal pada server live:

```bash
# Cache konfigurasi, rute, dan blade template
php artisan config:cache
php artisan route:cache
php artisan view:cache

# (Atau cukup jalankan perintah satu langkah ini)
php artisan optimize
```

Jika sewaktu-waktu Anda memperbarui kode atau file `.env`:
```bash
# Bersihkan cache lama
php artisan optimize:clear

# Kompilasi ulang cache baru
php artisan optimize
```

---

## 🛠️ Troubleshooting & Solusi Error

| Masalah | Penyebab Umum | Solusi |
|---|---|---|
| **Error 500 (Internal Server Error)** | Izin folder atau file `.env` belum dikonfigurasi. | Periksa log di `storage/logs/laravel.log`. Pastikan folder `storage` & `bootstrap/cache` memiliki permission `775` dan kepemilikan `www-data`. |
| **Gambar Profil/Logo 404** | Symbolic link `storage` belum terpasang. | Jalankan `php artisan storage:link` di terminal. |
| **Error Database Access Denied** | Kredensial `.env` salah atau user DB belum diberi hak akses. | Periksa kembali `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` di `.env`, lalu jalankan `php artisan config:clear`. |
| **WhatsApp Otomatis Tidak Terkirim** | Scheduler Cron belum aktif di server. | Pastikan cron job `* * * * * php artisan schedule:run` sudah aktif pada cPanel atau crontab VPS. |
| **Fonnte API Gagal** | API Key kosong atau kuota WhatsApp Gateway habis. | Masuk ke menu **Pengaturan WhatsApp** (`/wa-settings`) di aplikasi dan lakukan uji pesan test. |

---

## 🔐 Catatan Keamanan Produksi
1. Pastikan `APP_DEBUG=false` pada environment produksi agar tidak membocorkan struktur kode saat terjadi error.
2. Jangan pernah menyimpan file `.env` di dalam folder public yang dapat diunduh langsung lewat browser.
3. Selalu simpan cadangan (*backup*) berkala database MySQL menggunakan cPanel Backup Wizard atau `mysqldump` pada VPS.
