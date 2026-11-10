#!/usr/bin/env bash

# ==============================================================================
# Sultan Banten - One-Step Server Update Shortcut
# ==============================================================================
# Jalankan perintah ini di root folder proyek pada terminal hosting / SSH:
#   bash update.sh
# ==============================================================================

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
bash "$SCRIPT_DIR/scripts/deploy-update.sh" "$@"

