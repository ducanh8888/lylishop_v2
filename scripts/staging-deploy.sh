#!/usr/bin/env bash
# Deploys one release artifact to the shared host using the release/symlink layout
# described in docs/DEPLOYMENT.md. Defaults to a DRY RUN — pass --apply to actually
# upload and switch the release. Refuses to run against production unless
# TARGET_ENV=production is explicitly set, on top of --apply.
#
# Preconditions this script does NOT verify for you (see docs/HOSTING-AUDIT.md
# section 15 stop conditions) — confirm these manually before first real use:
#   - PHP 8.3 selected as the web runtime for the domain (OnePanel PHP Selector)
#   - Database + user created via OnePanel
#   - shared/.env already placed on the host with real credentials
set -euo pipefail
cd "$(dirname "$0")/.."

SSH_ALIAS="${SSH_ALIAS:-commerce-host}"
REMOTE_APP_DIR="${REMOTE_APP_DIR:-apps/lylishop}"
TARGET_ENV="${TARGET_ENV:-staging}"
ARTIFACT="${1:?Usage: staging-deploy.sh <path-to-artifact.tar.gz> [--apply]}"
APPLY="${2:-}"

if [ ! -f "$ARTIFACT" ]; then
    echo "ERROR: artifact not found: $ARTIFACT" >&2
    exit 1
fi

STAMP="$(date +%Y%m%d%H%M%S)"
RELEASE_PATH="${REMOTE_APP_DIR}/releases/${STAMP}"

echo "Target env:    $TARGET_ENV"
echo "SSH alias:     $SSH_ALIAS"
echo "Remote app dir: ~/${REMOTE_APP_DIR}"
echo "New release:   ~/${RELEASE_PATH}"
echo

if [ "$APPLY" != "--apply" ]; then
    echo "DRY RUN — no changes made. Re-run with --apply to actually deploy."
    echo "Would run:"
    echo "  ssh $SSH_ALIAS mkdir -p ~/${RELEASE_PATH}"
    echo "  scp $ARTIFACT $SSH_ALIAS:~/${RELEASE_PATH}/release.tar.gz"
    echo "  ssh $SSH_ALIAS tar -xzf ~/${RELEASE_PATH}/release.tar.gz -C ~/${RELEASE_PATH}"
    echo "  ssh $SSH_ALIAS ln -sfn ~/${REMOTE_APP_DIR}/shared/.env ~/${RELEASE_PATH}/.env"
    echo "  ssh $SSH_ALIAS ln -sfn ~/${REMOTE_APP_DIR}/shared/uploads ~/${RELEASE_PATH}/web/app/uploads"
    echo "  ssh $SSH_ALIAS '/opt/alt/php83/usr/bin/php ~/${RELEASE_PATH}/web/wp-cli.phar core update-db --path=~/${RELEASE_PATH}/web/wp'"
    echo "  ssh $SSH_ALIAS ln -sfn ~/${RELEASE_PATH} ~/${REMOTE_APP_DIR}/current"
    exit 0
fi

if [ "$TARGET_ENV" = "production" ]; then
    read -r -p "Type 'deploy production' to confirm: " confirm
    if [ "$confirm" != "deploy production" ]; then
        echo "Not confirmed. Aborting." >&2
        exit 1
    fi
    echo "Run scripts/backup.sh against production BEFORE continuing — this script does not run it for you."
fi

ssh "$SSH_ALIAS" "mkdir -p ~/${RELEASE_PATH}"
scp "$ARTIFACT" "$SSH_ALIAS:~/${RELEASE_PATH}/release.tar.gz"
ssh "$SSH_ALIAS" "tar -xzf ~/${RELEASE_PATH}/release.tar.gz -C ~/${RELEASE_PATH} && rm ~/${RELEASE_PATH}/release.tar.gz"
ssh "$SSH_ALIAS" "ln -sfn ~/${REMOTE_APP_DIR}/shared/.env ~/${RELEASE_PATH}/.env"
ssh "$SSH_ALIAS" "ln -sfn ~/${REMOTE_APP_DIR}/shared/uploads ~/${RELEASE_PATH}/web/app/uploads"
ssh "$SSH_ALIAS" "/opt/alt/php83/usr/bin/php ~/${REMOTE_APP_DIR}/shared/wp-cli.phar core update-db --path=~/${RELEASE_PATH}/web/wp"
ssh "$SSH_ALIAS" "ln -sfn ~/${RELEASE_PATH} ~/${REMOTE_APP_DIR}/current"

echo "Deployed release ${STAMP} to ${TARGET_ENV}. Run scripts/health-check.sh next."
