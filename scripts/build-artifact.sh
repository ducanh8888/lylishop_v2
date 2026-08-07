#!/usr/bin/env bash
# Build a production artifact from an immutable Git snapshot. Composer-managed
# WordPress core, plugins and parent themes are installed inside a temporary
# staging directory; local untracked files can never leak into the release.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT_DIR="${OUT_DIR:-artifacts}"
STAMP="${1:-$(date +%Y%m%d%H%M%S)}"
SOURCE_REF="${SOURCE_COMMIT:-HEAD}"

case "$OUT_DIR" in
    /*) ;;
    *) OUT_DIR="$ROOT/$OUT_DIR" ;;
esac

if ! command -v git >/dev/null 2>&1; then
    echo "ERROR: git is required." >&2
    exit 1
fi
if ! command -v composer >/dev/null 2>&1; then
    echo "ERROR: composer is required." >&2
    exit 1
fi

SOURCE_SHA="$(git -C "$ROOT" rev-parse "${SOURCE_REF}^{commit}")"
if ! git -C "$ROOT" diff --quiet --ignore-submodules -- || \
   ! git -C "$ROOT" diff --cached --quiet --ignore-submodules --; then
    echo "ERROR: tracked changes exist. Commit them before building an artifact." >&2
    exit 1
fi

STAGE_ROOT="$(mktemp -d)"
STAGE="$STAGE_ROOT/source"
ARTIFACT="$OUT_DIR/release-${STAMP}.tar.gz"
trap 'rm -rf "$STAGE_ROOT"' EXIT

mkdir -p "$STAGE" "$OUT_DIR"
git -C "$ROOT" archive "$SOURCE_SHA" | tar -x -C "$STAGE"

echo "Building source commit: $SOURCE_SHA"
composer --working-dir="$STAGE" install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction \
    --no-progress

required_paths=(
    config
    vendor
    web/wp
    web/wp-config.php
    web/index.php
    web/.htaccess
    web/app/mu-plugins
    web/app/plugins
    web/app/themes/botiga
    web/app/themes/shop-child
    web/app/themes/storefront
)
for path in "${required_paths[@]}"; do
    if [ ! -e "$STAGE/$path" ]; then
        echo "ERROR: clean build is missing $path" >&2
        exit 1
    fi
done

printf '%s\n' "$SOURCE_SHA" > "$STAGE/SOURCE_COMMIT"

tar --create --gzip \
    --directory "$STAGE" \
    --file "$ARTIFACT" \
    SOURCE_COMMIT \
    config \
    vendor \
    web/wp \
    web/wp-config.php \
    web/index.php \
    web/.htaccess \
    web/app/mu-plugins \
    web/app/plugins \
    web/app/themes/botiga \
    web/app/themes/shop-child \
    web/app/themes/storefront

echo "Artifact created: $ARTIFACT"
sha256sum "$ARTIFACT" 2>/dev/null || shasum -a 256 "$ARTIFACT"
