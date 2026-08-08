#!/usr/bin/env bash
# Step 8 of docs/DEPLOYMENT.md. Read-only post-deploy verification. Safe to run any time.
set -euo pipefail

SSH_HOST_ALIAS="${SSH_HOST_ALIAS:-commerce-host}"
REMOTE_APP_DIR="${REMOTE_APP_DIR:-apps/lylishop}"
DOMAIN="${DOMAIN:-https://lylishop.online}"

echo "== HTTP check: ${DOMAIN} =="
http_code="$(curl -s -o /dev/null -w '%{http_code}' "$DOMAIN" || echo "000")"
echo "HTTP status: $http_code"
if [ "$http_code" != "200" ]; then
    echo "WARNING: expected 200, got $http_code"
fi

echo "== WP-CLI checks (via SSH, PHP 8.3) =="
ssh "$SSH_HOST_ALIAS" "
  cd ~/${REMOTE_APP_DIR}/current/web/wp 2>/dev/null || { echo 'current release not found'; exit 1; }
  WP='/opt/alt/php83/usr/bin/php /usr/bin/wp --path=.'
  echo '--- core is-installed ---'
  \$WP core is-installed && echo OK || echo FAIL
  echo '--- active plugins ---'
  \$WP plugin list --status=active --field=name
  echo '--- maintenance mode ---'
  \$WP maintenance-mode status
  echo '--- site URLs ---'
  \$WP option get siteurl
  \$WP option get home
"

echo "== Cache exclusion spot-check (cart/checkout must not be cached) =="
for path in "cart" "checkout" "my-account"; do
    code="$(curl -s -o /dev/null -w '%{http_code}' -H 'Cache-Control: no-cache' "${DOMAIN}/${path}/" || echo "000")"
    echo "  ${path}: HTTP $code (verify manually that no page-cache header is present)"
done

echo "Health check complete."
