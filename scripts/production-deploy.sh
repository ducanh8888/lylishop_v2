#!/usr/bin/env bash
# Deploys one release artifact to production using the release/symlink layout in
# docs/DEPLOYMENT.md (steps 1-9; rollback is scripts/production-rollback.sh, step 10).
# There is no staging — this script always targets the single production environment,
# so it defaults to a DRY RUN and requires both --apply and a typed confirmation.
#
# Preconditions this script does NOT verify for you (docs/HOSTING-AUDIT.md section 15):
#   - PHP 8.3 selected as the web runtime for the domain (OnePanel PHP Selector)
#   - Database + user created via OnePanel
#   - shared/.env already placed on the host with real credentials
#   - Theme decision finalized by the founder (docs/THEME-DECISION-BRIEF.md)
# Run scripts/production-preflight.sh first.
set -euo pipefail
cd "$(dirname "$0")/.."

SSH_HOST_ALIAS="${SSH_HOST_ALIAS:-commerce-host}"
REMOTE_APP_DIR="${REMOTE_APP_DIR:-apps/lylishop}"
ARTIFACT="${1:?Usage: production-deploy.sh <path-to-artifact.tar.gz> [--apply]}"
APPLY="${2:-}"

if [ ! -f "$ARTIFACT" ]; then
    echo "ERROR: artifact not found: $ARTIFACT" >&2
    exit 1
fi

STAMP="$(date +%Y%m%d%H%M%S)"
RELEASE_PATH="${REMOTE_APP_DIR}/releases/${STAMP}"

echo "SSH alias:      $SSH_HOST_ALIAS"
echo "Remote app dir: ~/${REMOTE_APP_DIR}"
echo "New release:    ~/${RELEASE_PATH}"
echo

if [ "$APPLY" != "--apply" ]; then
    echo "DRY RUN — no changes made. Re-run with --apply to actually deploy."
    echo "Would run, in order:"
    echo "  1. ssh $SSH_HOST_ALIAS bash -s < scripts/production-backup.sh   (DB + uploads backup)"
    echo "  2. ssh $SSH_HOST_ALIAS wp maintenance-mode activate --path=~/${REMOTE_APP_DIR}/current/web"
    echo "  3. ssh $SSH_HOST_ALIAS mkdir -p ~/${RELEASE_PATH}"
    echo "  4. scp $ARTIFACT $SSH_HOST_ALIAS:~/${RELEASE_PATH}/release.tar.gz"
    echo "  5. ssh $SSH_HOST_ALIAS tar -xzf ~/${RELEASE_PATH}/release.tar.gz -C ~/${RELEASE_PATH}"
    echo "  6. ssh $SSH_HOST_ALIAS ln -sfn ~/${REMOTE_APP_DIR}/shared/.env ~/${RELEASE_PATH}/.env"
    echo "  7. ssh $SSH_HOST_ALIAS ln -sfn ~/${REMOTE_APP_DIR}/shared/uploads ~/${RELEASE_PATH}/web/app/uploads"
    echo "  8. ssh $SSH_HOST_ALIAS /opt/alt/php83/usr/bin/php ~/${REMOTE_APP_DIR}/shared/wp-cli.phar core update-db --path=~/${RELEASE_PATH}/web/wp"
    echo "  9. ssh $SSH_HOST_ALIAS ln -sfn ~/${RELEASE_PATH} ~/${REMOTE_APP_DIR}/current"
    echo " 10. ssh $SSH_HOST_ALIAS wp maintenance-mode deactivate --path=~/${REMOTE_APP_DIR}/current/web"
    echo "Then run: scripts/production-health-check.sh"
    exit 0
fi

read -r -p "This deploys to PRODUCTION (lylishop.online). Type 'deploy production' to confirm: " confirm
if [ "$confirm" != "deploy production" ]; then
    echo "Not confirmed. Aborting." >&2
    exit 1
fi

echo "Step 1/9: backing up database and uploads..."
ssh "$SSH_HOST_ALIAS" "APP_DIR=~/${REMOTE_APP_DIR} bash -s" < "$(dirname "$0")/production-backup.sh"

echo "Step 2/9: enabling maintenance mode on current release..."
ssh "$SSH_HOST_ALIAS" "/opt/alt/php83/usr/bin/php ~/${REMOTE_APP_DIR}/shared/wp-cli.phar maintenance-mode activate --path=~/${REMOTE_APP_DIR}/current/web" || true

echo "Step 3/9: creating release directory..."
ssh "$SSH_HOST_ALIAS" "mkdir -p ~/${RELEASE_PATH}"

echo "Step 4/9: uploading artifact..."
scp "$ARTIFACT" "$SSH_HOST_ALIAS:~/${RELEASE_PATH}/release.tar.gz"

echo "Step 5/9: extracting artifact..."
ssh "$SSH_HOST_ALIAS" "tar -xzf ~/${RELEASE_PATH}/release.tar.gz -C ~/${RELEASE_PATH} && rm ~/${RELEASE_PATH}/release.tar.gz"

echo "Step 6-7/9: linking shared persistent data (.env, uploads)..."
ssh "$SSH_HOST_ALIAS" "ln -sfn ~/${REMOTE_APP_DIR}/shared/.env ~/${RELEASE_PATH}/.env"
ssh "$SSH_HOST_ALIAS" "ln -sfn ~/${REMOTE_APP_DIR}/shared/uploads ~/${RELEASE_PATH}/web/app/uploads"

echo "Step 8/9: running WP-CLI database migration..."
ssh "$SSH_HOST_ALIAS" "/opt/alt/php83/usr/bin/php ~/${REMOTE_APP_DIR}/shared/wp-cli.phar core update-db --path=~/${RELEASE_PATH}/web/wp"

echo "Step 9/9: switching current release symlink..."
ssh "$SSH_HOST_ALIAS" "ln -sfn ~/${RELEASE_PATH} ~/${REMOTE_APP_DIR}/current"

echo "Disabling maintenance mode..."
ssh "$SSH_HOST_ALIAS" "/opt/alt/php83/usr/bin/php ~/${REMOTE_APP_DIR}/shared/wp-cli.phar maintenance-mode deactivate --path=~/${REMOTE_APP_DIR}/current/web" || true

echo "Deployed release ${STAMP} to production. Run scripts/production-health-check.sh now."
echo "If anything looks wrong, run scripts/production-rollback.sh."
