<#
.SYNOPSIS
    SULTAN Banten - Automated Desktop Offline Builder
    Kantor Wilayah Ditjenpas Banten

.DESCRIPTION
    Skrip otomatis untuk merakit bundel offline Kalkulator Telram:
    1. Menyiapkan staging directory (desktop/build_stage)
    2. Menyalin Core Laravel Telram, View, Controller, Service, dan Aset
    3. Mengemas PHP 8.3 portable dengan konfigurasi offline
    4. Mengemas launcher silent (VBS/BAT)
    5. Menghasilkan paket ZIP Portable mandiri
    6. (Opsional) Mengompilasi Setup_Kalkulator_Telram_Ditjenpas_Banten.exe jika Inno Setup terpasang
#>

param(
    [switch]$SkipInstaller = $false
)

$ErrorActionPreference = "Stop"
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Resolve-Path "$ScriptDir\.."
$BuildStage = Join-Path $ScriptDir "build_stage"
$DistDir = Join-Path $ScriptDir "dist"

Write-Host "============================================================" -ForegroundColor Cyan
Write-Host " SULTAN BANTEN - KALKULATOR TELRAM DESKTOP BUILDER" -ForegroundColor Yellow
Write-Host " Kantor Wilayah Ditjenpas Banten" -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "Project Root : $ProjectRoot"
Write-Host "Build Stage  : $BuildStage"
Write-Host "Dist Output  : $DistDir"
Write-Host ""

# 1. Bersihkan staging directory lama
if (Test-Path $BuildStage) {
    Write-Host "[1/6] Membersihkan staging directory lama..." -ForegroundColor Gray
    Remove-Item -Path $BuildStage -Recurse -Force
}
New-Item -ItemType Directory -Path $BuildStage | Out-Null
New-Item -ItemType Directory -Path $DistDir -Force | Out-Null

# 2. Salin Core Laravel & Aset
Write-Host "[2/6] Menyalin berkas aplikasi Laravel & aset ke build stage..." -ForegroundColor Green
$foldersToCopy = @("app", "bootstrap", "config", "database", "public", "resources", "routes", "vendor")
foreach ($folder in $foldersToCopy) {
    $src = Join-Path $ProjectRoot $folder
    $dest = Join-Path $BuildStage $folder
    if (Test-Path $src) {
        Write-Host "  -> Menyalin $folder..." -ForegroundColor DarkGray
        Copy-Item -Path $src -Destination $dest -Recurse -Force
    }
}

# Salin file artisan
Copy-Item -Path (Join-Path $ProjectRoot "artisan") -Destination (Join-Path $BuildStage "artisan") -Force

# Buat folder storage kosong dengan subfolder wajib
$storagePaths = @(
    "storage\app\public",
    "storage\framework\cache\data",
    "storage\framework\sessions",
    "storage\framework\views",
    "storage\logs"
)
foreach ($sp in $storagePaths) {
    New-Item -ItemType Directory -Path (Join-Path $BuildStage $sp) -Force | Out-Null
}

# Terapkan konfigurasi .env.desktop untuk offline
Write-Host "[3/6] Menerapkan konfigurasi offline (.env.desktop & SQLite)..." -ForegroundColor Green
Copy-Item -Path (Join-Path $ScriptDir "config\.env.desktop") -Destination (Join-Path $BuildStage ".env") -Force

# Inisialisasi file SQLite kosong jika belum ada
$sqlitePath = Join-Path $BuildStage "database\desktop.sqlite"
if (-not (Test-Path $sqlitePath)) {
    New-Item -ItemType File -Path $sqlitePath -Force | Out-Null
}

# Salin launcher
Write-Host "[4/6] Menyalin launcher desktop (VBS & BAT)..." -ForegroundColor Green
$destLauncher = Join-Path $BuildStage "launcher"
New-Item -ItemType Directory -Path $destLauncher -Force | Out-Null
Copy-Item -Path (Join-Path $ScriptDir "launcher\*") -Destination $destLauncher -Recurse -Force

# 5. Salin PHP Portable Environment
Write-Host "[5/6] Menyiapkan runtime PHP Portable..." -ForegroundColor Green
$phpSource = (Get-Command php -ErrorAction SilentlyContinue).Source
if ($phpSource) {
    $phpDir = Split-Path -Parent $phpSource
    $destPhp = Join-Path $BuildStage "php"
    New-Item -ItemType Directory -Path $destPhp -Force | Out-Null
    
    # Salin binary PHP dan DLL penting
    Copy-Item -Path (Join-Path $phpDir "*.exe") -Destination $destPhp -Force
    Copy-Item -Path (Join-Path $phpDir "*.dll") -Destination $destPhp -Force -ErrorAction SilentlyContinue
    if (Test-Path (Join-Path $phpDir "ext")) {
        Copy-Item -Path (Join-Path $phpDir "ext") -Destination $destPhp -Recurse -Force
    }
    
    # Terapkan php.ini offline khusus Telram
    Copy-Item -Path (Join-Path $ScriptDir "config\php.ini") -Destination (Join-Path $destPhp "php.ini") -Force
} else {
    Write-Warning "PHP tidak ditemukan di PATH sistem. Pastikan PHP 8.3 tersedia di folder php/!"
}

# 6. Buat Paket ZIP Portable Mandiri
Write-Host "[6/6] Mengemas paket ZIP Portable mandiri..." -ForegroundColor Green
$zipOutput = Join-Path $DistDir "Kalkulator_Telram_Offline_Portable.zip"
if (Test-Path $zipOutput) { Remove-Item $zipOutput -Force }
Compress-Archive -Path "$BuildStage\*" -DestinationPath $zipOutput -CompressionLevel Optimal
Write-Host "  -> Berhasil dibuat: $zipOutput" -ForegroundColor Cyan

# 7. Cek Inno Setup Compiler untuk membuat file .exe installer
$innoPaths = @(
    "C:\Program Files (x86)\Inno Setup 6\ISCC.exe",
    "C:\Program Files\Inno Setup 6\ISCC.exe",
    "C:\Users\$env:USERNAME\AppData\Local\Programs\Inno Setup 6\ISCC.exe"
)
$iscc = $innoPaths | Where-Object { Test-Path $_ } | Select-Object -First 1

if ($iscc -and -not $SkipInstaller) {
    Write-Host ""
    Write-Host "Mengompilasi Installer Windows (.exe) via Inno Setup..." -ForegroundColor Yellow
    $issFile = Join-Path $ScriptDir "inno-setup\telram-installer.iss"
    & "$iscc" "$issFile"
    Write-Host "Installer .exe berhasil dibuat di folder desktop/dist/!" -ForegroundColor Green
} else {
    Write-Host ""
    Write-Host "Catatan Installer .EXE:" -ForegroundColor DarkYellow
    Write-Host "Inno Setup Compiler (ISCC.exe) belum terpasang di sistem ini." -ForegroundColor Gray
    Write-Host "Untuk menghasilkan Setup_Kalkulator_Telram_Ditjenpas_Banten.exe:" -ForegroundColor Gray
    Write-Host "  Jalankan perintah: winget install JRSoftware.InnoSetup" -ForegroundColor Cyan
    Write-Host "Paket ZIP Portable mandiri sudah siap digunakan di desktop/dist/!" -ForegroundColor Green
}

Write-Host ""
Write-Host "BUILD SUKSES!" -ForegroundColor Green
