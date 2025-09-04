#!/usr/bin/env bash
set -euo pipefail

ROOT="/root/crm.esl.cz/src"
REPORT_DIR="$ROOT/storage/app/import_reports"
mkdir -p "$REPORT_DIR"

PY="$ROOT/tools/import/analyze_excel.py"
PHP_INSPECT="$ROOT/tools/import/analyze_crm.php"

# 1) Excel profiling
python3 "$PY" | tee "$REPORT_DIR/_excel_run.json" || true

# 2) CRM schema inspection
php "$PHP_INSPECT" | tee "$REPORT_DIR/_crm_inspect.log" || true

# 3) Summarize to findings.md
{
  echo "# Import analysis findings"
  echo
  echo "## Excel"
  if [ -f "$REPORT_DIR/excel_profile.md" ]; then
    echo
    cat "$REPORT_DIR/excel_profile.md"
  else
    echo "Excel profile not generated."
  fi
  echo
  echo "## CRM Schema"
  if [ -f "$REPORT_DIR/crm_schema.md" ]; then
    echo
    cat "$REPORT_DIR/crm_schema.md"
  else
    echo "CRM schema report not generated."
  fi
} > "$REPORT_DIR/findings.md"

echo "All reports written to $REPORT_DIR"
