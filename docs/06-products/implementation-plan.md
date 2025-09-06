---
title: Produkty - Implementační plán
description: Detailní kroky implementace produktového modulu a synchronizace Heureka feedů.
---

# Implementační Plán Produktového Modulu

Tento dokument rozepisuje konkrétní úkoly, jejich pořadí, akceptační kritéria a technické detaily.

## Legenda stavů
- TODO – neimplementováno
- IN PROGRESS – rozpracováno
- DONE – hotovo (merge v main + changelog)

## Fáze 1: Datový Model
| Krok | Popis | Výstup | Kritéria | Stav |
|------|-------|--------|----------|------|
| 1.1 | Migrace tabulky `products` | migration + model | Sloupce dle návrhu, indexy external_id, ean, category_path | TODO |
| 1.2 | Audit tab. `product_price_changes` | migration | FK na product, old/new value, created_at | TODO |
| 1.3 | Audit tab. `product_availability_changes` | migration | FK na product, old/new code, stock qty, created_at | TODO |
| 1.4 | Eloquent model `Product` + scopes | Model | Scopes: filterCategory, filterAvailability, filterManufacturer, search | TODO |

## Fáze 2: Full Import Pipeline
| Krok | Popis | Výstup | Kritéria | Stav |
|------|-------|--------|----------|------|
| 2.1 | Parsovací služba (stream) | Service class | Umí iterovat položky bez načtení celého XML do paměti | TODO |
| 2.2 | Canonical builder + hash | Service | Stabilní SHA1 generace nad sorted keys | TODO |
| 2.3 | Command `products:import-full` | Artisan command | Log summary (new/updated/unchanged/errors) | TODO |
| 2.4 | Upsert logic (chunked) | Service method | Chunk <=1000, transakce, minimalizace N+1 | TODO |
| 2.5 | Ops event log integration | Event dispatch | Záznam v ops_events s metrikami | TODO |

## Fáze 3: Availability Delta Sync
| Krok | Popis | Výstup | Kritéria | Stav |
|------|-------|--------|----------|------|
| 3.1 | Parsování availability feedu | Service | Map external_id → changes | TODO |
| 3.2 | Command `products:sync-availability` | Command | Statistiky: changed count, skipped, errors | TODO |
| 3.3 | Audit zápis availability změn | Insert logic | Správně uchovává historii | TODO |
| 3.4 | Ops event log integration | Event | Událost products.availability_sync | TODO |

## Fáze 4: UI - Seznam & Detail
| Krok | Popis | Výstup | Kritéria | Stav |
|------|-------|--------|----------|------|
| 4.1 | Route + Controller index | Controller + route | Autorizace products.view | TODO |
| 4.2 | Blade seznam (Adminto) | View | Breadcrumb, collapsible filtry, clickable rows, tooltips sync info | TODO |
| 4.3 | Detailová stránka (Adminto) | View + controller show | Tabs historie (cena/dostupnost), lazy obrázek, badges | TODO |
| 4.4 | Filtrovací logika (scopes) | Implementace | Kombinace filtrů bez výrazného zpomalení | TODO |
| 4.5 | Permission seeding | Seeder update | Admin role má products.view + products.sync | TODO |
| 4.6 | UX polish & a11y | Review checklist | Tooltips aktivní, klávesová navigace, kontrast badges | TODO |

## Fáze 5: Integrace Ops & Scheduler
| Krok | Popis | Výstup | Kritéria | Stav |
|------|-------|--------|----------|------|
| 5.1 | Naplánování cronů | Kernel schedule | Cron zápisy viditelné v generovaném schedule.md | TODO |
| 5.2 | Ops dashboard widget | Blade partial | Zobrazuje poslední full import & availability sync | TODO |
| 5.3 | Ruční spuštění akcí | Ops controller endpoint | Throttle + autorizace products.sync | TODO |

## Fáze 6: Logování a Observabilita
| Krok | Popis | Výstup | Kritéria | Stav |
|------|-------|--------|----------|------|
| 6.1 | Chybové retry wrapper | Trait / helper | Exponenciální backoff (max 3) | TODO |
| 6.2 | Maskování feed URL tokenu | Logger config | Token se v logu nikdy neobjeví v plné podobě | TODO |
| 6.3 | Alert podmínky (budoucí) | Spec | Definice pravidel (doc) | TODO |

## Fáze 7: Optimalizace & Hardening
| Krok | Popis | Výstup | Kritéria | Stav |
|------|-------|--------|----------|------|
| 7.1 | Indexy a EXPLAIN ověření | SQL / dokument | EXPLAIN u filtrů < 50 ms při 100k řádcích (cíleně) | TODO |
| 7.2 | Memory footprint test | Report | Full import < 512MB peak | TODO |
| 7.3 | Rate limiting ručních synců | Middleware | Zabránění zneužití (např. 5/min) | TODO |

## Datové transformace (detail)
```php
canonical = [
  'external_id' => (string)$xml->ITEM_ID,
  'name' => trim((string)$xml->PRODUCTNAME),
  'description' => sanitizeHtml((string)$xml->DESCRIPTION),
  'ean' => (string)$xml->EAN ?: null,
  'manufacturer' => (string)$xml->MANUFACTURER ?: null,
  'category_path' => buildCategoryPath($xml),
  'url' => (string)$xml->URL,
  'image_url' => (string)$xml->IMGURL ?: null,
  'price_vat' => (float)$xml->PRICE_VAT,
  'price_original_vat' => (float)$xml->ITEM_TYPE == 'action' ? (float)$xml->ORIGINAL_PRICE_VAT : null,
  'currency' => 'CZK',
  'availability_code' => (string)$xml->DELIVERY_DATE,
  'availability_text' => mapAvailability((string)$xml->DELIVERY_DATE),
  'stock_quantity' => null, // z availability feedu později
  'delivery_date' => (string)$xml->DELIVERY_DATE ?: null,
];
```

Hash generace:
```php
$ordered = collect($canonical)->sortKeys()->all();
$hash = sha1(json_encode($ordered, JSON_UNESCAPED_UNICODE));
```

## Pseudokód full importu
```php
foreach (streamHeurekaProducts($xmlFile) as $item) {
  $canonical = buildCanonical($item);
  $hash = canonicalHash($canonical);
  $existing = $repo->findByExternalId($canonical['external_id']);
  if (!$existing) { $toInsert[] = [...$canonical, 'hash_payload'=>$hash]; continue; }
  if ($existing->hash_payload !== $hash) {
     $changes = diffPrice($existing, $canonical); // pokud cena jiná -> audit price change
     $toUpdate[] = [...$canonical, 'id'=>$existing->id, 'hash_payload'=>$hash];
  }
  if (count($toInsert)+count($toUpdate) >= 1000) { flushUpserts(); }
}
flushUpserts();
```

## Pseudokód availability sync
```php
foreach (streamHeurekaAvailability($xmlFile) as $item) {
  $externalId = (string)$item->ITEM_ID;
  $newCode = (string)$item->DELIVERY_DATE;
  $newQty = parseQty($item);
  $p = $repo->findByExternalId($externalId);
  if (!$p) continue;
  if ($p->availability_code !== $newCode || $p->stock_quantity !== $newQty) {
     auditAvailability($p, $newCode, $newQty);
     $p->availability_code = $newCode;
     $p->availability_text = mapAvailability($newCode);
     $p->stock_quantity = $newQty;
     $p->availability_synced_at = now();
     $p->save();
  }
}
```

## Akceptační kritéria modulu (MVP)
- Full import dokončí < 30 min pro feed do 100k položek.
- Delta sync < 2 min.
- UI načte seznam < 2 s při 50k položkách (paginace + indexy).
- Audit tabulky neobsahují duplikáty stejných změn back-to-back.
- UX: Filtry reagují < 500 ms, historie načtena bez refresh (XHR) do tabů.

## Changelog
Po dokončení každé fáze přidat položku (`ADDED`).

## Bezpečnostní opatření
- Rate limit ruční sync akce.
- Validace URL aby nešlo stáhnout jiný host.
- Ošetřit XML entity (libxml_disable_entity_loader / LIBXML_NOENT nepoužívat).

---

Aktualizuj průběžně dle skutečné implementace.

## Analýza Heureka feedu (real data)

Zdroj: stažený soubor `docs/feeds/heureka.xml` (≈2.6 MB, 2332 položek).

### Přehled tagů (frekvence a statistiky)
```
ITEMS (SHOPITEM)........ 2332
ITEM_ID................. 2332 (numeric, max délka 6 -> rezerva varchar(32))
PRODUCTNAME............. 2332 (max 105 znaků)
PRICE_VAT............... 2332 (0 – 318230; čárka jako desetinný oddělovač)
DESCRIPTION............. 2332 (prázdné 545; max délka 2350 po normalizaci)
IMGURL.................. 2325 (7 chybí) [.jpg 1280 | .gif 514 | .png 508 | .jpeg 23]
MANUFACTURER............ 660 (nullable)
CATEGORYTEXT............ 2332 (max 148 znaků)
EAN..................... 1773 (délky: 5..15, převaha 9 -> není čisté EAN13)
ITEMGROUP_ID............ 1992 (unique groups 359; všechny multi; 340 bez skupiny)
DELIVERY_DATE........... distribuce: 7=1624, 0=681, prázdné=26, 3=1
DELIVERY_ID............. vždy TOPTRANS
PARAM bloky............. 160 (jen 72 položek mají param → nízká adopce)
```

### Revize schématu `products`
Sloupce (srovnáno podle reálných dat):
```
id BIGINT AI PK
external_id VARCHAR(32) UNIQUE
group_id VARCHAR(32) NULL INDEX
name VARCHAR(150)
description TEXT NULL
price_vat_cents INT UNSIGNED
currency CHAR(3) DEFAULT 'CZK'
manufacturer VARCHAR(80) NULL
ean VARCHAR(20) NULL INDEX
category_path VARCHAR(255)
category_hash CHAR(40) INDEX
url VARCHAR(255)
image_url VARCHAR(255) NULL
availability_code VARCHAR(8) INDEX
availability_text VARCHAR(32)
stock_quantity INT UNSIGNED NULL
availability_synced_at TIMESTAMP NULL
hash_payload CHAR(40)
first_imported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
last_synced_at TIMESTAMP NULL
last_price_changed_at TIMESTAMP NULL
last_availability_changed_at TIMESTAMP NULL
created_at TIMESTAMP
updated_at TIMESTAMP
```

### Audit tabulky (upraveno)
product_price_changes(id, product_id FK, old_price_cents, new_price_cents, changed_at, INDEX(product_id, changed_at desc))

product_availability_changes(id, product_id FK, old_code, new_code, old_stock_qty, new_stock_qty, changed_at, INDEX(product_id, changed_at desc))

### Transformace
- PRICE_VAT → replace "," -> "." → float → cents (round) → int
- DESCRIPTION → trim, komprese whitespace, strip HTML tagů
- CATEGORYTEXT → split `|` → trim → join ` > `; hash = sha1(lower(join('|', normalSegments)))
- EAN → uložit raw (regex volně: `[0-9A-Za-z_-]{5,20}`), nevalidujeme EAN13
- ITEMGROUP_ID → uppercase, empty => NULL
- IMGURL → validace protokolu + přípona (.jpg/.jpeg/.png/.gif) jinak NULL
- DELIVERY_DATE → availability_code; mapování v configu

### Mapování dostupnosti (počáteční návrh)
```
7 => Skladem
0 => Na objednávku
3 => Do 3 dnů
"" => Neznámo
default => Neznámo
```

### Odložené prvky (mimo MVP)
- PARAM atributy → budoucí tabulka product_attributes nebo JSON.
- DETAIL dopravy (ceny TOPTRANS) → nyní ignorováno.

### Edge cases pokrytí
- 7 chybějících IMGURL → povoleno (NULL)
- 545 prázdných DESCRIPTION → povoleno
- Heterogenní EAN délky → no UNIQUE constraint
- Varianty (group_id) – index pro listingy
- Prázdné DELIVERY_DATE → map na Neznámo

### Aktualizace plánovaných kroků
- Fáze 1.1 doplnit: category_hash, hash_payload, časové sloupce změn.
- Fáze 2 generuje category_hash + hash_payload + sleduje price delty.
- Fáze 3 jen availability_code / stock_quantity + audit availability.

### Další akce
1. Připravit migration skeleton dle výše.
2. Vytvořit config/products.php s availability map.
3. Implementovat stream parser s převodem PRICE_VAT do cents.
4. Přidat helper pro category_hash.
5. Připravit unit testy (price parsing, category normalize, availability map).
