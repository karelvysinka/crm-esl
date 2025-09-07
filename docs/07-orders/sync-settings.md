---
title: Objednávky – Synchronizační nastavení
description: Konfigurace a princip automatické synchronizace objednávek.
---

# Objednávky – Synchronizační nastavení

Tato stránka popisuje UI stránku `Objednávky > Nastavení synchronizace` a interní procesy.

## Cíle
- Umožnit administrátorovi upravit zdrojovou URL, přihlašovací údaje a interval běhu.
- Zobrazit historii běhů (úspěchy / selhání) a základní metriky.
- Umožnit bezpečné vypnutí / zapnutí automatické synchronizace.

## Datové entity
| Tabulka | Účel | Klíčová pole |
|---------|------|--------------|
| `order_sync_settings` | Jediný řádek konfigurace | `source_url`, `username`, `password_encrypted`, `interval_minutes`, `enabled` |
| `order_sync_runs` | Log jednotlivých běhů | `started_at`, `finished_at`, `status`, `new_orders`, `updated_orders`, `message` |

`password_encrypted` je ukládáno přes Laravel `encrypt()`. Zobrazení hesla zpět v UI není podporováno (write-only).

## Interval
Skutečný interval hlídá job `AutoSyncOrdersJob`, který běží každou minutu a kontroluje:
1. Zda jsou k dispozici tabulky.
2. Zda je synchronizace povolena (`enabled`).
3. Zda od posledního běhu uplynulo alespoň `interval_minutes`.

Teprve poté spustí `orders:sync-incremental` a zapíše výsledek do `order_sync_runs`.

## UI prvky
- Panel KPI: Celkem běhů / Úspěšné / Neúspěšné.
- Formulář konfigurace: URL, uživatel, heslo (ponechat prázdné = neměnit), interval v minutách, checkbox Enabled.
- Log tabulka: Posledních 15 běhů, doba trvání a status.
- Fallback stránka pokud tabulky chybí (instrukce spustit migrace).

## Bezpečnost
- Přístup chráněn stejným oprávněním jako prohlížení objednávek (`orders.view`).
- Heslo se při update přepisuje pouze když není input prázdný.
- Žádné přímé zobrazování plaintext hesla.

## Chybové stavy
| Situace | Řešení |
|---------|--------|
| Chybí tabulky | Zobrazí se fallback + instrukce migrate |
| Výjimka během sync | Status `failed`, zkrácená message v logu |
| Interval ještě neuplynul | Job prostě ukončí bez záznamu |

## Rozšíření do budoucna
- Detail běhu s kompletním logem.
- Manuální re-run tlačítko v UI.
- Graf trendu úspěšnosti.
- Více zdrojových endpointů (multi-row settings + prioritizace).

## Akceptační kritéria
- Uložení formuláře přepíše konfiguraci a redirectne se zprávou.
- Změna `interval_minutes` se projeví nejpozději do 1 min od dalšího kontrolního běhu.
- Při `enabled = false` se nespouští žádné nové běhy.

