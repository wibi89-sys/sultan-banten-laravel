#!/usr/bin/env bash

# ==============================================================================
# Sultan Banten - WhatsApp Automation Management CLI Toolkit
# ==============================================================================
# Gunakan script ini untuk menguji, memantau, dan menjalankan otomatisasi WA.
#
# Cara Penggunaan:
#   ./scripts/wa-automation.sh status       Cek status koneksi WA & jadwal aktif
#   ./scripts/wa-automation.sh run          Jalankan cron lengkap (pagi + siang + terjadwal)
#   ./scripts/wa-automation.sh morning      Kirim reminder pagi sekarang
#   ./scripts/wa-automation.sh afternoon    Kirim laporan siang sekarang
#   ./scripts/wa-automation.sh scheduled    Proses semua jadwal aktif sekarang
#   ./scripts/wa-automation.sh logs         Lihat 30 baris log terakhir pengiriman WA
#   ./scripts/wa-automation.sh crontab      Lihat instruksi crontab untuk server
# ==============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_DIR" || exit 1

PHP_BIN=""
for candidate in "/usr/local/bin/php" "/usr/bin/php" "/opt/cpanel/ea-php83/root/usr/bin/php" "$(which php 2>/dev/null)"; do
    if [ -x "$candidate" ]; then
        PHP_BIN="$candidate"
        break
    fi
done

if [ -z "$PHP_BIN" ]; then
    echo "❌ Error: PHP binary tidak ditemukan!"
    exit 1
fi

ACTION="${1:-status}"

case "$ACTION" in
    status)
        echo "🔍 Memeriksa status otomatisasi WhatsApp..."
        "$PHP_BIN" artisan wa:cron status
        ;;
    run)
        echo "🚀 Menjalankan seluruh alur WhatsApp Cron..."
        "$PHP_BIN" artisan wa:cron run
        ;;
    morning)
        echo "🌅 Mengirim Reminder Pagi ke Operator..."
        "$PHP_BIN" artisan wa:cron morning
        ;;
    afternoon)
        echo "📊 Mengirim Laporan Siang ke Grup..."
        "$PHP_BIN" artisan wa:cron afternoon
        ;;
    scheduled)
        echo "⏰ Memproses Laporan Terjadwal yang jatuh tempo..."
        "$PHP_BIN" artisan wa:cron scheduled
        ;;
    logs)
        LOG_FILE="$PROJECT_DIR/storage/logs/cron-wa.log"
        if [ -f "$LOG_FILE" ]; then
            echo "📄 30 Baris Log Terakhir dari $LOG_FILE:"
            echo "=================================================="
            tail -n 30 "$LOG_FILE"
        else
            echo "ℹ️ Belum ada file log $LOG_FILE. Silakan jalankan cron terlebih dahulu."
        fi
        ;;
    crontab)
        echo "=================================================="
        echo "📋 FORMAT CRONTAB UNTUK CPANEL / LINUX SERVER"
        echo "=================================================="
        echo "Jalankan 'crontab -e' di server, lalu tempel salah satu baris berikut:"
        echo ""
        echo "1) Opsi Standar Laravel Scheduler (Rekomendasi):"
        echo "* * * * * cd $PROJECT_DIR && $PHP_BIN artisan schedule:run >> /dev/null 2>&1"
        echo ""
        echo "2) Opsi Langsung Shell Script (dengan log otomatis):"
        echo "* * * * * /bin/bash $PROJECT_DIR/cron-wa.sh run > /dev/null 2>&1"
        echo ""
        echo "3) Opsi Langsung PHP CLI:"
        echo "* * * * * $PHP_BIN $PROJECT_DIR/cron-wa.php run > /dev/null 2>&1"
        echo "=================================================="
        ;;
    *)
        echo "Penggunaan: $0 {status|run|morning|afternoon|scheduled|logs|crontab}"
        exit 1
        ;;
esac
