# Ops Runbook (Zálohy & Obnova)

Přesunuto z `RUNBOOK_OPS.md` (originál zachován pro diff). Aktualizace provádět zde.

## 1. Rychlý stav (Checklist)
| Kontrola | Příkaz / Akce | Očekávaný výsledek |
|----------|---------------|--------------------|
| Poslední DB dump stárnutí | Web: Ops Dashboard / DB Záloha | Status OK / Stáří < prah STALE |
| Poslední snapshot uploads | Ops Dashboard / Snapshot | Status OK |
| Verify restore fresh | Ops Dashboard / Verify | Status OK (věk < verify_overdue_hours) |
| Metriky dostupné | GET /crm/ops/metrics | HTTP 200 + prom text |
| Poslední alert | Log `storage/logs/ops.log` | Žádné nové FAIL alerty |

## 2. Manuální spuštění
| Akce | UI | CLI |
|------|----|-----|
| DB Backup | Tlačítko "Spustit DB Backup" | (alias do budoucna) |
| Snapshot Uploadů | "Snapshot Uploadů" | (stejné) |
| Verify Restore | "Verify Restore" | (stejné) |
| Report | "Report" | plánované |

## 3. Obnova (Disaster Recovery) – scénář DB korupce
1. Identifikuj poslední validní dump: `/srv/backups/crm/db/full-YYYYmmdd-HHMM.sql.gz` + ověř `sha256sum -c full-*.sql.gz.sha256`.
2. Připrav prázdnou náhradní DB.
3. Import:
```bash
gunzip -c full-YYYYmmdd-HHMM.sql.gz | mysql -h <host> -u <user> -p <dbname>
```
4. Ověření: tabulky vs. verify log, kritické tabulky existují.
5. Úprava `.env` DB host a redeploy.
6. Spusť Verify Restore ručně.

## 4. Částečná obnova nahraných souborů
1. List snapshotů:
```bash
RESTIC_PASSWORD=$RESTIC_PASSWORD restic -r $RESTIC_REPO snapshots --tag uploads
```
2. Obnova:
```bash
RESTIC_PASSWORD=$RESTIC_PASSWORD restic -r $RESTIC_REPO restore <snapshot-id> --target /tmp/restore_uploads
```
3. Selektivní kopie zpět.

## 5. Verify Restore interní workflow
Vybere dump, import, validace tabulek, log, cleanup, alert při selhání.

## 6. Migrace legacy záloh
Suchý běh:
```bash
php artisan ops:backups-migrate --dry-run
```
Ostrá migrace:
```bash
php artisan ops:backups-migrate
```

## 7. Alerting stavy
| Alert | Spouštěč | Akce |
|-------|----------|------|
| DB dump STALE/FAIL | EvaluateBackupHealthJob | Spustit dump / ověř cron |
| Uploads snapshot STALE | EvaluateBackupHealthJob | Spustit snapshot job |
| Verify restore overdue | EvaluateBackupHealthJob | Spustit verify |
| Verify restore FAILED | RunVerifyRestoreJob | Analyzovat log |

## 8. Slabá místa / měsíční checklist
- Náhodná validace dumpu.
- Trend velikosti dumpů.
- Restic `check`.

## 9. Env proměnné (ops)
| Var | Význam |
|-----|--------|
| OPS_DB_DUMP_STALE_MIN | Prah stárnutí dumpu |
| OPS_DB_DUMP_FAIL_MIN | Prah FAIL |
| OPS_SNAPSHOT_STALE_MIN | Prah snapshotu |
| OPS_VERIFY_OVERDUE_HOURS | Prah verify (h) |
| OPS_BACKUP_BASE | Root adresář |
| OPS_VERIFY_AUTO | Povolit weekly verify |

## 10. Rychlé debug příkazy
```bash
ls -1t ${OPS_BACKUP_BASE:-/srv/backups/crm}/db/full-*.sql.gz 2>/dev/null | head -1
find ${OPS_BACKUP_BASE:-/srv/backups/crm}/snapshots -type f -name 'snapshot-*.txt' -printf '%T@ %f\n' 2>/dev/null | sort -nr | head -1
find ${OPS_BACKUP_BASE:-/srv/backups/crm}/verify -type f -name 'verify-*.log' -printf '%T@ %f\n' 2>/dev/null | sort -nr | head -1 | cut -d' ' -f2-
```

## 11. Budoucí rozšíření
- Sidecar git runner.
- Binlog shipping.
- Automatizovaný DR drill.

---
Aktualizace při každé změně workflow.
