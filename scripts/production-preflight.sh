#!/usr/bin/env bash
# Production readiness gate. Read-only by default — never writes to the remote host.
# Run this on the development machine or in CI before any production-deploy attempt.
# There is no staging environment (docs/DEPLOYMENT.md) — this script, plus local/CI
# checks, is the only pre-production gate.
set -euo pipefail
cd "$(dirname "$0")/.."

SSH_HOST_ALIAS="${SSH_HOST_ALIAS:-commerce-host}"
ARTIFACT="${1:-}"

failures=()
warnings=()

echo "== Git working tree =="
if [ -d .git ]; then
    if [ -n "$(git status --porcelain)" ]; then
        warnings+=("Working tree has uncommitted changes — verify nothing unintended is about to ship.")
    fi
else
    warnings+=("Not a git repository — cannot verify commit state.")
fi

echo "== Secret scan (basic) =="
if git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    tracked_secrets="$(git ls-files | grep -E '^\.env$|^auth\.json$|\.sql(\.gz)?$|id_(rsa|ed25519)$' || true)"
    if [ -n "$tracked_secrets" ]; then
        failures+=("Secret-shaped files are tracked in git: $tracked_secrets")
    fi
else
    warnings+=("Skipping git-tracked secret scan — not inside a git work tree.")
fi

echo "== Composer =="
if command -v composer >/dev/null 2>&1; then
    composer validate --strict || failures+=("composer validate --strict failed")
else
    warnings+=("composer not found — skipping composer validate (expected to run in CI if not installed locally).")
fi

echo "== Release artifact =="
if [ -n "$ARTIFACT" ]; then
    if [ ! -f "$ARTIFACT" ]; then
        failures+=("Artifact not found: $ARTIFACT")
    else
        echo "Artifact present: $ARTIFACT"
    fi
else
    warnings+=("No artifact path given — pass one as \$1 once scripts/build-artifact.sh has run.")
fi

echo "== SSH reachability (read-only) =="
if ssh -o BatchMode=yes -o ConnectTimeout=10 "$SSH_HOST_ALIAS" "echo ok" >/dev/null 2>&1; then
    echo "SSH to $SSH_HOST_ALIAS: OK"
else
    failures+=("Cannot reach $SSH_HOST_ALIAS over SSH with a non-interactive key login.")
fi

echo
if [ ${#warnings[@]} -gt 0 ]; then
    echo "Warnings:"
    printf '  - %s\n' "${warnings[@]}"
fi

if [ ${#failures[@]} -gt 0 ]; then
    echo "FAILURES:"
    printf '  - %s\n' "${failures[@]}"
    exit 1
fi

echo "Production preflight passed. Proceed to scripts/production-backup.sh, then scripts/production-deploy.sh."
