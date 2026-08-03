#!/usr/bin/env bash
# Build step for CI or a dev machine with PHP 8.3 + Composer installed.
# Does NOT run on the shared host by default — see docs/DEPLOYMENT.md ("Composer
# chạy ở máy dev hoặc CI"). Produces vendor/ and web/wp/ locally; does not touch
# any remote server.
set -euo pipefail
cd "$(dirname "$0")/.."

PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"

if ! command -v "$PHP_BIN" >/dev/null 2>&1; then
    echo "ERROR: $PHP_BIN not found. Install PHP 8.3 (or set PHP_BIN) before building." >&2
    exit 1
fi

if ! command -v "$COMPOSER_BIN" >/dev/null 2>&1; then
    echo "ERROR: $COMPOSER_BIN not found. Install Composer (or set COMPOSER_BIN) before building." >&2
    exit 1
fi

php_version="$("$PHP_BIN" -r 'echo PHP_VERSION;')"
echo "Using PHP ${php_version}"
case "$php_version" in
    8.3.*|8.4.*) ;;
    *) echo "WARNING: TECH_STACK.md pins PHP 8.3. Detected ${php_version}." >&2 ;;
esac

echo "== composer validate =="
"$COMPOSER_BIN" validate --strict

echo "== composer install (no-dev) =="
"$COMPOSER_BIN" install --no-dev --optimize-autoloader --no-interaction

if [ -f package.json ]; then
    echo "== npm ci && npm run build (theme assets) =="
    npm ci
    npm run build
fi

echo "Build complete."
