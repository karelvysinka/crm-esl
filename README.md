<div align="center">

# CRM ESL

![CI](https://github.com/karelvysinka/crm-esl/actions/workflows/ci.yml/badge.svg)
![Release](https://img.shields.io/github/v/tag/karelvysinka/crm-esl?label=version&sort=semver)
![PHP](https://img.shields.io/badge/PHP-%5E8.2-blue)
![License: MIT](https://img.shields.io/badge/License-MIT-green)

Lehký interní CRM systém pro evidenci a orchestraci procesů (kampaně, komunikace, znalostní báze, integrace služeb). Postaveno na Laravel 11 s důrazem na auditovatelnost a automatizovanou dokumentaci.

[📘 Dokumentace](https://ce1.opent2.com/crm-docs/) · [🧾 Changelog](https://ce1.opent2.com/crm-docs/01-intro/changelog/) 

</div>

## 🚀 Klíčové vlastnosti

- Automatizovaná dokumentace (`php artisan docs:refresh`) – routy, joby, schedule, permissions, ERD, DB schema, ENV matrix.
- Strukturovaný changelog + generátor release notes (`php artisan release:notes`).
- CI guard (testy, lint, validace changelogu, kontrola aktuálnosti generovaných docs).
- SemVer tagging + automatizovaný GitHub Release workflow.
- Role & permissions (Spatie) připravené pro granularitu přístupů.
- Backup reporting integrace (Spatie backup + generovaný přehled posledního reportu).
- Integrace: ActiveCampaign, Qdrant (vektorové vyhledávání / embeddings), plán pro knowledge subsystem.
- Docker-first architektura (app, queue, scheduler, embedder, Playwright runner, Redis, MySQL, Qdrant, Mailhog, Nginx, Watchtower, Traefik).

## 🧩 Tech Stack

| Vrstva | Technologie |
|--------|-------------|
| Backend | Laravel 11 (PHP 8.3) |
| Frontend build | Vite, Sass, Bootstrap 5, datatables, FullCalendar |
| Queue & Jobs | Laravel Queue (default), artisan scheduler |
| DB | MySQL |
| Cache & Session | Redis |
| Vector Search | Qdrant |
| Auth & Permissions | Laravel auth + Spatie Permission |
| Dokumentace | MkDocs Material (build do `public/crm-docs`) |
| Backup | spatie/laravel-backup |

## 🏁 Rychlý start (lokálně)

```bash
git clone git@github.com:karelvysinka/crm-esl.git
cd crm-esl
cp .env.example .env
composer install
php artisan key:generate
# Nastav DB (pokud používáš lokální MySQL / Docker) -> aktualizuj .env
php artisan migrate --seed || php artisan migrate
php artisan serve &
npm install
npm run dev
```

Volitelně generuj dokumentaci:
```bash
php artisan docs:refresh
./scripts/build-docs.sh   # Docker build MkDocs → public/crm-docs
```

Otevři: `http://localhost:8000` (nebo definovaný host v docker compose).

## 📚 Dokumentace

Primární znalostní báze: https://ce1.opent2.com/crm-docs/

Sekce „16-generated“ jsou generované – needitovat ručně. Úpravy dělej v source (modely, routy, config) a spusť obnovu.

## 🔄 Release & Changelog Flow

1. Přidej záznam: `php artisan changelog:add` (vybereš typ a popis).
2. Commit + push.
3. Tag: `git tag vX.Y.Z && git push origin vX.Y.Z`.
4. GitHub Actions: `Release` workflow přidá release + použije `php artisan release:notes`.

Validace: Release workflow selže pokud v changelogu chybí hlavička pro daný tag.

## 🛡️ Kvalita & CI

CI pipeline ( `.github/workflows/ci.yml` ) provádí:
- PHPUnit testy.
- Lint (Pint) – nezastavuje build (informativní fail).
- Generaci referenčních dokumentů + diff guard.
- Validaci changelogu (formát & pořadí).

## 🧪 Testy

Spusť lokálně:
```bash
vendor/bin/phpunit
```

## 🤝 Přispívání (interní)

Branch naming: `feature/...`, `fix/...`, `chore/...`.
Každá funkční změna musí mít položku v changelogu (s výjimkou čistě interních refactorů – ty lze značit `CHANGED` s poznámkou „interní“).
Před PR: `php artisan docs:refresh` a ověř že není diff v `docs/16-generated`.

## 🔐 Bezpečnost

Nahlášení zranitelnosti: vytvoř privátní kanál / kontaktuj interního správce repozitáře (ne zakládat public issue).

Citlivé hodnoty drž jen v `.env` (repo obsahuje pouze `.env.example`).

## 🗺️ Roadmap (výběr)

- Integrace AI pro sumarizaci aktivit (navazuje na knowledge subsystem ADR).
- Gitleaks / secret scanning v CI.
- Coverage report & badge.
- Hardening rate limiting / audit logů.

## 📄 Licence

MIT – viz `LICENSE` (zachováno z Laravel skeletonu).

---
Interní poznámka: pokud README upravuješ, drž sekce stručné (<= ~120 řádků) a nezdvojuj obsah, který už žije v MkDocs.
