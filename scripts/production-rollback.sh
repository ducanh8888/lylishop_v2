#!/usr/bin/env bash
# Step 10 of docs/DEPLOYMENT.md, plus general point-in-time restore (docs/BACKUP-RESTORE.md).
# Two independent actions, either or both:
#   --to-release <timestamp>   switch the `current` symlink back to an existing release
#   --restore-db <timestamp>   restore the database from a scripts/production-backup.sh
#                               snapshot (shared/backups/<timestamp>/database.sql.gz)
# Runs ON the shared host (invoke via: ssh "${SSH_HOST_ALIAS:-commerce-host}" 'bash -s' -- \
#   --to-release ... < scripts/production-rollback.sh) or copy once and run locally on host.
# Defaults to DRY RUN. Requires --apply, and a typed confirmation for the DB restore
# specifically, since that overwrites the live production database.
set -euo pipefail

APP_DIR="${APP_DIR:-$HOME/apps/lylishop}"

TO_RELEASE=""
RESTORE_DB=""
APPLY="no"

while [ $# -gt 0 ]; do
    case "$1" in
        --to-release) TO_RELEASE="$2"; shift 2 ;;
        --restore-db) RESTORE_DB="$2"; shift 2 ;;
        --apply) APPLY="yes"; shift ;;
        *) echo "Unknown argument: $1" >&2; exit 1 ;;
    esac
done

if [ -z "$TO_RELEASE" ] && [ -z "$RESTORE_DB" ]; then
    echo "Available releases:"
    ls -1 "${APP_DIR}/releases" 2>/dev/null || echo "  (none found)"
    echo "Available backups:"
    ls -1 "${APP_DIR}/shared/backups" 2>/dev/null || echo "  (none found)"
    echo
    echo "Usage: production-rollback.sh [--to-release <timestamp>] [--restore-db <timestamp>] [--apply]"
    exit 0
fi

if [ "$APPLY" != "yes" ]; then
    echo "DRY RUN — no changes made. Re-run with --apply to actually roll back."
    [ -n "$TO_RELEASE" ] && echo "Would run: ln -sfn ${APP_DIR}/releases/${TO_RELEASE} ${APP_DIR}/current"
    if [ -n "$RESTORE_DB" ]; then
        echo "Would restore database from ${APP_DIR}/shared/backups/${RESTORE_DB}/database.sql.gz"
        echo "Would restore uploads from ${APP_DIR}/shared/backups/${RESTORE_DB}/uploads.tar.gz (if present)"
    fi
    exit 0
fi

if [ -n "$TO_RELEASE" ]; then
    RELEASE_DIR="${APP_DIR}/releases/${TO_RELEASE}"
    if [ ! -d "$RELEASE_DIR" ]; then
        echo "ERROR: release not found: $RELEASE_DIR" >&2
        exit 1
    fi
    echo "Switching current -> ${TO_RELEASE}..."
    ln -sfn "$RELEASE_DIR" "${APP_DIR}/current"
    echo "Done. Run scripts/production-health-check.sh next."
fi

if [ -n "$RESTORE_DB" ]; then
    BACKUP_DIR="${APP_DIR}/shared/backups/${RESTORE_DB}"
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

    read -r -p "This OVERWRITES the production database ${DB_NAME}. Type 'restore ${DB_NAME}' to confirm: " confirm
    if [ "$confirm" != "restore ${DB_NAME}" ]; then
        echo "Not confirmed. Aborting database restore." >&2
        exit 1
    fi

    echo "Restoring database from ${RESTORE_DB}..."
    gunzip -c "${BACKUP_DIR}/database.sql.gz" | mysql -h "${DB_HOST:-localhost}" -u "${DB_USER}" -p"${DB_PASSWORD}" "${DB_NAME}"

    if [ -f "${BACKUP_DIR}/uploads.tar.gz" ]; then
        echo "Restoring uploads from ${RESTORE_DB}..."
        tar -xzf "${BACKUP_DIR}/uploads.tar.gz" -C "${APP_DIR}/shared"
    fi
    echo "Database restore complete. Run scripts/production-health-check.sh next."
fi
