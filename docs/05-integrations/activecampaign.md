# Integrace: ActiveCampaign

## Účel
Pravidelná inkrementální synchronizace kontaktů z ActiveCampaign do interních tabulek (kontakty, identity, log šarží `ac_sync_runs`).

## Flow (zjednodušeno)
1. Scheduled job `ActiveCampaignSyncJob` běží každou minutu.
2. Z DB (SystemSetting) čte offset `ac_sync_offset` a flag `ac_sync_enabled`.
3. Volá AC API `GET /contacts` se stránkováním (limit, offset, order by cdate DESC).
4. Importer normalizuje & mapuje pole → vytvoří / aktualizuje kontakty.
5. Uloží nový offset (posun o počet zpracovaných).

## Modely / tabulky
- `contacts` / `contact_identities` – cílová data.
- `ac_sync_runs` – průběh synchronizací (offset, počty, chybové stavy).
- `system_settings` – runtime přepínače.

## Konfigurace / nastavení
| Klíč SystemSetting | Význam |
|--------------------|--------|
| ac_sync_enabled | Povolit / zakázat automatickou šarži |
| ac_sync_offset | Aktuální offset v AC stránkování |

ENV proměnné (viz `.env.example`): `AC_BASE_URL`, `AC_API_TOKEN`.

## Failure Modes
| Scénář | Detekce | Mitigace |
|--------|---------|----------|
| Rate limit / 403 | Importer vrátí soft-fail (`ok=false`) | Job skončí OK bez posunu offsetu, log warning |
| API změna schématu | Výjimka v importeru | Alert v logu, manuální zásah |
| Desynchronizace offsetu | Skok v počtu kontaktů | Ruční reset offsetu (SystemSetting) |

## Alerting
- (Plán) Slack notifikace při opakované soft-fail sekvenci.

## Manuální spuštění
```
php artisan queue:dispatch "App\\Jobs\\ActiveCampaignSyncJob"
```
(Alternativně tinker a new instance s force=true.)

## Rozšíření
- Delta sync změněných od určitého času.
- Webhook push režim.
