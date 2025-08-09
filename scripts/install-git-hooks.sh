#!/usr/bin/env bash
# Install repository git hooks (pre-commit) pointing to scripts.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
HOOKS_DIR="$ROOT/.git/hooks"
mkdir -p "$HOOKS_DIR"

install_hook() {
  local name="$1" src="$2"
  local target="$HOOKS_DIR/$name"
  echo "Installing hook $name -> $src" >&2
  cat >"$target" <<EOF
#!/usr/bin/env bash
exec "$src"
EOF
  chmod +x "$target"
}

install_hook pre-commit "$ROOT/scripts/pre-commit-ci.sh"

echo "Hooks installed."
