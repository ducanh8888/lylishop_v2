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
# Bedrock keeps wp-config.php one level above web/wp/ (WordPress core itself),
# so --path must point at the release's web/wp with the CWD left at the
# release root — not `cd` into web/wp itself with --path=. (that never finds
# wp-config.php and every WP-CLI call below silently failed). Matches the
# --path usage already used successfully throughout every deploy this
# session.
ssh "$SSH_HOST_ALIAS" "
  cd ~/${REMOTE_APP_DIR}/current 2>/dev/null || { echo 'current release not found'; exit 1; }
  WP='/opt/alt/php83/usr/bin/php /usr/bin/wp --path=web/wp'
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

# Added 2026-08-20 after a production incident: a manual deploy used relative
# symlinks (../../shared/uploads) for paths nested under web/app/, where the
# correct relative depth is four levels up, not two — the resulting symlink
# resolved to a path inside the release directory that never existed, so
# wp_upload_dir()'s basedir did not exist and every upload failed with
# "Unable to create directory uploads/2026/08". Nothing above this script
# would have caught it: the homepage still returned 200, WP-CLI still
# reported the site installed. This check verifies persistent storage is
# actually reachable through both the filesystem AND the PHP runtime, and
# fails the health check (non-zero exit) if it is not — a broken uploads
# path must block a deploy from being called healthy.
echo "== Persistent storage (uploads) =="
STORAGE_OK=1
ssh "$SSH_HOST_ALIAS" "
  set -e
  UPLOADS=~/${REMOTE_APP_DIR}/current/web/app/uploads
  SHARED=~/${REMOTE_APP_DIR}/shared/uploads
  if [ ! -L \"\$UPLOADS\" ]; then
    echo 'FAIL: current/web/app/uploads is not a symlink'
    exit 1
  fi
  RESOLVED=\"\$(readlink -f \"\$UPLOADS\" || true)\"
  echo \"symlink target (literal): \$(readlink \"\$UPLOADS\")\"
  echo \"symlink target (resolved): \${RESOLVED:-<none — broken symlink>}\"
  if [ -z \"\$RESOLVED\" ]; then
    echo 'FAIL: symlink target does not resolve to an existing path'
    exit 1
  fi
  if [ \"\$RESOLVED\" != \"\$(readlink -f \"\$SHARED\")\" ]; then
    echo \"FAIL: resolved target is not shared/uploads (got \$RESOLVED)\"
    exit 1
  fi
  echo 'OK: uploads symlink resolves to shared/uploads'
  DISK_LINE=\"\$(df -P \"\$SHARED\" | tail -1)\"
  USE_PCT=\"\$(echo \"\$DISK_LINE\" | awk '{print \$5}' | tr -d '%')\"
  echo \"disk use: \${USE_PCT}%\"
  if [ \"\${USE_PCT:-0}\" -ge 95 ]; then
    echo 'FAIL: disk usage >= 95%'
    exit 1
  fi
  cd ~/${REMOTE_APP_DIR}/current
  WP='/opt/alt/php83/usr/bin/php /usr/bin/wp --path=web/wp'
  \$WP eval '
    \$dir = wp_upload_dir();
    if (!empty(\$dir[\"error\"])) { fwrite(STDERR, \"FAIL: wp_upload_dir() error: \" . \$dir[\"error\"] . PHP_EOL); exit(1); }
    if (!wp_is_writable(\$dir[\"basedir\"])) { fwrite(STDERR, \"FAIL: basedir not writable: \" . \$dir[\"basedir\"] . PHP_EOL); exit(1); }
    \$probe = \$dir[\"basedir\"] . \"/.health-check-\" . uniqid() . \".tmp\";
    if (@file_put_contents(\$probe, \"probe\") === false) { fwrite(STDERR, \"FAIL: could not write probe file\" . PHP_EOL); exit(1); }
    \$ok = file_exists(\$probe);
    @unlink(\$probe);
    if (!\$ok || file_exists(\$probe)) { fwrite(STDERR, \"FAIL: probe write/cleanup did not verify cleanly\" . PHP_EOL); exit(1); }
    echo \"OK: PHP runtime confirms upload base is writable (create+delete probe passed)\" . PHP_EOL;
  '
" || STORAGE_OK=0

if [ "$STORAGE_OK" != "1" ]; then
    echo "FAIL: persistent storage check failed — do not consider this deploy healthy."
    exit 1
fi

echo "Health check complete."
