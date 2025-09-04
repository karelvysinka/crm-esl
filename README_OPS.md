# Ops Modul (Git & Zálohy)

Tento modul poskytuje operátorům přehled o stavu záloh a verzí aplikace a umožňuje spouštět vybrané údržbové akce z UI.

## Hlavní funkce (MVP)
- Dashboard se stavem: poslední DB dump, snapshot uploadů, verify restore.
- Ruční akce: DB Backup, Storage Snapshot, Verify Restore, Report, Create Git Tag (zatím pouze UI hák – sidecar strategie viz plán §48).
- Audit aktivit v tabulce `ops_activities` (typ, status, timing, meta, log excerpt).
- Permission sada `ops.view`, `ops.execute`, `ops.release` (command `php artisan ops:permissions-sync`).
- Kontextová nápověda přes `@help('klíč')` s registry v `config/ops_help.php`.
- Proaktivní job `EvaluateBackupHealthJob` – alerty (Slack webhook) při stárnutí dumpu / snapshotu nebo chybějícím verify restore.

## Adresářová struktura záloh (cílová)
```
/srv/backups/crm/
  db/               # full-YYYYmmdd-HHMM.sql.gz (+ .sha256)
  snapshots/        # marker / metadata soubory snapshotů (restic repo je mimo)
  verify/           # logy verify restore běhů (verify-YYYYmmdd-HHMM.log)
  reports/          # generované reporty (backup-report-YYYYmmdd.md)
```
Nastavit pomocí `OPS_BACKUP_BASE` (default `/srv/backups/crm`).

## Environment proměnné (výběr)
Viz `.env.example` – klíčové:
- `OPS_DB_DUMP_STALE_MIN`, `OPS_DB_DUMP_FAIL_MIN`, `OPS_SNAPSHOT_STALE_MIN`, `OPS_VERIFY_OVERDUE_HOURS` – prahy.
- `OPS_BACKUP_BASE` – kořen struktury.
- `ALERT_SLACK_WEBHOOK` – Slack webhook pro alerty (pokud prázdné → alerty off).
- `RESTIC_REPO`, `RESTIC_PASSWORD` – snapshoty uploadů.

## Povolení oprávnění
```
php artisan migrate
php artisan ops:permissions-sync
```
Poté přiřaďte roli / uživateli odpovídající oprávnění (automaticky admin roli pokud existuje).

## Testy
- Jednotkové testy stavu záloh (`BackupStatusServiceTest`).
- Test nápovědy (`OpsHelpConfigTest`, `OpsHelpUsageTest`).
- Feature testy přístupu na dashboard / metrics (pozitivní větve přeskakovány pokud běží sqlite fallback).

## Alerting
`EvaluateBackupHealthJob` kontroluje:
- DB dump status (STALE / FAIL) – podle prahů.
- Restic snapshot stáří (`snapshot_stale_minutes`).
- Verify restore (čas od posledního úspěchu > `verify_overdue_hours`).

## Git Tagging
Implementace jobu připravena (`CreateGitTagJob`) – očekává sidecar strategii s bare clonem (plán §48). UI aktuálně odesílá požadavek, je nutné dokončit worker integraci.

## Verify Restore
Job `RunVerifyRestoreJob`:
1. Vybere nejnovější dump `db/full-*.sql.gz`.
2. Vytvoří dočasnou databázi, importuje dump, spočítá tabulky.
3. Zapíše log `verify/verify-YYYYmmdd-HHMM.log` (dump, tables, duration_ms).
4. Uloží meta (`verify_table_count`, `verify_dump`, `verify_log`) do `ops_activities`.
5. Při selhání vytvoří `verify-*-fail.log`.

Plán: týdenní běh (neděle 03:15) – vypnout lze `OPS_VERIFY_AUTO=false`.

## Snapshot Uploadů
`RunStorageSnapshotJob` provede `restic backup` uploads cesty a vytvoří marker `snapshots/snapshot-YYYYmmdd-HHMM.txt` s krátkým ID. Dashboard určuje stav podle stáří markeru.

## Další kroky
- Dokončit migraci starých záloh (`ops:backups-migrate --dry-run` / bez --dry-run).
- Sidecar Git runner + bezpečný deploy key.
- Prometheus metriky (rozšíření `/crm/ops/metrics`).
- Dokumentace DR postupu a runbook (chybí detail restore kroků).

---
Reference detailních sekcí viz `plan_git.md` (§36–§50). Tento soubor slouží jako rychlý praktický přehled.
