# Import tools

This folder contains reproducible scripts for analyzing and importing data from `data_import/data_firmy_import.xlsx` into the CRM.

- analyze_excel.py — fast schema and data profiling of the Excel workbook
- analyze_crm.php — inspects current Laravel models, migrations and DB schema (MySQL) relevant for import
- run_analysis.sh — orchestrates the analysis and saves results under `storage/app/import_reports`
- findings_template.md — template that the scripts populate with basic metrics

ETL pipeline
- etl/parse_contacts.py, parse_groups.py, parse_sales.py -> CSVs in tools/import/etl/out
- Load via Artisan: php artisan import:etl --dir=tools/import/etl/out [--dry-run] [--limit=N]

Idempotency
- Upserts by external keys; order items are replaced per order on each run.
- Missing emails are deterministically generated placeholders to satisfy unique constraint.

Logs
- storage/app/import_logs/import_YYYYmmdd_HHMMSS.json
