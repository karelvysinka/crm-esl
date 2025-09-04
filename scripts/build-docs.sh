#!/usr/bin/env bash
set -euo pipefail

# Build MkDocs site into public/crm-docs using official Material image.
# Requires Docker. Run from repository root.

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

echo "[build-docs] Refreshing generated references (artisan)" >&2
php artisan docs:refresh || echo "(warning) docs:refresh failed – continuing to static build" >&2

echo "[build-docs] Building MkDocs site → public/crm-docs" >&2
docker run --rm \
  -u $(id -u):$(id -g) \
  -v "$ROOT_DIR":/docs \
  squidfunk/mkdocs-material:9.5.18 build --strict --site-dir public/crm-docs

echo "[build-docs] Done. Open /crm-docs/ in browser (ensure web server serves public/)." >&2
