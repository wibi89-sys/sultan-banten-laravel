<#
.SYNOPSIS
    Sultan Banten - WhatsApp Automation PowerShell Toolkit (Windows / Laragon)
.DESCRIPTION
    Script untuk menguji dan memantau cron otomatisasi WhatsApp di lokal Windows / Laragon.
.PARAMETER Action
    Pilihan action: status, run, morning, afternoon, scheduled, logs
.EXAMPLE
    .\scripts\wa-automation.ps1 -Action status
    .\scripts\wa-automation.ps1 -Action run
#>

param (
    [ValidateSet("status", "run", "morning", "afternoon", "scheduled", "logs")]
    [string]$Action = "status"
)

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$projectDir = Split-Path -Parent $scriptDir
Set-Location $projectDir

$phpBin = "php"

Write-Host "==================================================" -ForegroundColor Cyan
Write-Host " SULTAN BANTEN - WHATSAPP AUTOMATION TOOLKIT" -ForegroundColor Cyan
Write-Host "==================================================" -ForegroundColor Cyan

switch ($Action) {
    "status" {
        Write-Host "🔍 Memeriksa status otomatisasi WhatsApp..." -ForegroundColor Yellow
        & $phpBin artisan wa:cron status
    }
    "run" {
        Write-Host "🚀 Menjalankan seluruh alur WhatsApp Cron..." -ForegroundColor Green
        & $phpBin artisan wa:cron run
    }
    "morning" {
        Write-Host "🌅 Mengirim Reminder Pagi ke Operator..." -ForegroundColor Yellow
        & $phpBin artisan wa:cron morning
    }
    "afternoon" {
        Write-Host "📊 Mengirim Laporan Siang ke Grup..." -ForegroundColor Yellow
        & $phpBin artisan wa:cron afternoon
    }
    "scheduled" {
        Write-Host "⏰ Memproses Laporan Terjadwal yang jatuh tempo..." -ForegroundColor Yellow
        & $phpBin artisan wa:cron scheduled
    }
    "logs" {
        $logFile = Join-Path $projectDir "storage\logs\cron-wa.log"
        if (Test-Path $logFile) {
            Write-Host "📄 30 Baris Log Terakhir dari $logFile:" -ForegroundColor Green
            Get-Content $logFile -Tail 30
        } else {
            Write-Host "ℹ️ Belum ada file log $logFile." -ForegroundColor Yellow
        }
    }
}
