---
title: Produkty - Přehled
description: Koncepční návrh produktového modulu a jeho synchronizace z Heureka feedů.
---

# Produkty (Návrh)

Tato sekce popisuje plán zavedení produktového modulu do CRM včetně:
- Datového modelu
- Importu ze zdrojových feedů Heureka (zboží + dostupnosti)
- Synchronizační strategie (delta aktualizace)
- UI stránky pro správu / náhled produktů
- Monitoring a chybové zachycení

## Cíle
- Centralizovat produktová data (název, EAN, kategorie, ceny, dostupnost, URL, popis, výrobce) uvnitř CRM.
- Umožnit obchodnímu týmu rychlý přehled a filtrování.
- Průběžně aktualizovat dostupnost a ceny bez plného reimportu.
- Logovat změny (audit) pro analýzu vývoje cen a dostupností.

## Zdroje dat
| Feed | URL | Popis | Formát | Frekvence načtení |
|------|-----|-------|--------|-------------------|
| Heureka zboží | https://www.esl.cz/feed/heureka?token=5f0dac7b23e1d | Kompletní katalog produktů (záznamy) | XML | 1× denně full refresh |
| Heureka dostupnosti | https://www.esl.cz/feed/heureka-stocks?token=5f0dac7b23e1d | Průběžné změny dostupnosti / skladů | XML | každých 15 min (delta) |

## Datový model (návrh)
Hlavní tabulka: `products`

| Sloupec | Typ | Poznámka |
|---------|-----|----------|
| id | BIGINT PK | interní ID |
| external_id | VARCHAR(100) index | ID z feedu (např. ITEM_ID) |
| name | VARCHAR(255) | Název |
| description | LONGTEXT nullable | Popis (HTML / text) |
| ean | VARCHAR(50) index nullable | EAN kód |
| manufacturer | VARCHAR(120) nullable | Výrobce |
| category_path | VARCHAR(255) | Kategoriální hierarchie (sloučený string) |
| url | VARCHAR(500) | Produktová URL (eshop) |
| image_url | VARCHAR(500) nullable | Hlavní obrázek |
| price_vat | DECIMAL(10,2) | Aktuální cena s DPH |
| price_original_vat | DECIMAL(10,2) nullable | Původní / doporučená cena |
| currency | CHAR(3) default 'CZK' | Měna |
| availability_code | VARCHAR(50) | Kód dostupnosti (např. skladem) |
| availability_text | VARCHAR(120) | Překlad / text dostupnosti |
| stock_quantity | INT nullable | Počet kusů (pokud feed poskytuje) |
| delivery_date | VARCHAR(50) nullable | Termín dodání (pokud feed poskytuje) |
| hash_payload | CHAR(40) | SHA1 canonical payload (detekce změn full feed) |
| last_synced_at | TIMESTAMP | Poslední úspěšná synchronizace z full feedu |
| availability_synced_at | TIMESTAMP nullable | Poslední delta dostupnosti |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

Audit / změny: `product_price_changes`, `product_availability_changes` (pouze změny).

## Importní pipeline
### 1. Full Import (Daily)
- Scheduler job `products:import-full` (1× denně, např. 04:10).
- Stáhne celý Heureka feed (zboží XML) do dočasného souboru.
- Stream / SAX parsování (nižší paměťový footprint) -> canonical array -> SHA1 JSON -> hash.
- Detekce nových / změněných produktů porovnáním `hash_payload`.
- Upsert změněné záznamy v transakcích (chunky 500–1000).
- Log: počet nových, změněných, nezměněných, chybné položky.

### 2. Availability Delta (Every 15 min)
- Scheduler job `products:sync-availability`.
- Načte dostupnost feed (menší XML) – iterace položek -> match by `external_id`.
- Pokud změna v `availability_code` nebo `stock_quantity` → uložit a insert do audit tabulky.
- `availability_synced_at` update pro produkt.

### 3. Error Handling
- Síť / HTTP chyba → retry (exponenciální backoff max 3x) -> pokud fail, uložit záznam do `ops_events` pro monitoring.
- XML parsing error → přeskočit položku + log detailu (limit 20 položek na jeden běh).

## Canonicalizace dat
- Trim whitespace, unifikace měny na CZK.
- Ceny parse jako float → DECIMAL.
- Kategorie join path `>` (např. "Elektronika > Audio > Sluchátka").
- Hash: SHA1 JSON seřazených klíčů (stabilní pořadí pro opakovatelnost).

## UI Stránka
Route: `GET /crm/products`

Základní seznam:
- Sloupce: Název, Cena (aktuální vs původní), Dostupnost (barevný badge), Výrobce, Kategorie (první 2 úrovně), Posl. sync ikonky.
- Filtrování: kategorie (prefix match), dostupnost, výrobce, cenové rozpětí, fulltext název/EAN.
- Akce (MVP): refresh availability (async trigger), detail produktu.

Detail produktu:
- Základní info + historie změn (posledních X cen a dostupností timeline).

### UI a šablona (Adminto)
Použije se existující Adminto layout (`@extends('layouts.base')`).

Seznam (index) – komponenty:
- Breadcrumb: "CRM / Produkty".
- Header toolbar: tlačítko "Ruční sync dostupnosti" (pokud `products.sync`), export CSV (etapa později), badge s počtem produktů.
- Filtrační panel (collapsible card) se základními poli (text search, kategorie select (lazy), výrobce select, dostupnost multi-select, cenové min/max).
- Tabulka: čistá Bootstrap table + sticky header (CSS), stránkování dole (Laravel paginator). Každý řádek kliknutelný (JS) -> detail.
- Dostupnost: barevné badge mapované dle kódu (např. zelená = skladem, oranžová = do 3 dnů, šedá = neznámé) – konzistentní s definicí v mapovací funkci.
- Poslední sync: dvě ikonky (full import vs availability) s tooltipem (`data-bs-toggle="tooltip"`).

Detail:
- Breadcrumb: "CRM / Produkty / {Název}".
- Levý sloupec (col-lg-8): základní karta (název, výrobce, kategorie path jako small text, cena + původní cena přeškrtnutá pokud existuje), dostupnost badge, primary akce (aktualizovat dostupnost jedné položky – async).
- Pravý sloupec (col-lg-4): obrázek (lazy), metadata (EAN, external_id, URL link target _blank), poslední sync timestamps.
- Tabs: "Historie cen", "Historie dostupnosti" – timeline komponenta (vertical list) s datem + změna stará → nová (šipka). Paginace / infinite scroll (limit 50 záznamů na načtení).

Interakce / JS:
- Minimální JS (Bootstrap tooltips + jednoduchý row click) – žádná heavy SPA knihovna.
- Filtrovací formulář submit přes GET (SEO / shareable URL), persistent parametry.
- Loading stav pro ruční sync (disable button + spinner) – využít existující spinner utility z Adminto (viz dokumentace `Adminto Šablona`).

Přístupnost / uživatelská přívětivost:
- Tab index zachován, filtry seskupeny přes `<fieldset>`.
- Zajištěné kontrastní barvy pro badge (WCAG AA). 

Reference: viz `Adminto Šablona` dokument pro konvence komponent a utility třídy.

## Monitoring / Observabilita
Implementováno: zapisuje se do `ops_activities` (type `products.full_import`, `products.sync_availability`) – `meta` obsahuje metriky (new, updated, unchanged / updated, skipped, missing; duration_ms).
Plán (budoucí): alerting (pokud full import > 48h starý nebo >5 po sobě jdoucích delt bez změn / s chybou).

## Napojení na existující Ops modul
- Ops dashboard zobrazí poslední full import + poslední availability sync.
- Akce: ruční vyvolání full importu / availability sync (throttle + autorizace `can:ops.execute`).

## Permission Model
- Nová permission skupina `products.view`, `products.sync`.
- Default admin role → obojí povoleno.

## Scheduler Plán (návrh)
| Job | Cron | Popis |
|-----|------|-------|
| products:import-full | 10 4 * * * | Full feed (zboží) |
| products:sync-availability | */15 * * * * | Dostupnosti delta |

## Technická implementace (etapy)
1. Migrace DB tabulek (`products`, audit tabulky). – HOTOVO
2. Parsovací služba + hash + full import command. – HOTOVO
3. Availability sync command + audit. – HOTOVO (MVP)
4. UI seznam + základní filtrování. – HOTOVO (rozšíření filtrů pending)
5. Detail produktu + historie. – HOTOVO
6. Integrace s Ops modulem (události, ruční spuštění). – HOTOVO (CLI; UI trigger pending)
7. Permissions + cron zápis do schedule dokumentace. – HOTOVO
8. Optimalizace (indexy: external_id, ean, category_path prefix, availability_code) – PENDING

## Edge Cases
- Produkt odstraněn z feedu: (MVP) ignorovat, dlouhodobě přidat `is_active` flag při neexistenci v dalších 7 dnech.
- Extreme ceny (0 nebo > přiměřený strop) – validovat a logovat.
- Duplicitní external_id → log + skip druhý výskyt.

## Bezpečnost
- Externí feed token je v URL – nepersistovat do DB (jen v env pokud bude potřeba). V logu maskovat.
- Ochrana proti DoS: limit velikosti staženého souboru (např. 50MB guard).

## Budoucí rozšíření
- Napojení na pricing engine (marže, dynamika).
- Uložení více obrázků (nová tabulka `product_images`).
- Fulltext index (Meilisearch / Elastic) pro pokročilé vyhledávání.

---

Stav: Základní implementace dokončena; následují optimalizace, UI akce a rozšíření filtrů.
