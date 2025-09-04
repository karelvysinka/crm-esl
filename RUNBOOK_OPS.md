# Ops Runbook (Zálohy & Obnova)

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
| DB Backup | Tlačítko "Spustit DB Backup" | `php artisan ops:activity db_backup` (budoucí alias) |
| Snapshot Uploadů | "Snapshot Uploadů" | (stejné) |
| Verify Restore | "Verify Restore" | (stejné) |
| Report | "Report" | plánované |

## 3. Obnova (Disaster Recovery) – scénář DB korupce
1. Identifikuj poslední validní dump:
   - `/srv/backups/crm/db/full-YYYYmmdd-HHMM.sql.gz` + ověř `sha256sum -c full-*.sql.gz.sha256`.
2. Připrav prázdnou náhradní DB (pokud původní instance mrtvá):
   - Vytvoř nový MySQL server / kontejner, nastav přístup.
3. Import:
   ```bash
   gunzip -c full-YYYYmmdd-HHMM.sql.gz | mysql -h <host> -u <user> -p <dbname>
   ```
4. Ověření:
   - Počet tabulek vs. referenční `verify` log (`verify/verify-*.log`).
   - Kritické tabulky (users, contacts, opportunities) existují a mají očekávaný počet záznamů.
5. Aplikace: uprav `.env` DB host na novou instanci a redeploy / cache clear.
6. Post-audit: spusť manuálně Verify Restore pro jistotu.

## 4. Částečná obnova nahraných souborů
1. Listing snapshotů:
   ```bash
   RESTIC_PASSWORD=$RESTIC_PASSWORD restic -r $RESTIC_REPO snapshots --tag uploads
   ```
2. Obnovení konkrétního snapshotu (např. do /tmp/restore_uploads):
   ```bash
   RESTIC_PASSWORD=$RESTIC_PASSWORD restic -r $RESTIC_REPO restore <snapshot-id> --target /tmp/restore_uploads
   ```
3. Selektivní kopie potřebných souborů zpět do `CRM_UPLOADS_PATH` (rsync).

## 5. Verify Restore interní workflow
1. Vyhledá nejnovější `db/full-*.sql.gz`.
2. Vytvoří dočasnou DB (prefix `verify_`).
3. Importuje dump a počítá tabulky (`information_schema.tables`).
4. Výsledek loguje do `verify/verify-YYYYmmdd-HHMM.log`.
5. Drop dočasné DB.
6. Alert při selhání (Slack webhook).

## 6. Migrace legacy záloh
Suchý běh:
```bash
php artisan ops:backups-migrate --dry-run
```
Ostrá migrace (komprese a přesun):
```bash
php artisan ops:backups-migrate
```
Po migraci: zkontroluj počet souborů v `db/` a existence `.sha256`.

## 7. Alerting stavy
| Alert | Spouštěč | Akce |
|-------|----------|------|
| DB dump STALE/FAIL | `EvaluateBackupHealthJob` | Ověřit cron / job běh, ručně spustit dump |
| Uploads snapshot STALE | `EvaluateBackupHealthJob` | Spustit snapshot job |
| Verify restore overdue | `EvaluateBackupHealthJob` | Spustit verify restore |
| Verify restore FAILED | `RunVerifyRestoreJob` | Zkontrolovat logy + integritu dumpu |

## 8. Slabá místa / manuální checklist měsíčně
- Validace náhodného dumpu (zkusit ruční import do dočasné DB).
- Kontrola velikosti dumpu vs. trend (výrazná změna < -30% nebo > +50% = audit).
- Restic `check`:
  ```bash
  RESTIC_PASSWORD=$RESTIC_PASSWORD restic -r $RESTIC_REPO check
  ```

## 9. Env proměnné (ops)
| Var | Význam |
|-----|--------|
| OPS_DB_DUMP_STALE_MIN | Prah stárnutí dumpu (min) |
| OPS_DB_DUMP_FAIL_MIN | Prah FAIL (min) |
| OPS_SNAPSHOT_STALE_MIN | Prah snapshotu (min) |
| OPS_VERIFY_OVERDUE_HOURS | Prah verify (h) |
| OPS_BACKUP_BASE | Root adresář struktur |
| OPS_VERIFY_AUTO | Povolit weekly verify |

## 10. Rychlé debug příkazy
```bash
# Poslední dump
ls -1t $(echo $OPS_BACKUP_BASE:/srv/backups/crm)/db/full-*.sql.gz 2>/dev/null | head -1

# Stáří posledního snapshot markeru
find $(echo $OPS_BACKUP_BASE:/srv/backups/crm)/snapshots -type f -name 'snapshot-*.txt' -printf '%T@ %f\n' 2>/dev/null | sort -nr | head -1

# Verify log
find $(echo $OPS_BACKUP_BASE:/srv/backups/crm)/verify -type f -name 'verify-*.log' -printf '%T@ %f\n' 2>/dev/null | sort -nr | head -1 | cut -d' ' -f2-
```

## 11. Budoucí rozšíření (mimo MVP)
- Sidecar git runner (tag push + GPG).
- Binlog shipping pro menší RPO.
- Automatizovaný DR drill skript.

---
Aktualizuj tento runbook při změně workflow nebo přidání nových alertů.
