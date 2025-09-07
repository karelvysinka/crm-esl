#!/usr/bin/env bash
set -euo pipefail

# Build MkDocs site into public/crm-docs using official Material image.
# Requires Docker. Run from repository root.

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

echo "[build-docs] Refreshing generated references (artisan)" >&2
php artisan docs:refresh || echo "(warning) docs:refresh failed – continuing to static build" >&2

echo "[build-docs] Building MkDocs site → public/crm-docs (with linkcheck)" >&2
docker run --rm \
  -u $(id -u):$(id -g) \
  -v "$ROOT_DIR":/docs \
  -e CI=1 \
  squidfunk/mkdocs-material:9.5.18 bash -c 'if [ -f docs/requirements.txt ]; then pip install --no-cache-dir -r docs/requirements.txt >/dev/null 2>&1; fi; mkdocs build --strict --site-dir public/crm-docs'

echo "[build-docs] Done. Open /crm-docs/ in browser (ensure web server serves public/)." >&2
