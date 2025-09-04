#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
ETL_DIR="$ROOT_DIR/tools/import/etl"
OUT_DIR="$ETL_DIR/out"

mkdir -p "$OUT_DIR"

python3 "$ETL_DIR/parse_contacts.py"
python3 "$ETL_DIR/parse_groups.py"
python3 "$ETL_DIR/parse_sales.py"

php "$ROOT_DIR/artisan" import:etl --dir="$OUT_DIR" --dry-run

echo "ETL dry-run completed. CSVs in: $OUT_DIR"
