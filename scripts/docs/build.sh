#!/usr/bin/env bash
set -euo pipefail

echo "[docs] Refreshing reference data"
SCRIPT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
LARAVEL_ROOT=$(cd "$SCRIPT_DIR/../../" && pwd)
if [ ! -f "$LARAVEL_ROOT/artisan" ]; then
  # Fallback: walk upwards until artisan found
  SEARCH_DIR=$SCRIPT_DIR
  while [ "$SEARCH_DIR" != "/" ]; do
    if [ -f "$SEARCH_DIR/artisan" ]; then
      LARAVEL_ROOT=$SEARCH_DIR
      break
    fi
    SEARCH_DIR=$(dirname "$SEARCH_DIR")
  done
fi

if [ ! -f "$LARAVEL_ROOT/artisan" ]; then
  echo "[docs] ERROR: Unable to locate artisan (Laravel root)." >&2
  exit 1
fi

echo "[docs] Detected Laravel root: $LARAVEL_ROOT"
cd "$LARAVEL_ROOT"

echo "[docs] Refreshing reference data"
php artisan docs:refresh || { echo 'docs:refresh failed'; exit 1; }

if ! command -v docker &>/dev/null; then
  echo "Docker not found – skipping mkdocs container build (install python+mkdocs locally if needed)" >&2
  exit 0
fi

echo "[docs] Building static site via mkdocs-material container"
# Mount project root at /docs (mkdocs expects mkdocs.yml in CWD)
# Already in Laravel root ($LARAVEL_ROOT)

# Allow overriding output directory (default deploys directly into public/crm-docs for serving)
OUTPUT_DIR=${OUTPUT_DIR:-public/crm-docs}
echo "[docs] Output dir: $OUTPUT_DIR"

# Ensure clean output directory
rm -rf "$OUTPUT_DIR" site 2>/dev/null || true

# Build directly into target directory
docker run --rm -v "$PWD":/docs -w /docs squidfunk/mkdocs-material:latest build -d "$OUTPUT_DIR"

echo "[docs] Site generated in $OUTPUT_DIR"
