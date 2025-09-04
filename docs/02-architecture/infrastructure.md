# Infrastruktura & Prostředí

## Doména / URL
- Primární doména (aktuální instance): `https://ce1.opent2.com`
- Dokumentace staticky servírovaná na: `https://ce1.opent2.com/crm-docs/`
- Reverzní proxy / router: Traefik (externí síť `web`).

## VPS / Host
- Linux VPS (typ: standard x86_64) – parametry CPU/RAM nejsou v repozitáři; doplnit do ADR pokud budou SLA.
- Perzistence dat: host adresáře pod `/srv/volumes/crm/*` a `/srv/backups/crm/*`.

## Docker Služby (docker-compose)
| Service | Image | Účel | Persistentní volume |
|---------|-------|------|----------------------|
| db | mysql:8.0 | Primární MySQL databáze | `/srv/volumes/crm/mysql-data` |
| redis | redis:alpine | Cache / queue backend | `/srv/volumes/crm/redis-data` |
| qdrant | qdrant/qdrant:latest | Vector store (knowledge) | `/srv/volumes/crm/qdrant-data` |
| app | crmeslcz-crm-app (Dockerfile.app) | PHP-FPM / Laravel aplikace | bind `/srv/volumes/crm/src` |
| scheduler | crmeslcz-crm-scheduler | Laravel schedule worker | bind source + logs |
| queue | crmeslcz-crm-queue | Queue worker (jobs) | bind source + logs |
| embedder | crmeslcz-embedder | Embedding service (externí API volání) | n/a |
| playwright | crmeslcz-playwright-runner | E2E / scraping / test automace | n/a |
| web | nginx:1.27-alpine | HTTP server + statické docs | bind source + logs |
| mailhog | mailhog/mailhog | Lokální SMTP testing | ephemeral |

## Síťová Topologie
- Interní síť: `crm-internal` (app, db, redis, qdrant, workers, embedder, playwright, mailhog)
- Externí: `web` (Traefik + web container)
- Traefik směruje host `ce1.opent2.com` na service `web` port 80.

## Healthchecks
| Service | Test | Interval | Retry |
|---------|------|----------|-------|
| db | `mysqladmin ping` | 30s | 5 |
| redis | `redis-cli ping` | 30s | 5 |
| qdrant | HTTP GET `/collections` | 30s | 5 |
| app | `php -v` | 30s | 5 |
| web | HTTP GET `/healthz` | 20s | 5 |

## Logy & Soubory
| Path | Popis |
|------|-------|
| `/srv/volumes/crm/src` | Aplikační zdrojový kód (bind) |
| `/srv/volumes/crm/logs/php` | PHP-FPM / app logy |
| `/srv/volumes/crm/logs/nginx` | Nginx access/error |
| `/srv/volumes/crm/mysql-data` | Datové soubory MySQL |
| `/srv/volumes/crm/redis-data` | Redis persistence |
| `/srv/volumes/crm/qdrant-data` | Qdrant storage |
| `/srv/backups/crm` | Zálohy + verify + reporty |
| `/srv/backups/crm/reports` | Markdown reporty generované jobem |

## Zálohy & Verify
- Spouštěné joby: `RunBackupJob`, `RunVerifyRestoreJob`, `GenerateBackupReportJob`, `EvaluateBackupHealthJob`.
- Reporty sumarizovány v `backup-report-latest.md` (generováno).
- Verifikace: import dumpu do temp DB + sanity checks.

## Build & Deploy
| Oblast | Mechanismus |
|--------|-------------|
| Dok. generace | `php artisan docs:refresh` + MkDocs (Material) -> `public/crm-docs` |
| CI | GitHub Actions `ci.yml` (tests, lint, docs diff) |
| Frontend assets | Vite (viz `npm run dev` v `composer dev` skriptu) |

## Bezpečnost / Konfigurace
- `.env` hodnoty (viz `.env.example`): DB, Redis, RESTIC, ActiveCampaign, OPS_* prahy.
- Slack alerting: `ALERT_SLACK_WEBHOOK` (pokud není nastaveno, tichý režim alertů).
- Oprávnění: Spatie Role/Permission (dokumentováno v `permissions.md`).

## Doporučené Budoucí Vylepšení (Infra)
| Téma | Návrh | Přínos |
|------|-------|--------|
| Observability | Přidat Prometheus scrape (externí) + Grafana dashboard | Viditelnost trendů |
| Logs struktura | JSON log channel pro ops a access | Snadná agregace / filtrace |
| Backups integrity | Periodický SHA256 audit + random restore drill | Vyšší jistota obnovy |
| Rate Limiting | Přidat konfigurovatelné limity na kritické API (knowledge ingest) | Ochrana výkonu |
| Secrets management | Vault / SSM místo prostého `.env` | Bezpečnost |

---
Aktualizovat při změně docker-compose, přidání služeb nebo změně cest.
