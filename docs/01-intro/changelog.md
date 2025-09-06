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
### [2025-09-06] v0.1.13 (ADDED / CHANGED)
#### Shrnutí
Analytics & UI rozšíření modulu objednávek: KPI karty, 12měsíční graf, větší typografie, rozšířená telemetrie importu + navýšení délky `order_number`.

#### Detaily
- Přidány 4 KPI karty na `/crm/orders`: Celkem, Poslední měsíc, Poslední týden, 7denní průměr (počty se počítají SQL agregací v `OrderController`).
- 12měsíční kombinovaný graf (sloupce = počet objednávek, čára = součet tržeb) pomocí ApexCharts (lazy load pouze při zobrazení stránky, snížení TTFB / bundle size).
- Typografie a vzhled sjednocen s hlavním dashboardem (větší kontrast, bold čísla, responsivní škálování přes `clamp`).
- Rozšířené metriky v OpsActivity pro full import (detaily: `details_fetched`, `items_inserted`, `states_inserted`, `integrity_mismatches`).
- Navýšena délka sloupce `order_number` z 32 → 80 znaků (podpora suffixů typu `(02-0106)`).
- Bezpečnější guard proti prázdné Page 1 (log varování) + heuristika zastavení při opakujícím se hash stránkování.
- UI: refaktor stat sekce do samostatných karet + separovaná karta grafu (lepší čitelnost nad foldem).

#### Dopady
- Uživatel: Okamžitá vizuální orientace v objemu objednávek a trendu posledního roku bez exportů.
- Provoz: Snadnější sledování efektivity importu (OpsActivity meta) při delším běhu / ladění výkonu.
- Vývoj: Připravený prostor pro následné přidání procentuálních trendů (MoM / WoW) a cache layer.

#### Migrace / Kroky po nasazení
1. `php artisan migrate --force` (aplikuje změnu délky `order_number` pokud ještě neproběhla).
2. (Volitelně) Nastavit `ORDERS_FULL_IMPORT_MAX_PAGES` podle požadované hloubky historie.
3. Ověřit vykreslení grafu (v produkci musí být povolen načtený CDN skript ApexCharts v CSP pokud je zavedena).

#### Odkazy
- `app/Http/Controllers/OrderController.php`
- `resources/views/orders/index.blade.php`
- `database/migrations/2025_09_06_234500_alter_orders_increase_number_and_add_edit_id.php`
- `app/Jobs/Orders/RunFullImportJob.php`

#### Poznámky
Plánované další kroky (mimo scope této verze): procentuální trend indikátory (šipky), cache 5–10 min pro KPI, denní 30denní mini graf, lokalizované formátování měny v tooltipech.


> Planned: Produktový modul (synchronizace Heureka feedů) – bude verzováno jako `ADDED` po dokončení fáze 1 (datový model). Návrh a implementační plán viz sekce Produkty v dokumentaci.

### [2025-09-06] v0.1.12 (ADDED / CHANGED / FIXED)
#### Shrnutí
Stabilizace modulu objednávek: přesné parsování položek z dedikované záložky, perzistence interního edit ID, nové příkazy pro zpětné doplnění položek a integritní kontrolu, volitelné plánování a úklid fallback logiky.

#### Detaily
- Přidán sloupec / podpora `external_edit_id` (interní ID z odkazu `/admin/order/edit/{id}`) – spolehlivý klíč pro detail.
- `OrderScrapeClient`: dvojfázové načtení detailu (hlavní stránka + `/tab-ite`) → strukturované parsování tabulky položek.
- Odstraněno duplicitní počítání položek (gating fallback logiky pouze když structured parse vrátí 0 položek).
- Dedup klíč položek: md5(name|line_type|unit_price|qty|total) + `external_item_id` pokud dostupné.
- Debug dumpy (HTML/JSON) podmíněné `ORDERS_DEBUG_DUMP_ITEMS` (výchozí ticho v produkci).
- Nové příkazy: `orders:backfill-items` (chytřejší výběr kandidátů, guard na nedostupnou DB) a `orders:integrity-check`.
- Rozšířen `Kernel` o volitelné plánované úlohy (`ORDERS_BACKFILL_NIGHTLY`, `ORDERS_INTEGRITY_DAILY`).
- Upřesněn hash zdroje – zahrnuje obsah záložky položek (stabilnější detekce změn).
- Dokumentace `07-orders/*` aktualizována na reálný stav implementace (fáze a zjednodušení datového modelu vs původní návrh).

#### Dopady
- Uživatel: Kompletní položky (produkt + doprava + platba) u nových objednávek bez ruční intervence.
- Provoz: Možnost bezpečného zpětného doplnění historických objednávek bez plošného přepisování všech (cílený výběr).
- Vývoj: Lepší determinismus hashe → méně zbytečných diff operací; snížená hlučnost debug výstupů.

#### Migrace / Kroky po nasazení
1. `php artisan migrate --force` (pokud ještě nebyl nasazen sloupec `external_edit_id`).
2. (Volitelně) Spustit jednorázově import všech objednávek: `php artisan orders:import-full` (uvnitř kontejneru s DB) – ověřit počty pomocí skriptu `scripts/count_items.php`.
3. Spustit `php artisan orders:backfill-items --limit=300` pro doplnění starších záznamů bez produktových řádků (opakovat dokud `candidates=0`).
4. `php artisan orders:integrity-check` a řešit případné rozdíly (zatím pouze reportuje).
5. Aktivovat noční backfill / integritní report přidáním do `.env`: `ORDERS_BACKFILL_NIGHTLY=true`, `ORDERS_INTEGRITY_DAILY=true`.

#### Odkazy
- `app/Services/Orders/OrderScrapeClient.php`
- `app/Console/Commands/OrdersBackfillItems.php`
- `app/Console/Commands/OrdersIntegrityCheck.php`
- `app/Console/Kernel.php`
- `docs/07-orders/overview.md`
- `docs/07-orders/implementation-plan.md`

#### Poznámky
Adresy a fakturační údaje zatím nejsou ukládány (vědomé zúžení rozsahu). Zůstává fallback heuristika položek – plánované odstranění po širším testu různých typů objednávek.

### [2025-09-06] v0.1.9 (ADDED / CHANGED)
### [2025-09-06] v0.1.10 (ADDED / CHANGED)
### [2025-09-06] v0.1.11 (ADDED / CHANGED)
#### Shrnutí
Konfigurace `config/orders.php`, denormalizace `last_state_code`, integritní metriky a rozšíření OpsActivity.

#### Detaily
- Přidána migrace `add_last_state_code_to_orders_table` a napojení v sync příkazech.
- UI filtrování stavů využívá nyní `last_state_code` (fallback na relaci při starších záznamech).
- OpsActivity meta rozšířena o `integrity_mismatches` pro full/import/reconcile.
- Konfigurační soubor `config/orders.php` (retry attempts, base delay, limity stránek).
- Reconcile příkaz respektuje config default pro pages, pokud není předán parametr.

#### Kroky po nasazení
1. `php artisan migrate --force`
2. (Volitelně) doplnit `.env` proměnné `ORDERS_RETRY_ATTEMPTS`, `ORDERS_RETRY_BASE_DELAY_MS`, `ORDERS_FULL_IMPORT_MAX_PAGES`.
3. Ověřit filtrování podle stavu na `/crm/orders` (mělo by být rychlejší).

#### Odkazy
- `database/migrations/*last_state_code*`
- `config/orders.php`
- `app/Console/Commands/Orders*`
- `app/Http/Controllers/OrderController.php`

#### Shrnutí
UI objednávek (list + detail), diff položek, retry & custom výjimky pro scraping, permission `orders.view`.

#### Detaily
- Přidány Blade view `orders/index` a `orders/show` + controller `OrderController`.
- Filtry: číslo, stav, datum od/do, completed flag; timeline stavů, integritní kontrola částek.
- Diff položek (md5 klíč name|line_type|price|qty) v incremental a reconcile místo full delete.
- Soft deletes pro `order_items` + migrace.
- Retry/backoff helper (`Retry`) + specializované výjimky (`AuthException`, `NetworkException`, `ParseException`).
- Permission `orders.view` + příkaz `orders:permissions-sync`, přidán odkaz v sidenav.
- Aktualizován implementační plán (Fáze 5.1, 5.2 DONE základ; 3.4 DONE – diff).

#### Dopady
- Uživatel: Viditelný seznam a detail objednávky přímo v CRM.
- Provoz: Snížení šumu položek při změnách díky diffs; robustnější scraping.
- Vývoj: Základ pro další observabilitu a mapování stavů.

#### Migrace / Kroky po nasazení
1. `php artisan migrate --force` (soft deletes order_items).
2. `php artisan orders:permissions-sync` a přiřadit roli uživatelům.
3. Ověřit přístup `/crm/orders` s účtem s povolením.

#### Odkazy
- `app/Http/Controllers/OrderController.php`
- `resources/views/orders/*`
- `app/Services/Orders/Support/Retry.php`
- `app/Services/Orders/Exceptions/*`
- `app/Console/Commands/OrdersPermissionsSync.php`

#### Shrnutí
Implementace detailního scrapingu objednávek (položky, hash, stavy) + příkaz denního reconciliation a rozšíření full/incremental sync.

#### Detaily
- `OrderScrapeClient`: přidán `fetchDetail` (heuristické selektory `#order-basic`, `#order-items`, `#order-status-history`), výpočet `source_raw_hash` (sha1 konkat bloků) a parsování položek + stavových kódů.
- Full import (`orders:import-full`): nyní okamžitě načítá detail nových objednávek, ukládá položky a generuje sekvenci `order_state_changes`.
- Incremental sync (`orders:sync-incremental`): detail fetch pro nové i změněné objednávky, detekce změny hash → reinicializace položek + append nových stavů.
- Přidán příkaz `orders:reconcile-recent` (rychlý scan prvních N stránek) a naplánován denně 04:00 (5 stránek) pro dorovnání případných výpadků.
- Aktualizován implementační plán (`07-orders/implementation-plan.md`) – Fáze 2 dokončena (základ), část Fáze 3 & 4 posunuta.
- Refaktor klienta (viditelnost protected) pro usnadnění testování, přidán základ test fixture scaffold (parsovací testy zatím INCOMPLETE pending real HTML).

#### Dopady
- Uživatel: Datově kompletnější objednávky (položky a stavy) dostupné ihned po full/importu nebo během přírůstkového běhu.
- Provoz: Reconciliation command snižuje riziko trvalého vynechání detailu při transient chybě.
- Vývoj: Základ pro budoucí zjemnění diff položek a přesnější timestamps stavů.

#### Migrace / Kroky po nasazení
1. Není potřeba nová DB migrace.
2. Nastavit `ORDERS_FULL_IMPORT_WEEKLY=true` (volitelné) pokud požadováno.
3. Monitorovat první běh `orders:reconcile-recent` v OpsActivity.

#### Odkazy
- `app/Services/Orders/OrderScrapeClient.php`
- `app/Console/Commands/OrdersImportFull.php`
- `app/Console/Commands/OrdersSyncIncremental.php`
- `app/Console/Commands/OrdersReconcileRecent.php`
- `docs/07-orders/implementation-plan.md`


### [2025-09-06] v0.1.8 (DOCS / ADDED)
#### Shrnutí
Přidána koncepce a implementační plán modulu Objednávky (scraping z adminu bez API) – dokumentace sekce `07-orders` (overview + implementation-plan).

#### Detaily
- Navigační návrh a datový model (`orders`, `order_items`, `order_state_changes`).
- Strategie: full import (paged), incremental sync (page 1 diff), denní reconciliation, detail fetch on-demand.
- Hashování HTML bloků pro detekci změn, ukládání sekvence stavových kódů.
- Návrh transformací (parsování částek, kódů stavů, normalizace order number se suffixy).
- Definována rizika & mitigace (HTML změny, throttling, fallback headless režim).
- Security & rate limiting zásady, observabilita (OpsActivity typy).

#### Dopady
- Urychlení následné implementace – jasné akceptační kritéria a fáze s granularitou úkolů.
- Snížení rizika rework díky upfront analýze DOM (listing + detail + položky).
- Připravené podklady pro tvorbu migrací a parserů (deterministické názvy sloupců).

#### Migrace / Kroky po nasazení
1. Zatím žádné DB migrace – čeká se na potvrzení návrhu.
2. Po schválení spustit Fázi 1 (migrace) dle `07-orders/implementation-plan.md`.

#### Odkazy
- `docs/07-orders/overview.md`
- `docs/07-orders/implementation-plan.md`


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
