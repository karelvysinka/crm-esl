# Changelog

Všechny významné změny v tomto projektu jsou dokumentovány v tomto souboru.
Formát vychází z [Keep a Changelog](https://keepachangelog.com/) a verze dodržují [Semantic Versioning](https://semver.org/lang/cz/).

## [Unreleased]
### Přidáno
- Panel KPI statistik na stránce příležitostí (win rate, průměrná hodnota, otevřené, prohrané, vyhrané, celková hodnota).
- Submenu Objednávky s novou stránkou nastavení synchronizace (interval, URL, přihlašovací údaje, log běhů, KPI běhů).
- Automatický job `AutoSyncOrdersJob` s dynamickým intervalem dle nastavení.
- Dokumentace `07-orders/sync-settings.md` přidána do MkDocs navigace.
### Opraveno
- Guard a fallback view pro stránku nastavení synchronizace objednávek při chybějících migracích.

## [0.1.0] - 2025-09-07
### Přidáno
- Nové KPI metriky na CRM dashboardu (objednávky: dnes, týden, měsíc, rok; průměrné hodnoty, rychlost uzavírání, míry dokončení a včasnosti, stock rate aj.).
- Sekční strukturování dashboardu (Objednávky, Základní CRM Entity, Obchod & Pipeline, Realizace & Výkon).
- Lokalizovaný pozdrav v horní liště s českým datem.
- Inline SVG ikona pro položku "Obchody" v levém menu.
- Globální unifikace stylů: černá typografie, jednotné rámečky karet, odstranění šedých textů.

### Změněno
- Navigační struktura: "CRM Moduly" → "CRM", "Nástroje" → "Znalosti".
- Přesuny v menu: Projekty a Úkoly do sekce "Řízení projektů"; Objednávky a Produkty do sekce "Ecommerce".
- Vizuální vylepšení dashboardu (větší ikony, hover efekty, jasnější popisky KPI).
- Zvětšená loga v topbaru i sidebaru.

### Opraveno
- Chyby v dotazech: použit správný sloupec `stage` pro příležitosti a `availability_code` pro produkty.
- Parse error v topbaru (přepsání inline `@php` na blokovou formu).
- Odstraněn duplicitní user panel v sidebaru.

### Odstraněno
- Lokální duplicitní CSS v `crm/dashboard.blade.php` (nahrazeno globálními styly).

---
