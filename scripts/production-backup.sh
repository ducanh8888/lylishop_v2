#!/usr/bin/env bash
# Step 2-3 of docs/DEPLOYMENT.md: backs up the production database and uploads before
# a deploy (or on a schedule). Runs ON the shared host. Upload the file with scp,
# execute it there with a timeout, then remove the temporary copy; do not pipe the
# script into an interactive remote shell. Cron must call PHP 8.3 explicitly because
# the host default is PHP 8.1.
# Never parses or prints .env credentials; WP-CLI loads Bedrock normally.
set -euo pipefail

APP_DIR="${APP_DIR:-$HOME/apps/lylishop}"
BACKUP_DIR="${APP_DIR}/shared/backups/$(date +%Y%m%d%H%M%S)"
PHP_BIN="${PHP_BIN:-/opt/alt/php83/usr/bin/php}"
WP_CLI_BIN="${WP_CLI_BIN:-/usr/bin/wp}"
WP_PATH="${WP_PATH:-${APP_DIR}/current/web/wp}"

if [ ! -e "$WP_PATH" ]; then
    echo "ERROR: WordPress path not found: $WP_PATH" >&2
    exit 1
fi

mkdir -p "$BACKUP_DIR"

echo "Dumping production database..."
"$PHP_BIN" "$WP_CLI_BIN" --path="$WP_PATH" db export \
    "${BACKUP_DIR}/database.sql" --single-transaction
gzip "${BACKUP_DIR}/database.sql"
gzip -t "${BACKUP_DIR}/database.sql.gz"

echo "Archiving production uploads..."
if [ -d "${APP_DIR}/shared/uploads" ]; then
    tar -czf "${BACKUP_DIR}/uploads.tar.gz" -C "${APP_DIR}/shared" uploads
    tar -tzf "${BACKUP_DIR}/uploads.tar.gz" >/dev/null
fi

echo "Backup written to ${BACKUP_DIR}"
echo "Remember: copy this off-site — the host is not the only copy (docs/BACKUP-RESTORE.md)."
echo "${BACKUP_DIR}" > "${APP_DIR}/shared/backups/.last-backup"
