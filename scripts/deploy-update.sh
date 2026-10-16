#!/usr/bin/env bash

# ==============================================================================
# Sultan Banten - One-Step GitHub Pull & Server Update Script
# ==============================================================================
# Jalankan script ini di cPanel / VPS via SSH untuk memperbarui kode dari GitHub:
#   bash scripts/deploy-update.sh
# ==============================================================================

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_DIR"

echo "=================================================="
echo "🚀 SULTAN BANTEN - UPDATE FROM GITHUB"
echo "=================================================="
echo "📁 Direktori: $PROJECT_DIR"
echo "⏰ Waktu    : $(date '+%Y-%m-%d %H:%M:%S')"
echo "--------------------------------------------------"

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

echo "1. Menarik commit terbaru dari GitHub (origin main)..."
git pull origin main

echo "2. Memberikan izin eksekusi pada script shell..."
chmod +x cron-wa.sh scripts/*.sh 2>/dev/null || true

echo "3. Membersihkan cache konfigurasi lama..."
"$PHP_BIN" artisan optimize:clear

echo "4. Mengompilasi ulang cache Laravel untuk performa..."
"$PHP_BIN" artisan optimize

echo "5. Memeriksa status otomatisasi WhatsApp..."
"$PHP_BIN" artisan wa:cron status

echo "=================================================="
echo "✅ UPDATE BERHASIL DISELESAIKAN!"
echo "=================================================="
