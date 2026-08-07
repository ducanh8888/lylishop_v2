#!/usr/bin/env bash
# Back up the production database and uploads before a deploy or on a schedule.
# Runs on the shared host. Never prints credentials to stdout or logs.
set -euo pipefail

APP_DIR="${APP_DIR:-$HOME/apps/lylishop}"
BACKUP_DIR="${APP_DIR}/shared/backups/$(date +%Y%m%d%H%M%S)"
PHP_BIN="${PHP_BIN:-/opt/alt/php83/usr/bin/php}"
WP_CLI_BIN="${WP_CLI_BIN:-/usr/bin/wp}"
WP_PATH="${APP_DIR}/current/web/wp"

if [ ! -f "${APP_DIR}/shared/.env" ]; then
    echo "ERROR: ${APP_DIR}/shared/.env not found; nothing to back up yet." >&2
    exit 1
fi

if [ ! -x "$PHP_BIN" ] || [ ! -f "$WP_CLI_BIN" ] || [ ! -d "$WP_PATH" ]; then
    echo "ERROR: PHP 8.3, WP-CLI, or WordPress path unavailable; backup not started." >&2
    exit 1
fi

mkdir -p "$BACKUP_DIR"

echo "Dumping production database..."
"$PHP_BIN" "$WP_CLI_BIN" --path="$WP_PATH" db export "${BACKUP_DIR}/database.sql" --quiet
gzip "${BACKUP_DIR}/database.sql"

echo "Archiving production uploads..."
if [ -d "${APP_DIR}/shared/uploads" ]; then
    tar -czf "${BACKUP_DIR}/uploads.tar.gz" -C "${APP_DIR}/shared" uploads
fi

echo "Backup written to ${BACKUP_DIR}"
echo "Remember: copy this off-site; the host is not the only copy (docs/BACKUP-RESTORE.md)."
echo "${BACKUP_DIR}" > "${APP_DIR}/shared/backups/.last-backup"
