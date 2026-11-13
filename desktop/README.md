# Kalkulator Telram & Integrasi SDP - Desktop Offline Package
**Kantor Wilayah Ditjenpas Banten**

Folder ini berisi seluruh instrumen, konfigurasi, skrip launcher, dan compiler untuk menghasilkan **Aplikasi Desktop Mandiri (Full Fitur & 100% Offline)** untuk Kalkulator Telram & Integrasi SDP.

---

## 🎯 Mengapa Versi Desktop Ini Unggul?
1. **100% Offline Penuh**: Tidak memerlukan koneksi internet untuk menghitung remisi, masa pidana, riwayat penahanan bertahap, maupun mencetak berkas PDF resmi Ditjenpas.
2. **Kalkulasi Akurat & Tetap Sinkron**: Menggunakan engine perhitungan backend Laravel (`TelramCalculatorService`) yang sama persis dengan versi web portal Sultan Banten, sehingga tidak ada perbedaan rumus (*no code drift*).
3. **Dedicated App Window**: Berjalan pada jendela tersendiri (berbasis Microsoft Edge WebView / App Mode) tanpa bilah URL browser dan memiliki icon tersendiri di taskbar Windows.
4. **Ringan & Hemat Daya**: Menggunakan micro-server PHP 8.3 portable dengan database in-memory/SQLite lokal, mengonsumsi RAM < 35 MB.

---

## 📁 Struktur Folder `desktop/`

```text
desktop/
├── README.md                      # Dokumentasi & panduan teknis
├── build.ps1                      # Skrip PowerShell otomatis untuk merakit installer
├── .gitignore                     # Memastikan file installer binary raksasa tidak membengkak di Git
├── config/
│   ├── php.ini                    # Konfigurasi runtime PHP portable offline
│   └── .env.desktop               # Konfigurasi environment Laravel offline (SQLite)
├── launcher/
│   ├── run-telram.bat             # Skrip batch runner (Micro-server + App window)
│   ├── stop-telram.bat            # Skrip pembersih proses server di port 8192
│   ├── TelramLauncher.vbs         # Launcher silent (tanpa pop-up jendela CMD hitam)
│   └── app-icon.ico               # Ikon resmi aplikasi
├── inno-setup/
│   └── telram-installer.iss       # Skrip Inno Setup Compiler untuk installer .exe
└── dist/                          # Output file installer .exe & paket portable zip
```

---

## 🚀 Cara Merakit / Build Installer Baru

Kapan pun ada pembaruan rumus remisi atau tampilan di project `sultan-banten-laravel`, Anda dapat merakit ulang installer desktop dengan langkah mudah:

1. Buka PowerShell di folder project:
   ```powershell
   cd c:\laragon\www\sultan-banten-laravel\desktop
   .\build.ps1
   ```
2. Skrip akan otomatis:
   - Menyalin controller, view, dan aset CSS/JS terbaru.
   - Mengemas PHP portable dengan ekstensi lengkap (`sqlite3`, `curl`, `gd`, `mbstring`, `fileinfo`).
   - Membuat file **`desktop/dist/Kalkulator_Telram_Offline_Portable.zip`**.
   - Jika **Inno Setup** terpasang, otomatis menghasilkan installer **`desktop/dist/Setup_Kalkulator_Telram_Ditjenpas_Banten.exe`**.

> **Tips Memasang Inno Setup Compiler di Windows:**
> Jika belum terpasang, cukup jalankan satu perintah di terminal Windows:
> ```powershell
> winget install JRSoftware.InnoSetup
> ```
> Setelah itu jalankan kembali `.\build.ps1`.

---

## 💻 Penggunaan di PC Petugas Lapas / Rutan / Bapas
Petugas cukup menjalankan `Setup_Kalkulator_Telram_Ditjenpas_Banten.exe` (atau mengekstrak paket ZIP Portable), lalu klik dua kali ikon **Kalkulator Telram & Integrasi SDP** di Desktop Windows. Aplikasi akan langsung terbuka secara offline dan siap digunakan!
