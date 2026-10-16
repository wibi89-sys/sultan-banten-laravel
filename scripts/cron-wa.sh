#!/usr/bin/env bash

# ==============================================================================
# Sultan Banten - WhatsApp Cron Job Runner
# ==============================================================================
# Script ini dirancang khusus untuk Crontab cPanel / Linux VPS.
# Otomatis mendeteksi PHP binary, berpindah ke direktori proyek,
# dan mencatat hasil eksekusi ke storage/logs/cron-wa.log.
#
# Penggunaan di Crontab (eksekusi setiap menit):
# * * * * * /bin/bash /home/ditjenpa/sultan.ditjenpasbanten.com/cron-wa.sh run > /dev/null 2>&1
# ==============================================================================

# 1. Tentukan direktori proyek
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if [ -f "$SCRIPT_DIR/artisan" ]; then
    PROJECT_DIR="$SCRIPT_DIR"
elif [ -f "$SCRIPT_DIR/../artisan" ]; then
    PROJECT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
else
    PROJECT_DIR="/home/ditjenpa/sultan.ditjenpasbanten.com"
fi

cd "$PROJECT_DIR" || exit 1

# 2. Deteksi PHP Binary yang valid
PHP_BIN=""
for candidate in \
    "/usr/local/bin/php" \
    "/usr/bin/php" \
    "/opt/cpanel/ea-php83/root/usr/bin/php" \
    "/opt/cpanel/ea-php82/root/usr/bin/php" \
    "$(which php 2>/dev/null)"
do
    if [ -x "$candidate" ]; then
        PHP_BIN="$candidate"
        break
    fi
done

if [ -z "$PHP_BIN" ]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: PHP binary tidak ditemukan!" >> "$PROJECT_DIR/storage/logs/cron-wa.log"
    exit 1
fi

# 3. Setup File Log
LOG_FILE="$PROJECT_DIR/storage/logs/cron-wa.log"
mkdir -p "$PROJECT_DIR/storage/logs"

# Rotasi log jika melebihi 5000 baris (mencegah disk penuh)
if [ -f "$LOG_FILE" ]; then
    LINE_COUNT=$(wc -l < "$LOG_FILE" 2>/dev/null || echo 0)
    if [ "$LINE_COUNT" -gt 5000 ]; then
        tail -n 1000 "$LOG_FILE" > "$LOG_FILE.tmp" && mv "$LOG_FILE.tmp" "$LOG_FILE"
    fi
fi

ACTION="${1:-run}"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] CRON STARTED - Action: $ACTION (PHP: $PHP_BIN)" >> "$LOG_FILE"

# 4. Jalankan perintah (utamakan Artisan, fallback ke cron-wa.php)
if [ -f "$PROJECT_DIR/artisan" ]; then
    OUTPUT=$("$PHP_BIN" "$PROJECT_DIR/artisan" wa:cron "$ACTION" 2>&1)
    EXIT_CODE=$?
elif [ -f "$PROJECT_DIR/cron-wa.php" ]; then
    OUTPUT=$("$PHP_BIN" "$PROJECT_DIR/cron-wa.php" "$ACTION" 2>&1)
    EXIT_CODE=$?
else
    OUTPUT="ERROR: Tidak menemukan artisan maupun cron-wa.php di $PROJECT_DIR"
    EXIT_CODE=1
fi

echo "$OUTPUT" >> "$LOG_FILE"
echo "[$(date '+%Y-%m-%d %H:%M:%S')] CRON FINISHED - Exit code: $EXIT_CODE" >> "$LOG_FILE"
echo "--------------------------------------------------------------------------------" >> "$LOG_FILE"

exit $EXIT_CODE
