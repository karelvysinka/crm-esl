#!/usr/bin/env bash
set -euo pipefail

# Build MkDocs site into public/crm-docs using official Material image.
# Requires Docker. Run from repository root.

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

echo "[build-docs] Refreshing generated references (artisan)" >&2
php artisan docs:refresh || echo "(warning) docs:refresh failed – continuing to static build" >&2

IMAGE_TAG="crm-mkdocs:9.5.18"
if ! docker image inspect "$IMAGE_TAG" >/dev/null 2>&1; then
  echo "[build-docs] Building local image $IMAGE_TAG (Dockerfile.docs)" >&2
  docker build -q -f Dockerfile.docs -t "$IMAGE_TAG" .
fi

echo "[build-docs] Building MkDocs site → public/crm-docs (image $IMAGE_TAG)" >&2
docker run --rm \
  -u $(id -u):$(id -g) \
  -v "$ROOT_DIR":/docs \
  -e CI=1 \
  "$IMAGE_TAG" build --strict --site-dir public/crm-docs

echo "[build-docs] Done. Open /crm-docs/ in browser (ensure web server serves public/)." >&2
