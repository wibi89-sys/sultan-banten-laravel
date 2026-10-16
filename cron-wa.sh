#!/usr/bin/env bash

# Forward to scripts/cron-wa.sh
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if [ -f "$SCRIPT_DIR/scripts/cron-wa.sh" ]; then
    exec /bin/bash "$SCRIPT_DIR/scripts/cron-wa.sh" "$@"
else
    PHP_BIN="/usr/local/bin/php"
    [ ! -x "$PHP_BIN" ] && PHP_BIN="$(which php)"
    exec "$PHP_BIN" "$SCRIPT_DIR/artisan" wa:cron "${1:-run}"
fi
