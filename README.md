# SULTAN BANTEN (Sistem Informasi Monitoring & Pelaporan UPT)

Aplikasi Monitoring dan Pelaporan Terpadu Unit Pelaksana Teknis (UPT) Pemasyarakatan Kantor Wilayah Banten berbasis Laravel.

---

## 📋 Daftar Isi
1. [Prasyarat Sistem](#-prasyarat-sistem)
2. [Panduan Deploy ke Shared Hosting (cPanel)](#-panduan-deploy-ke-shared-hosting-cpanel)
   - [Cara 1: Melalui Terminal / SSH cPanel (Sangat Direkomendasikan)](#cara-1-melalui-terminal--ssh-cpanel-sangat-direkomendasikan)
   - [Cara 2: Melalui Fitur GUI "Git™ Version Control" cPanel](#cara-2-melalui-fitur-gui-git-version-control-cpanel)
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

Deploy menggunakan **Git Clone** di cPanel jauh lebih cepat, rapi, dan mudah diperbarui (*update/pull*) dibandingkan upload file zip manual.

Ada 2 cara melakukan `git clone` di cPanel:
- [**Cara 1: Melalui Terminal / SSH cPanel (Sangat Direkomendasikan)**](#cara-1-melalui-terminal--ssh-cpanel-sangat-direkomendasikan)
- [**Cara 2: Melalui Fitur GUI "Git™ Version Control" cPanel**](#cara-2-melalui-fitur-gui-git-version-control-cpanel)

---

### Cara 1: Melalui Terminal / SSH cPanel (Sangat Direkomendasikan)

Metode ini adalah cara standar developer karena cepat dan memanfaatkan *symbolic link* sehingga untuk update di masa depan Anda cukup mengetikkan `git pull`.

#### Langkah 1: Buka Terminal & Clone Repository
1. Login ke dashboard **cPanel Anda**.
2. Buka menu **Terminal** (di bawah kategori *Advanced*).
3. Pastikan Anda berada di direktori root akun Anda (`/home/username`):
   ```bash
   cd ~
   ```
4. Jalankan perintah `git clone`:
   ```bash
   git clone https://github.com/wibi89-sys/sultan-banten-laravel.git sultan-banten
   ```
   *Repository akan terunduh ke folder `/home/username/sultan-banten`.*

#### Langkah 2: Install Dependency & Siapkan Environment
Masuk ke folder project yang baru di-clone:
```bash
cd ~/sultan-banten

# 1. Salin file environment
cp .env.example .env

# 2. Install dependency composer (tanpa dev package)
composer install --optimize-autoloader --no-dev

# 3. Generate application key
php artisan key:generate
```

#### Langkah 3: Setup Database di cPanel
1. Di tab browser cPanel baru, buka menu **MySQL® Database Wizard**.
2. Buat nama database baru (misal: `username_sultan`).
3. Buat user database baru dan password yang kuat.
4. Centang **ALL PRIVILEGES** lalu klik **Make Changes**.
5. Kembali ke Terminal cPanel, edit file `.env`:
   ```bash
   nano .env
   ```
   *(Atau edit `.env` melalui **cPanel File Manager** jika lebih nyaman).*
   
   Sesuaikan konfigurasi database:
   ```env
   APP_NAME="Sultan Banten"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://domainanda.com

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=username_sultan
   DB_USERNAME=username_userdb
   DB_PASSWORD=password_userdb_anda

   CRON_SECRET_KEY=kunci_acak_rahasia_cron_anda
   API_SECRET_KEY=kunci_acak_rahasia_api_anda
   ```
   *(Tekan `Ctrl+O` lalu `Enter` untuk menyimpan, dan `Ctrl+X` untuk keluar dari nano).*

6. Jalankan migrasi database langsung dari Terminal:
   ```bash
   php artisan migrate --force
   ```

#### Langkah 4: Hubungkan ke Web Root (`public_html`)

Web server cPanel secara bawaan menyajikan file dari folder `public_html`. Kita perlu menghubungkan folder `sultan-banten/public` ke `public_html`.

**Pilihan A: Menggunakan Symbolic Link (Paling Bersih & Otomatis)**
Jika domain utama Anda mengarah ke `public_html`:
1. Cadangkan atau hapus folder `public_html` bawaan yang masih kosong:
   ```bash
   # Hapus folder public_html kosong bawaan cPanel
   rm -rf ~/public_html
   ```
2. Buat symlink dari folder `public` Laravel ke `public_html`:
   ```bash
   ln -s ~/sultan-banten/public ~/public_html
   ```
3. Hubungkan juga storage upload publik:
   ```bash
   cd ~/sultan-banten
   php artisan storage:link
   ```
   *Selesai! Website Anda sekarang langsung aktif. Ketika ada update di GitHub, Anda cukup menjalankan `cd ~/sultan-banten && git pull && php artisan optimize`.*

---

**Pilihan B: Jika Menggunakan Subdomain / Addon Domain**
Jika aplikasi diletakkan pada subdomain (misal: `sultan.domainanda.com`):
1. Buka cPanel > **Domains**.
2. Klik domain/subdomain Anda, klik **Manage**.
3. Ubah **Document Root** langsung mengarah ke:
   ```text
   /home/username/sultan-banten/public
   ```
4. Klik **Update**. Tidak perlu memindahkan atau membuat symlink file apa pun!

---

**Pilihan C: Jika Hosting Melarang Symlink `public_html`**
Jika hosting Anda menonaktifkan pembuatan symlink ke `public_html`:
1. Salin seluruh isi folder `~/sultan-banten/public/` ke dalam `~/public_html/`:
   ```bash
   cp -r ~/sultan-banten/public/* ~/public_html/
   cp ~/sultan-banten/public/.htaccess ~/public_html/
   ```
2. Edit file `~/public_html/index.php` melalui File Manager atau nano:
   ```php
   // Ubah:
   require __DIR__.'/../vendor/autoload.php';
   $app = require_once __DIR__.'/../bootstrap/app.php';

   // Menjadi:
   require __DIR__.'/../sultan-banten/vendor/autoload.php';
   $app = require_once __DIR__.'/../sultan-banten/bootstrap/app.php';
   ```

---

### Cara 2: Melalui Fitur GUI "Git™ Version Control" cPanel

Jika paket hosting Anda **tidak memiliki akses menu Terminal / SSH**, Anda dapat menggunakan antarmuka grafis Git bawaan cPanel:

#### Langkah 1: Clone via cPanel GUI
1. Buka cPanel > cari dan klik menu **Git™ Version Control**.
2. Klik tombol **Create** di pojok kanan atas.
3. Isi form:
   - **Clone a Repository**: Aktifkan toggle / centang (ON).
   - **Clone URL**: `https://github.com/wibi89-sys/sultan-banten-laravel.git`
   - **Repository Path**: `sultan-banten` (otomatis akan ditempatkan di `/home/username/sultan-banten`).
   - **Repository Name**: `sultan-banten`
4. Klik tombol **Create**. Tunggu beberapa saat hingga cPanel selesai meng-clone repository.

#### Langkah 2: Setup File Environment & Vendor
1. Buka cPanel > **File Manager**.
2. Masuk ke folder `/home/username/sultan-banten`.
3. Klik kanan file `.env.example`, pilih **Copy**, beri nama baru `.env`.
4. Edit file `.env` dan masukkan detail database Anda (lihat petunjuk database di atas).
5. Buat zip folder `vendor` dari komputer lokal Anda, lalu upload dan ekstrak ke `/home/username/sultan-banten/vendor` (jika tidak ada akses Terminal untuk menjalankan `composer install`).

#### Langkah 3: Menghubungkan Web Root & Update di Masa Depan
1. Arahkan Document Root domain ke folder `sultan-banten/public` via menu cPanel **Domains**.
2. Kapan pun ada update terbaru di GitHub, Anda tinggal membuka menu **Git™ Version Control** di cPanel > klik **Manage** pada repository `sultan-banten` > klik tab **Pull or Deploy** > klik tombol **Update from Remote**.

---

### Setup Cron Job Scheduler di cPanel

Sistem Sultan Banten dilengkapi dengan otomatisasi laporan terjadwal & pengingat WhatsApp harian (`wa:cron run`).
1. Buka cPanel > **Cron Jobs**.
2. Pada bagian **Common Settings**, pilih **Once Per Minute (* * * * *)**.
3. Pada kolom **Command**, masukkan:
   ```bash
   /usr/local/bin/php /home/username/sultan-banten/artisan schedule:run >> /dev/null 2>&1
   ```
   *(Catatan: Path binary PHP dapat bervariasi di tiap hosting, seperti `/usr/bin/php` atau `/usr/local/ea-php83/root/usr/bin/php`. Anda dapat melihat path PHP versi aktif di halaman utama cPanel).*

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
