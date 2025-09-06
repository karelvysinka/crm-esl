---
title: Objednávky - Implementační plán
description: Detailní implementační kroky pro scraping a synchronizaci objednávek do CRM.
---

# Implementační plán objednávek

Legenda stavů: TODO / IN PROGRESS / DONE

## Fáze 1: Základní schéma & infra
| Krok | Popis | Výstup | Kritéria | Stav |
|------|-------|--------|----------|------|
| 1.1 | Migrace `orders` (subset) | migration | Minimální sloupce: order_number, created_at, total_vat_cents, currency | DONE |
| 1.2 | Migrace `order_items` | migration | FK + základní cenová pole | DONE |
| 1.3 | Migrace `order_state_changes` | migration | FK + index (order_id, changed_at) | DONE |
| 1.4 | Eloquent modely + vztahy | models | Order hasMany items / states | DONE |
| 1.5 | Konfig `order_states.php` | config | Raw map + labels (zatím placeholder) | DONE |

## Fáze 2: Full import crawler
| Krok | Popis | Výstup | Kritéria | Stav |
|------|-------|--------|----------|------|
| 2.1 | HTTP klient + login flow | service | Umí získat session cookies | DONE |
| 2.2 | Parser listing page | service | Extrahuje 20 řádků → DTO kolekci | DONE (robust: table+anchor+regex fallback) |
| 2.3 | Stránkování loop | command `orders:import-full` | Projde všechny stránky, respektuje throttle | DONE |
| 2.4 | Detekce nových objednávek | repo logika | Unikátní order_number insert | DONE (základ) |
| 2.5 | Per-order detail fetch | service | Načte detail + položky + stavy (1 request) | DONE (základ heuristický) |
| 2.6 | Hash + změnová detekce | util | Stabilní hash bez akčních prvků | DONE (sha1 bloků) |
| 2.7 | OpsActivity log | integration | Záznam metrik (pages, new, duration) | DONE |

## Fáze 3: Přírůstkový sync
| Krok | Popis | Výstup | Kritéria | Stav |
|------|-------|--------|----------|------|
| 3.1 | Command `orders:sync-incremental` | command | Běží co 5 min (cron) | DONE (základ) |
| 3.2 | Page 1 diff strategie | service | Zastaví se při starém order_number | DONE (heuristický early break) |
| 3.3 | Aktualizace změn stavů | logic | Zapíše order_state_changes nové | DONE (sekvenční append – uniform timestamp) |
| 3.4 | Aktualizace položek (diff) | logic | Přidá / změní, nemaže hard (soft replace) | ČÁSTEČNĚ (hash diff + hard delete removed) |
| 3.5 | OpsActivity log metrics | integration | orders_new, orders_updated | DONE |

## Fáze 4: Reconciliation & hardening
| Krok | Popis | Výstup | Kritéria | Stav |
|------|-------|--------|----------|------|
| 4.1 | Denní rychlý scan (5 stránek) | command | `orders:reconcile-recent` | DONE |
| 4.2 | Robustní error classification | util | Specifické výjimky (Network, Parse, Auth) | ČÁSTEČNĚ (základ vyjímek) |
| 4.3 | Retry/backoff wrapper | helper | Exponenciální backoff (3 pokusy) | ČÁSTEČNĚ (manuální retry v klientu) |
| 4.4 | Headless fallback (flag) | service | Přepne render path | TODO |
| 4.5 | Rate limiting & jitter | util | Konfigurovatelné v .env | TODO |

## Fáze 5: UI & reporting
| Krok | Popis | Výstup | Kritéria | Stav |
|------|-------|--------|----------|------|
| 5.1 | CRM list `/crm/orders` | view+controller | Sloupce: číslo, datum, zákazník, částka, stav | TODO |
| 5.1 | CRM list `/crm/orders` | view+controller | Sloupce: číslo, datum, částka, stav | DONE |
| 5.2 | Detail objednávky | view | Hlavička, adresy, položky, timeline stavů | TODO |
| 5.2 | Detail objednávky | view | Hlavička, položky, timeline stavů | DONE (základ) |
| 5.3 | Filtry (datum od-do, stav, měna, fulltext) | query scopes | kombinovatelné indexované | TODO |
| 5.4 | Ops dashboard widget | partial | Poslední incremental + počty | TODO |
| 5.5 | Export CSV | action | Omezeno rolemi, throttled | TODO |

## Fáze 6: Observabilita & kvalita dat
| Krok | Popis | Výstup | Kritéria | Stav |
|------|-------|--------|----------|------|
| 6.1 | Metriky (proměnná tabulka) | ops events | durations, counts | TODO |
| 6.2 | Varování stagnace (žádné nové 24h) | alert rule (doc) | definovaná pravidla | TODO |
| 6.3 | Validace částek (součet položek) | integrity check | rozdíl <= 1 Kč / cent | DONE (report only) |
| 6.4 | Deduplikace duplicit hash | guard | skip duplicate insert | TODO |

## Datové transformace – klíčové funkce
```php
parseMoney('1 783,50 Kč') => 178350 (cents)
normalizeOrderNumber('2025000124(1.Mája)') => ['raw' => '2025000124(1.Mája)', 'base' => '2025000124']
extractStateCodes('S P V O C Č Z') => ['S','P','V','O','C','Č','Z']
```

## Pseudokód – listing fetch
```php
for ($page=1; $page<= $max; $page++) {
  $html = client->get('/admin/order/?grid-page='.$page.'&grid-sort[created]=desc');
  $rows = parseListing($html); // => array<OrderRowDTO>
  foreach ($rows as $r) {
    if (!exists($r->orderNumber)) collectForInsert($r); // basic fields
  }
  sleep(jitter());
}
hydrateDetails(newOnes());
```

## Pseudokód – incremental sync
```php
$html = client->get('/admin/order/?grid-sort[created]=desc');
$rows = parseListing($html);
foreach ($rows as $r) {
  if (isNew($r->orderNumber)) { insertBasic($r); queueDetail($r->orderNumber); }
  else { break; }
}
processDetailQueue();
```

## Akceptační kritéria (MVP)
- Full import dokončí < 10 min pro 500 objednávek.
- Incremental běh < 5 s v průměru (page 1 + nové detaily ≤ 5 nových).
- Žádné zduplikované `order_number`.
- Změna stavu v admin se projeví v CRM do 15 min.
- Odolnost: dočasný výpadek (1 stránka 500) nezpůsobí abort celé relace (max 3 retrypy, pak pokračuj).

## Bezpečnostní & provozní zásady
- .env secret rotation → ruční invalidace session při změně hesla.
- Logy: mask `password`, `PHPSESSID`.
- Limitované role: pouze interní system user může spouštět full import.

## Testování
- Unit: parsování jednoho řádku listing / detail HTML fixture.
- Integration: simulace 2 stránek pomocí lokálních HTML (snapshot test) – nezávislé na živém prostředí.
- Smoke: artisan command v dry-run režimu (neukládá) – ověří strukturální konzistenci.

## Rizika & mitigace
| Riziko | Dopad | Mitigace |
|--------|-------|----------|
| Změna HTML struktury | Parse chyby | Fixtures + central parser + rychlá úprava | 
| Blokace IP (mnoho requestů) | Nedostupnost | Throttle + user-agent + limit requestů | 
| Nesoulad částek | Špatné reporty | Integritní kontrola subtotal vs items | 
| Nejednoznačné stavy | Nesprávné dashboardy | Ukládat raw + mapovat později | 

## Další kroky po MVP
- Timeline vizualizace průběhu stavů (Gantt / sekvence).
- SLA výpočty (čas do Zaplaceno, čas do Vyřízeno).
- Export do BI / datového skladu.

---
Aktuální stav: Fáze 1–3 + části Fáze 4 a 6 hotové; zbývají adresy, přesné timestamps stavů, plná observabilita a odstranění fallback heuristik.
