@echo off
setlocal enabledelayedexpansion
title SULTAN Banten - Kalkulator Telram Offline Launcher

:: Navigasi ke direktori instalasi aplikasi
cd /d "%~dp0\.."

set "PORT=8192"
set "APP_URL=http://127.0.0.1:%PORT%/telram"
set "PHP_EXE=php\php.exe"
set "PHP_INI=php\php.ini"

:: Cek keberadaan PHP portable
if not exist "%PHP_EXE%" (
    echo [ERROR] PHP Portable tidak ditemukan pada folder: %PHP_EXE%
    echo Harap jalankan installer resmi atau ekstrak bundel dengan lengkap.
    pause
    exit /b 1
)

:: Cek apakah server PHP lokal sudah berjalan pada port tersebut
netstat -ano | findstr ":%PORT% " >nul 2>&1
if errorlevel 1 (
    echo Memulai micro-server lokal Kalkulator Telram...
    start /b "" "%PHP_EXE%" -c "%PHP_INI%" -S 127.0.0.1:%PORT% -t public >nul 2>&1
    :: Tunggu 1.5 detik agar socket siap menerima request
    timeout /t 2 /nobreak >nul 2>&1
)

:: Luncurkan jendela aplikasi (Dedicated App Mode tanpa address bar)
:: 1. Coba Microsoft Edge (tersedia secara bawaan di semua Windows 10/11)
if exist "%ProgramFiles(x86)%\Microsoft\Edge\Application\msedge.exe" (
    start "" "%ProgramFiles(x86)%\Microsoft\Edge\Application\msedge.exe" --app="%APP_URL%" --window-size=1280,840
    goto :done
)
if exist "%ProgramFiles%\Microsoft\Edge\Application\msedge.exe" (
    start "" "%ProgramFiles%\Microsoft\Edge\Application\msedge.exe" --app="%APP_URL%" --window-size=1280,840
    goto :done
)

:: 2. Coba Google Chrome jika ada
if exist "%ProgramFiles%\Google\Chrome\Application\chrome.exe" (
    start "" "%ProgramFiles%\Google\Chrome\Application\chrome.exe" --app="%APP_URL%" --window-size=1280,840
    goto :done
)
if exist "%ProgramFiles(x86)%\Google\Chrome\Application\chrome.exe" (
    start "" "%ProgramFiles(x86)%\Google\Chrome\Application\chrome.exe" --app="%APP_URL%" --window-size=1280,840
    goto :done
)

:: 3. Fallback browser default
start "" "%APP_URL%"

:done
exit /b 0
