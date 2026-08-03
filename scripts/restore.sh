#!/usr/bin/env bash
# Restores a backup produced by scripts/backup.sh. Requires explicit --apply to write
# anything — without it, only prints what would happen. Intended for staging/test
# restores first (docs/BACKUP-RESTORE.md) — restoring onto production requires the
# same flag plus a typed confirmation.
set -euo pipefail

APP_DIR="${APP_DIR:-$HOME/apps/lylishop}"
BACKUP_TIMESTAMP="${1:?Usage: restore.sh <backup-timestamp> [--apply]}"
APPLY="${2:-}"
BACKUP_DIR="${APP_DIR}/shared/backups/${BACKUP_TIMESTAMP}"
ENV_FILE="${APP_DIR}/shared/.env"

if [ ! -d "$BACKUP_DIR" ]; then
    echo "ERROR: backup not found: $BACKUP_DIR" >&2
    exit 1
fi
if [ ! -f "$ENV_FILE" ]; then
    echo "ERROR: $ENV_FILE not found — cannot determine target database." >&2
    exit 1
fi

# shellcheck disable=SC1090
set -a; source "$ENV_FILE"; set +a

if [ "$APPLY" != "--apply" ]; then
    echo "DRY RUN — would restore:"
    echo "  Database from ${BACKUP_DIR}/database.sql.gz into ${DB_NAME}@${DB_HOST:-localhost}"
    [ -f "${BACKUP_DIR}/uploads.tar.gz" ] && echo "  Uploads from ${BACKUP_DIR}/uploads.tar.gz into ${APP_DIR}/shared/uploads"
    echo "Re-run with --apply to actually restore. This OVERWRITES the current database."
    exit 0
fi

read -r -p "This will overwrite ${DB_NAME}. Type 'restore ${DB_NAME}' to confirm: " confirm
if [ "$confirm" != "restore ${DB_NAME}" ]; then
    echo "Not confirmed. Aborting." >&2
    exit 1
fi

echo "Restoring database..."
gunzip -c "${BACKUP_DIR}/database.sql.gz" | mysql -h "${DB_HOST:-localhost}" -u "${DB_USER}" -p"${DB_PASSWORD}" "${DB_NAME}"

if [ -f "${BACKUP_DIR}/uploads.tar.gz" ]; then
    echo "Restoring uploads..."
    tar -xzf "${BACKUP_DIR}/uploads.tar.gz" -C "${APP_DIR}/shared"
fi

echo "Restore complete. Run scripts/health-check.sh next."
