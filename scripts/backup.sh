#!/usr/bin/env bash
# Runs ON the shared host (invoke via: ssh commerce-host 'bash -s' < scripts/backup.sh)
# or copy it to the host once and cron it. Dumps the database and archives uploads
# into shared/backups/<timestamp>/. Never prints credentials to stdout/logs.
set -euo pipefail

APP_DIR="${APP_DIR:-$HOME/apps/lylishop}"
BACKUP_DIR="${APP_DIR}/shared/backups/$(date +%Y%m%d%H%M%S)"
ENV_FILE="${APP_DIR}/shared/.env"

if [ ! -f "$ENV_FILE" ]; then
    echo "ERROR: $ENV_FILE not found — nothing to back up yet." >&2
    exit 1
fi

# shellcheck disable=SC1090
set -a; source "$ENV_FILE"; set +a

mkdir -p "$BACKUP_DIR"

echo "Dumping database..."
mysqldump --single-transaction --quick \
    -h "${DB_HOST:-localhost}" -u "${DB_USER}" -p"${DB_PASSWORD}" "${DB_NAME}" \
    | gzip > "${BACKUP_DIR}/database.sql.gz"

echo "Archiving uploads..."
if [ -d "${APP_DIR}/shared/uploads" ]; then
    tar -czf "${BACKUP_DIR}/uploads.tar.gz" -C "${APP_DIR}/shared" uploads
fi

echo "Backup written to ${BACKUP_DIR}"
echo "Remember: copy this off-site — the host is not the only copy (docs/BACKUP-RESTORE.md)."
