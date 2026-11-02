# SULTAN Banten - Android Native APK Wrapper

Project Android Studio resmi untuk membungkus Web & PWA **SULTAN Banten** menjadi file aplikasi Android fisik (**`.apk`**) siap instal.

---

## 🚀 Fitur Native yang Disediakan
1. **Layar Penuh (Full Immersive)**: Berjalan tanpa bilah alamat URL (address bar) browser, persis seperti aplikasi native murni.
2. **Navigasi Tombol Kembali HP (*Hardware Back Button*)**: Saat tombol Back HP ditekan, halaman web mundur ke belakang (tidak langsung keluar aplikasi). Tekan 2 kali untuk keluar.
3. **Upload Berkas & Kamera (*File Chooser*)**: Mendukung upload bukti foto, dokumen PDF, dan foto profil langsung dari Galeri atau Kamera smartphone.
4. **Download Manager Otomatis**: Mendukung unduh laporan cetak PDF dan Excel langsung tersimpan di folder `Downloads` HP dengan notifikasi unduhan sistem Android.
5. **Dukungan Integrasi Eksternal**: Tautan WhatsApp (`wa.me` / `whatsapp://`), panggilan telepon (`tel:`), dan Google Maps otomatis membuka aplikasi WhatsApp/Telepon/Maps native di perangkat.
6. **Tarik Layar untuk Muat Ulang (*Swipe-to-Refresh*)**: Tarik layar dari atas ke bawah untuk memperbarui data apel atau laporan.
7. **Pembaruan Instan (*Zero Maintenance Update*)**: Setiap kali Anda melakukan `git pull` di server hosting, seluruh konten di dalam APK HP petugas otomatis terbarui detik itu juga tanpa perlu build ulang APK!

---

## 🛠️ Langkah-Langkah Build APK di Android Studio

### 1. Buka Project di Android Studio
1. Buka aplikasi **Android Studio**.
2. Pilih menu **File &rarr; Open...** (atau di layar awal pilih *Open*).
3. Arahkan dan pilih folder:
   ```
   c:\laragon\www\sultan-banten-laravel\android
   ```
4. Tunggu beberapa saat hingga Android Studio selesai melakukan **Gradle Sync**.

### 2. Atur Alamat Domain Hosting Anda
Buka berkas:
[`android/app/src/main/java/com/kemenkumham/sultanbanten/MainActivity.java`](file:///c:/laragon/www/sultan-banten-laravel/android/app/src/main/java/com/kemenkumham/sultanbanten/MainActivity.java)

Pada baris ke-47, ubah `TARGET_URL` dengan domain hosting Sultan Banten Anda:
```java
public static final String TARGET_URL = "https://sultanbanten.kemenkumham.go.id"; // Ganti dengan domain hosting Anda
```

### 3. Generate File `.apk` Siap Pakai
1. Di menu atas Android Studio, klik:
   **Build &rarr; Build Bundle(s) / APK(s) &rarr; Build APK(s)**
2. Tunggu proses build (biasanya 1–2 menit).
3. Ketika muncul notifikasi di pojok kanan bawah:
   `APK(s) generated successfully for module 'app'.`
   Klik tombol **locate** pada notifikasi tersebut.
4. File **`app-debug.apk`** siap diambil, diubah namanya menjadi **`SultanBanten.apk`**, dan dibagikan langsung lewat WhatsApp atau flashdisk ke seluruh UPT/petugas!

---

## 📱 Build Release APK (Opsional untuk Play Store / Produksi)
Jika ingin membuat file release bertanda tangan (*Signed Release APK*):
1. Klik **Build &rarr; Generate Signed Bundle / APK...**
2. Pilih **APK** &rarr; Next.
3. Buat Key store baru (*Create new...*) atau gunakan Keystore yang sudah ada.
4. Pilih build variant **release** &rarr; Finish.
5. File APK release yang dihasilkan siap dipasang di seluruh smartphone Android (Android 7.0 hingga Android 14+).

