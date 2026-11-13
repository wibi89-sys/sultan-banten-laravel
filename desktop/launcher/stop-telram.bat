@echo off
setlocal
title Stop Kalkulator Telram Offline Server

set "PORT=8192"

echo Menghentikan micro-server lokal Kalkulator Telram pada port %PORT%...

for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":%PORT% "') do (
    if not "%%a"=="" (
        taskkill /f /pid %%a >nul 2>&1
    )
)

echo Micro-server berhasil dihentikan.
timeout /t 1 /nobreak >nul 2>&1
exit /b 0
