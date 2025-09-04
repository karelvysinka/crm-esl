# Ops Modul

Provozní (operations) subsystém zajišťující: zálohy, ověřování obnovy, health reporting, audit aktivit.

## Cíle
- Spolehlivé denní (a ad-hoc) zálohy databáze a aplikačních dat.
- Automatická periodická verifikace obnovitelnosti (restore + sanity checks).
- Viditelnost: auditní trail klíčových ops úkonů (`ops_activities`).
- Rychlá diagnostika problémů (report + Slack notifikace – plán).

## Klíčové Jobs
| Job | Funkce | Frekvence |
|-----|--------|-----------|
| `RunBackupJob` (spatie/backup command) | Spouští zálohu (files + DB) | denně / manuálně |
| `CleanupBackupsJob` | Čistí staré zálohy dle retention | denně po backup |
| `RunVerifyRestoreJob` | Vytvoří temp DB, importuje poslední dump, ověří počty tabulek/řádků | denně |
| `GenerateBackupReportJob` | Sestaví Markdown report (přehled posledních záloh) | denně po verify |
| `EvaluateBackupHealthJob` | Vyhodnotí poslední běhy a nastaví status (pending) | denně |

## Datové Artefakty
- Složka backup storage (S3 / lokální volume) – snapshoty + dumpy.
- Tabulka `ops_activities` – log průběhu (start/finish, status, duration, meta).

## Metriky (cílové)
| Metrika | Cíl | Poznámka |
|---------|-----|----------|
| Backup success rate (7d) | ≥ 99% | Poměr úspěšných backup jobů |
| Verify success rate (7d) | ≥ 99% | Obnovovací testy |
| RPO (max age poslední valid backup) | < 24h | RPO definice |
| Restore duration (min) | sledovat trend | Měří čas importu dumpu |

### Exponovaný endpoint (plán)
`GET /crm/ops/metrics` vrátí Prometheus text formát, klíče (prefix `crm_ops_`):
- `crm_ops_last_db_dump_age_minutes`
- `crm_ops_last_verify_restore_age_hours`
- `crm_ops_backup_success_rate_7d`
- `crm_ops_verify_success_rate_7d`
- `crm_ops_restore_duration_seconds_last`

Generováno službou `BackupStatusService` + jednoduchý controller. Aktualizace každé volání (on-demand scrape).

## Alerting / Eskalace
- (Plán) Slack webhook při: 2× po sobě fail verify, chybějící dump >24h.
- Email fallback (cron) pokud Slack selže.

### Slack implementace (návrh)
Konfig: `ALERT_SLACK_WEBHOOK`. Služba `AlertService`:
```php
public function send(string $event, array $context = []): void {
	if (!($url = env('ALERT_SLACK_WEBHOOK'))) return;
	$payload = [
		'text' => "[OPS] $event".(empty($context)?'':" ```".json_encode($context, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."```")
	];
	Http::timeout(3)->post($url, $payload);
}
```
Rate-limit: jednoduchý cache klíč `ops_alert_{$event}` TTL 5m pro potlačení bouří.

## Proces Restore (automatický test)
1. Vytvoření dočasné DB (název s prefixem `verify_`).
2. Import posledního dumpu (`mysql < file.sql.gz`).
3. Ověření: počet tabulek, sampling klíčových – Companies, Contacts.
4. Drop temporary DB.

## Roadmap
- Přidat metriky do Promethea / pushgateway.
- Přidat export reportu do `docs/16-generated/backup-report.md`.
- Integrace Slack alertů.
- Automatický cleanup neúspěšných temp DB při restartu.

## Bezpečnostní Poznámky
- Dumpy držet jen na privátním storage (necommitovat).
- Skrýt citlivé hodnoty ve reportu (hash/email anonymizace budoucí).
