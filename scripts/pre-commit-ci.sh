#!/usr/bin/env bash
# Pre-commit hook: run fast local CI (lint + tests) before accepting commit.
# Blocks commit on failure. Opt-out with SKIP_LOCAL_CI=1 environment variable.
# Install via: ./scripts/install-git-hooks.sh or make install-hooks

set -euo pipefail

if [[ "${SKIP_LOCAL_CI:-}" == "1" ]]; then
  echo "[pre-commit] SKIP_LOCAL_CI=1 -> skipping local CI checks." >&2
  exit 0
fi

PROJECT_ROOT="$(git rev-parse --show-toplevel 2>/dev/null || pwd)"
cd "$PROJECT_ROOT"

echo "[pre-commit] Running staged-file aware CI checks..." >&2

CHANGED=$(git diff --cached --name-only || true)
if [[ -z "$CHANGED" ]]; then
  echo "[pre-commit] No staged files; skipping checks." >&2
  exit 0
fi

NEED_PHP=$(echo "$CHANGED" | grep -E '\\.php$' || true)
NEED_JS=$(echo "$CHANGED" | grep -E '\\.(js|cjs|mjs|jsx|ts|tsx)$' || true)

STATUS=0

if [[ -n "$NEED_PHP" ]]; then
  if [[ ! -d vendor ]]; then
    echo "[pre-commit] Installing composer dependencies (vendor missing)..." >&2
    composer install --no-interaction --prefer-dist >/dev/null
  fi
  echo "[pre-commit] PHP lint (phpcs) ..." >&2
  if ! composer run lint:php --quiet; then
    echo "[pre-commit] PHP lint failed." >&2
    STATUS=1
  fi
  echo "[pre-commit] PHP tests (phpunit) ..." >&2
  if ! composer test -- --no-coverage >/dev/null; then
    echo "[pre-commit] PHP tests failed." >&2
    STATUS=1
  fi
fi

if [[ -n "$NEED_JS" ]]; then
  if [[ ! -d node_modules ]]; then
    echo "[pre-commit] Installing npm dependencies (node_modules missing)..." >&2
    if [[ -f package-lock.json ]]; then npm ci --silent; else npm install --silent; fi
  fi
  echo "[pre-commit] JS lint (eslint) ..." >&2
  if ! npm run lint:js --silent; then
    echo "[pre-commit] JS lint failed." >&2
    STATUS=1
  fi
  echo "[pre-commit] JS tests (jest) ..." >&2
  if ! npm test --silent -- --runInBand >/dev/null; then
    echo "[pre-commit] JS tests failed." >&2
    STATUS=1
  fi
fi

if [[ "$STATUS" -ne 0 ]]; then
  cat <<'EOF' >&2

✗ Commit aborted due to failing local CI checks.
  - Fix the issues above or bypass with SKIP_LOCAL_CI=1 git commit ... (use sparingly).
EOF
  exit 1
fi

echo "✓ Local CI checks passed." >&2
exit 0
