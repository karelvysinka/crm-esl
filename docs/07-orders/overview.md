---
title: Objednávky - Přehled
description: Stav implementace modulu objednávek (scraping), současné chování, příkazy a pending kroky.
---

# Objednávky (aktuální stav)

Modul je nasazen: full import, incremental (každých 5 min), denní reconcile, parsování položek (produkt/doprava/platba), hash detekce změn, timeline stavů (sekvence kódů), integritní metriky. Původní rozsáhlejší návrh byl zúžen – adresy, fakturační a váhová data jsou odložena.

## Cíle (MVP dosaženo)
- Persistovat základ objednávky + položky + sekvenci stavů.
- Jednorázově nahrát historii (full import) a poté udržovat kontinuální tok.
- Minimalizovat počet requestů (page 1 + jemný jitter) a detekovat změny přes hash.
- Poskytnout audit (OpsActivity) pro běhy a metriky integrity.

## Zdroje dat
Listing: `/admin/order/?grid-page=N` (desc podle vytvoření). Interní edit ID extrahováno z odkazu `/admin/order/edit/{id}` → uložené jako `external_edit_id` (stabilní klíč do detailu a záložky položek `/admin/order/edit/{id}/tab-ite`).

Pozorované sloupce ve výpisu (page 1, 20 / page):
| Sloupec UI | Popis | Interní pole (navrh) | Poznámka |
|------------|-------|-----------------------|----------|
| Fakturováno | Ikona / text (Nefakturováno) | `invoiced` (bool) | Získáme ze souhrnu detailu. |
| Vytvořeno | Datum+čas `dd.mm.yyyy HH:MM` | `created_at_raw` | Převod na UTC / timestamp. |
| Číslo | Číslo objednávky (např. `2025000129`) | `order_number` | Unikátní, lexikálně i číselně roste. |
| Jméno a příjmení | Jméno + (firma) | `customer_name`, `company_name` | Firma je v detailu v IČ/DIČ části. |
| Doprava | Název dopravy | `shipping_method` | Např. `DPD`, `TopTrans`, `Osobní odběr Praha`. |
| Platba | Název platby | `payment_method` | Např. `Dobírka`, `Platba převodem`. |
| Počet položek | Počet řádků (produkt + doprava + platba) | `items_count` | Číslo zahrnuje všechny tři typy. |
| Celkem | Částka s měnou | `total_amount`, `currency` | Parsovat čísla s mezerami + čárka -> desetinná tečka. |
| Stavy (S P V O C Č Z) | Zkratky stavů | `state_codes` (array) | Zachovat pořadí; mapovat na enum tabulku. |
| Vyřízeno (V) | Finální flag | `is_completed` (bool) | Písmeno `V` ve vyřízené kolonce. |

### Detail objednávky – klíčová pole
Z pozorovaného DOM (souhrn + adresy + položky):

| Oblast | Pole | Návrh sloupce | Poznámka |
|--------|------|--------------|----------|
| Hlavička | Čas vytvoření | `created_at` | Přesné sekundové razítko. |
| Hlavička | E-mail | `customer_email` | Unikátnost nevyžadujeme. |
| Hlavička | Telefon | `customer_phone` | Normalizace +420? ponechat raw. |
| Hlavička | Celková hmotnost | `weight_kg` (DECIMAL(8,3)) | Může být NULL. |
| Doplňující | Měna | `currency` | Např. `CZK`, `EUR`. |
| Doplňující | IP adresa | `customer_ip` | IPv4/6 TEXT(45). |
| Doplňující | Registrovaný | `is_registered_customer` | bool. |
| Fakturační | IČO | `company_vat_id_local` | (IČO) |
| Fakturační | DIČ | `company_vat_id` | (DIČ) velikost 32. |
| Fakturační | Jméno | `billing_name` | |
| Fakturační | Ulice / Město / PSČ / Země | `billing_street`, `billing_city`, `billing_postcode`, `billing_country` | |
| Dodací | Název / adresa | `shipping_name`, `shipping_street`, ... | Může se shodovat s fakturační. |
| Ceny souhrn | Cena bez DPH | `subtotal_ex_vat_cents` | Odvozeno z položek, fallback z UI. |
| Ceny souhrn | Cena s DPH | `total_vat_cents` | Hlavní částka. |
| Položky | Produkt / varianta | `order_items` (separátní tabulka) | Každý řádek + řádky dopravy/platby. |

### Položky (aktuální persist)
Záložka `/tab-ite`: interní ID řádku, produkt/varianta kódy, název, množství, jednotka, cena/ks s DPH, DPH %, sleva %, celkem ex/s DPH. Persistujeme subset nutný pro výpočet a zobrazení.

| Pole | Sloupec | Typ |
|------|---------|-----|
| Vazba | `order_id` | BIGINT FK |
| Interní ID řádku | `external_item_id` | BIGINT / VARCHAR(16) |
| Název | `name` | VARCHAR(255) |
| Product code | `product_code` | VARCHAR(64) NULL |
| Variant code | `variant_code` | VARCHAR(64) NULL |
| Specifikace | `specification` | VARCHAR(255) NULL |
| Množství | `quantity` | INT UNSIGNED |
| Jednotka | `unit` | VARCHAR(16) NULL |
| Cena za kus s DPH | `unit_price_vat_cents` | INT UNSIGNED |
| DPH % | `vat_rate_percent` | TINYINT UNSIGNED |
| Sleva % | `discount_percent` | TINYINT UNSIGNED NULL |
| Celkem bez DPH | `total_ex_vat_cents` | INT UNSIGNED |
| Celkem s DPH | `total_vat_cents` | INT UNSIGNED |
| Typ řádku | `line_type` | ENUM(product, shipping, payment, other) |

## Datový model (realizovaný subset)

### Tabulka `orders` (aktivní pole)
`order_number`, `external_edit_id`, `order_created_at`, `total_vat_cents`, `currency`, `is_completed`, `source_raw_hash`, `last_state_code` (+ timestamps). Adresy, fakturace, IP, váha – odloženo.

### Tabulka `order_items`
Viz návrh výše + index `(order_id)` a `(product_code)`, `(variant_code)` pro rychlé mapování později.

### Tabulka `order_state_changes`
Sekvenční append; `changed_at` zatím jednotný timestamp (čas detekce). Přesnější granularita pending.

## Strategie synchronizace
1. Full import (`orders:import-full`) – inicializační historie.
2. Incremental (`orders:sync-incremental`) – každých 5 min stránka 1.
3. Reconcile (`orders:reconcile-recent`) – denní dorovnání prvních 5 stránek.
4. Backfill (`orders:backfill-items`) – doplnění starších bez produktové položky.

## Detekce změn
Hash = SHA1(hlavička + HTML blok stavů + záložka položek). Změna → diff položek (md5 name|line_type|unit_price|qty|total) + append stavů; přepočtená meta integrita logována.

## Mapování stavů
Pozorované kódy: `S P V O C Č Z` + `V` (Vyřízeno). `config/order_states.php` uchovává raw; labely a kategorizace (logistika, platba) TBD.
| Kód | Label příklad | Typ | Finální? |
|-----|---------------|-----|----------|
| S | Stornováno / nebo Stav „Přijato“? (ověřit přes detail) | status | ne |
| P | Přijato | status | ne |
| V | Vyskladněno | logistika | ne |
| O | Odesláno | logistika | ne |
| C | Čeká se na platbu | platba | ne |
| Č | Částečně? / (nutné ověření) | status | ne |
| Z | Zaplaceno | platba | ne |
| (Vyřízeno) | Vyřízeno | final | ano |

Do potvrzení mapujeme konzervativně – ukládáme raw sekvenci, UI může později zobrazit „neznámý kód“.

## Technologie scrapingu
HTTP klient + DOM parser (Symfony HttpClient + DomCrawler). Záložka položek se načítá extra requestem na `/tab-ite`. Headless fallback plánován (flag `ORDERS_SCRAPE_HEADLESS`).

Kroky loginu:
1. GET `/admin/` → získání přihlašovacího formuláře (hidden token / cookie start).
2. POST credentials (username, password, CSRF hidden input) → uložení cookies do persistent store (filesystem `storage/app/sessions/orders-sync.json`).
3. Reuse cookies pro další GET.
4. Na 401 / redirect na login → re-login (max 3x).

Knihovny: `symfony/http-client` + `symfony/dom-crawler` + `masterminds/html5` (robust parsing) nebo nativní `DOMDocument` s fallback.

Fallback režim: pokud se změní markup zásadně → přepneme na plnohodnotný headless (spustitelné přes externí service) – definovat feature flag `ORDERS_SCRAPE_HEADLESS=1`.

## Rate limiting & šetrnost
Incremental: 1 listing + (detaily jen nových / změněných). Jitter 200–500 ms. Full import manuálně / plán volitelně weekly.

## Chybové scénáře & retry
| Scénář | Reakce |
|--------|--------|
| Timeout / 5xx | Exponenciální backoff (1s, 3s, 9s) max 3 pokusy stránka → log error + pokračuj další stránkou. |
| HTML struktura neodpovídá | Raise structured `ParseStructureException` → stop job, OpsActivity critical. |
| Duplicita order_number (teoreticky) | Log warn, ignorovat duplikát. |
| Měna neparsovatelná | Fallback currency=UI default (CZK) + log. |
| Číslo obsahuje suffix `(1.Mája)` | Uložit raw do `order_number_raw`, extrahovat base numerickou sekvenci do `order_number_seq`. |

## Bezpečnost
- Credentials pouze v `.env` (`ORDERS_SYNC_USER`, `ORDERS_SYNC_PASSWORD`).
- Žádný plaintext v logu (maskovat heslo, session id).
- User-agent vlastní identifikátor `CRM OrdersSyncBot/1.0 (+contact)`.
- Ochrana proti SSRF – povolit pouze host `www.esl.cz` / `esl.cz`.

## Observabilita
OpsActivity: `orders.full_import`, `orders.incremental_sync`, `orders.reconcile_recent`. Metriky integrit `integrity_mismatches`. Budoucí: Prometheus export.

## Postupná adopce
Fáze 1 – minimální schema + full import.
Fáze 2 – incremental + detail fetch + state changes.
Fáze 3 – optimalizace, metriky, UI listing v CRM.
Fáze 4 – reporting & notifikace (nové velké objednávky, zpožděné platby).

## Edge Cases
- Multi-měna (EUR) – per-order currency (zatím jednotné CZK v datech).
- Řádky dopravy / platby – zahrnuty do počet položek a total.
- Odstranění objednávky – neznačíme smazání (historická zachovanost).
- Potenciální suffix v čísle – zatím nepozorován.

## Budoucí rozšíření
- Napojení na fakturační systém (match invoice numbers / PDF archiv).
- SLA metriky (čas od objednávky do vyskladnění / zaplacení / vyřízení).
- Notifikační kanál (Slack / e-mail) pro objednávky nad prahovou hodnotu.

---
## Stav funkcionalit

| Oblast | Stav |
|--------|------|
| Full import | HOTOVO |
| Incremental sync | HOTOVO |
| Reconcile scan | HOTOVO |
| Diff položek (hash) | HOTOVO |
| Timeline stavů | ZÁKLAD (bez přesných časů z detailu) |
| Denormalizace last_state_code | HOTOVO |
| UI listing + detail | HOTOVO (subset polí) |
| Integrity kontrola (sum items vs total) | LOGOVÁNO (OpsActivity) |
| Rozšířené adresy / fakturační data | ODLOŽENO |
| Mapování stavů (labely) | ČÁSTEČNĚ (raw) |
| Observabilita (retry counts) | ZÁKLAD |

## Poznámky
- Rozšíření adres lze přidat non-breaking migrací.
- Přesnější timestamps stavů: potřebné reverse engineering detailu (pending).
- Budoucí odstranění fallback heuristiky položek po delším provozu.
