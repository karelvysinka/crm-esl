---
title: Changelog & Release Notes
description: Záznam změn, nových funkcí a oprav v CRM.
---

# Changelog & Release Notes

Tento dokument udržuje auditní stopu významných změn aplikace. Každá položka musí být konzistentní s principy dokumentace (aktuálnost, jasná orientace, dopad na uživatele a provoz).

## Formát položky

```
## [YYYY-MM-DD] vX.Y.Z (TYPE)
### Shrnutí
Stručná věta co se změnilo / přibylo / opravilo.

### Detaily
- Kontext / důvod
- Co bylo přidáno / změněno / odstraněno
- Dotčené moduly / routes / config

### Dopady
- Uživatel: …
- Provoz / infra: …
- Bezpečnost / compliance: … (pokud relevantní)

### Migrace / Kroky po nasazení
- …

### Odkazy
- ADR: …
- Související dokumenty: …
```

TYPE používejte: `ADDED`, `CHANGED`, `FIXED`, `REMOVED`, `SECURITY`, `PERF`, `DOCS`.

Nové položky přidávejte NAHORU (nejnovější první) kvůli rychlé orientaci.

---

## Poslední změny

### [2025-09-04] v0.1.3 (DOCS)
#### Shrnutí
Rozšířena a zpřesněna dokumentace: nové README, průvodce šablonou Adminto, skript pro build statických docs a validace changelogu v release pipeline.

#### Detaily
- Přidán dokument `Adminto Šablona` (`docs/02-architecture/adminto-template.md`) – integrace, pluginy, roadmap.
- Přidán skript `scripts/build-docs.sh` (Docker build MkDocs → `public/crm-docs`).
- README kompletně přepracováno pro CRM (stack, quick start, release flow, roadmap).
- Release workflow doplněn o krok validace přítomnosti changelog položky pro tag.
- Navigace MkDocs doplněna o položku „Adminto Šablona“.
- Rebuild statických docs (viditelnost nové stránky + v0.1.2 položky).

#### Dopady
- Uživatel / Tým: Rychlejší onboarding – README a šablonový přehled poskytují kontext.
- Provoz: Jednoznačný postup pro rebuild dokumentace (snížení manuálních chyb).
- Governance: Release proces nyní chrání konzistenci verzí (chybějící položka = selhání workflow).

#### Migrace / Kroky
1. Není potřeba DB migrace.
2. Při další verzi používej `scripts/build-docs.sh` před nasazením (zajištění aktuálního statického webu).
3. Zachovej formát changelogu – validace je striktní.

#### Odkazy
- `.github/workflows/release.yml`
- `scripts/build-docs.sh`
- `README.md`
- `docs/02-architecture/adminto-template.md`

### [2025-09-04] v0.1.2 (CHANGED)
#### Shrnutí
Zavedena automatizovaná release pipeline, generátor release notes a povinná validace changelogu v CI.

#### Detaily
- Přidán příkaz `php artisan release:notes` pro extrakci poznámek z changelogu.
- Workflow `.github/workflows/release.yml` publikuje GitHub Release při tagu `v*.*.*` (SemVer).
- Doplněn krok validace: kontrola existence položky pro daný tag v `docs/01-intro/changelog.md` (chrání konzistenci historie).
- Rozšířen dokument `git-publishing.md` (sekce: Automatizovaná Release Akce + validace changelogu).
- Governance changelogu: příkaz `changelog:add` + skript `scripts/validate-changelog.php` popsaný již dříve – nyní napojen i na release flow.
- CI (`.github/workflows/ci.yml`) nadále hlídá aktuálnost dokumentace a strukturu changelogu.

#### Dopady
- Uživatel: Transparentnější vydání – každé označené verzí má okamžitě public release notes.
- Provoz / infra: Snížení manuální práce při releasu, menší riziko opomenutí kroků.
- Bezpečnost / compliance: Traceability verzí + auditní stopa změn CI konfigurace.

#### Migrace / Kroky
1. Není potřeba DB migrace.
2. Při přípravě další verze nejprve `php artisan changelog:add`, poté commit & tag.
3. Ověřit lokálně `php artisan release:notes vX.Y.Z` před push tagu.

#### Odkazy
- `.github/workflows/release.yml`
- `app/Console/Commands/ReleaseNotes.php`
- `docs/02-architecture/git-publishing.md`
- `scripts/validate-changelog.php`
- `app/Console/Commands/ChangelogAdd.php`

### [2025-09-04] v0.1.1 (FIXED)
#### Shrnutí
Opraven 419 (Page Expired) při přihlášení – doplněna web middleware vrstva (session & CSRF).

#### Detaily
- Přidány třídy `EncryptCookies`, `VerifyCsrfToken`, `Authenticate`.
- V `bootstrap/app.php` registrován web middleware stack.
- Alias `auth` nyní skutečně chrání chráněné routy.

#### Dopady
- Bezpečnost: Aktivní CSRF ochrana.
- Stabilita: Korektní udržení session a redirect po loginu.

#### Migrace / Kroky
1. Nasadit.
2. (Volitelně) Smazat staré cookies domény `.opent2.com`.
3. Retest POST /login (očekávaný 302 na `/crm`).

#### Odkazy
- `app/Http/Middleware/*`
- `bootstrap/app.php`
