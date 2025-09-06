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

> Planned: Produktový modul (synchronizace Heureka feedů) – bude verzováno jako `ADDED` po dokončení fáze 1 (datový model). Návrh a implementační plán viz sekce Produkty v dokumentaci.

### [2025-09-06] v0.1.7 (CHANGED / UI / SECURITY)
#### Shrnutí
Vylepšené UI produktů (table styl jako "Brands Listing" + výchozí zobrazení table) a úprava bezpečnostních hlaviček kvůli načítání externích obrázků.

#### Detaily
- Přidán nový table layout pro `/crm/products?view=table` inspirovaný komponentou Brands Listing (avatar kruh, status tečka, dropdown akce, kompaktní footer).
- Výchozí mód přepínače grid/table změněn na `table` (lepší datová hustota pro 2000+ záznamů).
- Odstraněno duplicitní stránkování (ponecháno jen ve footeru karty v table módu).
- Grid mód zachován (karty) – beze změny logiky; pouze kosmetické drobnosti (hover, badge blur) již dříve.
- Dočasně uvolněna politika CORP/COEP (komentováno v `crm-headers.yml`) kvůli hot-link obrázkům z `www.esl.cz` (dodavatel nevrací potřebné hlavičky). Plán: později proxy/cache lokálně a obnovit striktní politiky.
- CSP již dříve rozšířeno o `img-src https:`; toto chování ponecháno.

#### Dopady
- Uživatel: Přehlednější tabulkové zobrazení jako default → rychlejší orientace, méně scrollování.
- Provoz: Dočasné snížení izolace (COEP/CORP) – akceptovatelné krátkodobě; nutno sledovat budoucí reintrodukci politik.
- Vývoj: Připravený podklad pro per-user preference view (možno později uložit do session / DB).

#### Migrace / Kroky po nasazení
1. Není potřeba spouštět migrace.
2. Ověřit načítání produktových obrázků (síť v DevTools bez blokací).
3. Později implementovat image proxy a znovu zapnout `Cross-Origin-Resource-Policy` a `Cross-Origin-Embedder-Policy`.

#### Odkazy
- `resources/views/products/index.blade.php`
- `infra/traefik/dynamic/crm-headers.yml`
- `docs/01-intro/changelog.md`

### [2025-09-06] v0.1.6 (ADDED / CHANGED)
#### Shrnutí
Implementace produktového modulu (full import, delta dostupností, audit, plánovač, OpsActivity logování, UI základ).

#### Detaily
- Migrace vytvořeny a aplikovány (`products`, `product_price_changes`, `product_availability_changes`).
- Full import (`products:import-full`) naplnil 2332 produktů (hash-based upsert, audit cen).
- Delta sync (`products:sync-availability`) s audit tabulkou (nullable `new_code`).
- Scheduler: full import denně 04:10, availability každých 15 minut (Kernel schedule).
- OpsActivity logování pro oba příkazy (duration, počty new/updated/unchanged, changes).
- UI routy `/crm/products` + detail; položka v sidenav s permission guard `products.view`.
- Přidána permissions `products.view`, `products.sync` (přes sync command + HasRoles na `User`).
- Parser opraven (DOMDocument wrap) – původní `simplexml_import_dom` edge case.
- Changelog + dokumentace (Products overview) aktualizovány o stav implementace.

#### Dopady
- Uživatel: Základní katalog produktů s historií cen/dostupnosti (MVP) dostupný v CRM.
- Provoz: Metriky běhů importů dostupné v `ops_activities`; možnost budoucího dashboardu.
- Vývoj: Stabilní základ pro další optimalizace (indexy, rozšíření filtrů, pricing engine integrace).

#### Migrace / Kroky po nasazení
1. `php artisan migrate --force` (již proběhlo v prostředí).
2. `php artisan products:permissions-sync` + přiřadit `products.view` uživatelům (provedeno pro prvního uživatele).
3. Ověřit cron běh scheduler kontejneru (jobs se spustí dle definic v Kernelu).
4. Monitorovat OpsActivity položky při prvním nočním běhu.

#### Odkazy
- `app/Console/Commands/ProductsImportFull.php`
- `app/Console/Commands/ProductsSyncAvailability.php`
- `app/Services/Products/HeurekaProductStream.php`
- `app/Services/Products/AvailabilityDeltaStream.php`
- `app/Models/OpsActivity.php`
- `resources/views/products/`
- `routes/web.php`


### [2025-09-06] v0.1.5 (FIXED / DOCS)
#### Shrnutí
Další stabilizace přihlášení (intermitentní 419) + rozšíření dokumentace Produktového modulu (analýza reálného Heureka feedu, implementační plán) a přidání do navigace.

#### Detaily
- Middleware hardening auth flow:
	- Přidán `NoCache` middleware (GET /login) – zabraňuje servírování zastaralé stránky s neplatným CSRF tokenem z cache.
	- Přidán `RotateSessionId` middleware (POST /login) – zajišťuje regeneraci session ID (ochrana proti potenciálnímu edge případu opakovaného použití).
	- Logout nyní čistí interní flag `_rotated`.
	- Rozšířen debug endpoint `/_debug/csrf` (dev) o `rotated_flag` pro sledování regenerace.
	- GET /login označen `nocache` middlewarem; POST /login `rotate.session`.
- Dokumentace:
	- Přidána sekce Produkty → Implementační plán + detailní analýza reálného feedu `heureka.xml` (frekvence tagů, max délky, návrh DB schématu, transformace, mapování dostupnosti).
	- Aktualizována navigace (`mkdocs.yml`) – zanoření položky Implementační plán.
	- Uloženy zdrojové feedy do `docs/feeds/` pro auditovatelnost analýzy.
- Příprava datového modelu: definována cílová tabulka `products` (price_vat_cents, category_hash, hash_payload, audit timestamps) + audit tabulky price & availability changes.

#### Dopady
- Uživatel: Přihlášení konzistentnější i po opakovaných cyklech přihlášení/odhlášení.
- Provoz: Snadnější diagnostika (rozšířený debug JSON + explicitní no-store hlavičky).
- Vývoj: Jasný, daty podložený plán Produktového modulu minimalizuje refaktoring později.

#### Migrace / Kroky po nasazení
1. Nasadit kód (nové middleware třídy + úpravy rout). 
2. Ověřit v prohlížeči (inkognito) 3× cyklus login→logout bez 419.
3. Zkontrolovat logy na absenci nových TokenMismatch záznamů.
4. Zahájit implementaci migrací pro `products` dle dokumentace.

#### Odkazy
- `app/Http/Middleware/NoCache.php`
- `app/Http/Middleware/RotateSessionId.php`
- `routes/web.php`
- `bootstrap/app.php`
- `docs/06-products/implementation-plan.md`
- `docs/06-products/overview.md`
- `docs/feeds/heureka.xml`


### [2025-09-05] v0.1.4 (FIXED)
#### Shrnutí
Stabilizace přihlášení za reverzní proxy: přidán TrustProxies middleware, diagnostika CSRF a rozšířené logování token mismatch.

#### Detaily
- Přidán `app/Http/Middleware/TrustProxies.php` (důvěra X-Forwarded-* hlavičkám: správná detekce HTTPS → konzistentní secure cookies / CSRF).
- Aktualizován `bootstrap/app.php` – zařazení `TrustProxies` na začátek web stacku.
- Rozšířen `VerifyCsrfToken` o detailní log záznam při `TokenMismatchException` (session id, zkrácené tokeny, UA, cookie presence, same-site, secure flag) pro rychlou analýzu sporadických 419.
- Přidána neprodukční diagnostická trasa `/_debug/csrf` (zobrazuje session_id, csrf token, cookie doménu / flagy) – automaticky vynechána v `production`.
- Ověřeno automatizovaným prohlížečem: POST /login vrací 302 (správné chování), žádný 419.

#### Dopady
- Uživatel: Přihlášení nyní konzistentní i za Traefik/Nginx (eliminace náhodných 419).
- Provoz: Snadné ladění budoucích problémů díky strukturovanému logu a debug trase (nižší MTTR).
- Bezpečnost: Zachována ochrana CSRF; žádné otevření výjimek – debug endpoint není v produkci.

#### Migrace / Kroky po nasazení
1. Nasadit změny.
2. Vymazat staré cookies (pokud byly nastavovány s nesprávným schématem) – volitelné.
3. Zkontrolovat logy na absenci nových "CSRF token mismatch" záznamů po běžném používání.
4. Po ověření lze případně odstranit diagnostický endpoint (ponecháno dočasně pro monitoring první týden).

#### Odkazy
- `app/Http/Middleware/TrustProxies.php`
- `app/Http/Middleware/VerifyCsrfToken.php`
- `bootstrap/app.php`
- `routes/web.php`

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
