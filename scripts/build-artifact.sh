#!/usr/bin/env bash
# Packages a release artifact from the already-built working tree (run scripts/build.sh
# first). Excludes secrets, uploads, VCS metadata and dev-only files — nothing that
# belongs on the shared host's persistent 'shared/' directory goes into the artifact.
set -euo pipefail
cd "$(dirname "$0")/.."

OUT_DIR="${OUT_DIR:-artifacts}"
STAMP="${1:-$(date +%Y%m%d%H%M%S)}"
ARTIFACT="${OUT_DIR}/release-${STAMP}.tar.gz"

if [ ! -d vendor ] || [ ! -d web/wp ]; then
    echo "ERROR: vendor/ or web/wp/ missing. Run scripts/build.sh first." >&2
    exit 1
fi

mkdir -p "$OUT_DIR"

tar --create --gzip \
    --exclude='.git' \
    --exclude='.env' \
    --exclude='.env.*' \
    --exclude='auth.json' \
    --exclude='web/app/uploads' \
    --exclude='node_modules' \
    --exclude="$OUT_DIR" \
    --file "$ARTIFACT" \
    .

echo "Artifact created: $ARTIFACT"
sha256sum "$ARTIFACT" 2>/dev/null || shasum -a 256 "$ARTIFACT"
