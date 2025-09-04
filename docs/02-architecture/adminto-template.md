---
title: Adminto Šablona – Integrace a Využití
description: Jak je integrována komerční šablona Adminto (Laravel v2.0) a jak z ní bezpečně čerpat komponenty.
---

# Adminto Šablona (Laravel v2.0) – Integrace

Tento projekt využívá placenou šablonu **Adminto (Laravel 11, Bootstrap 5.3.3)** jako základ UI. Zde je praktický přehled co z ní máme, kde hledat zdrojové části a jak správně doplňovat nové UI prvky.

## Struktura zdrojů šablony

Umístění originálních souborů (nemodifikovat, slouží jako referenční zdroj):

```
/srv/projects/crm/template/Adminto-Laravel_v2.0/Adminto            # původní kód
/srv/projects/crm/template/Adminto-Laravel_v2.0/Documentation      # vendor dokumentace (HTML)
```

Adaptované části byly překopírovány a upraveny v našem app repozitáři (`resources/`, `public/`, SCSS/JS přes Vite). Původní Gulp flow jsme NEPŘEVZALI – build řeší Vite.

## Hlavní oblast převzetí

| Oblast | Stav integrace | Poznámka |
|--------|-----------------|----------|
| Layout (sidebar, topbar) | Převzato + custom úpravy | Soubor `resources/views/layouts/partials/sidebar.blade.php` obsahuje sekce CRM + zbytek demo menu. |
| Ikony (Remix, Tabler, Solar) | Připraveno | V menu sekce Icons – lze použít pro rychlou UI symboliku. |
| Komponenty UI (Base/Extended) | Základ ponechán | Demo routy zatím převážně placeholdery. |
| Form komponenty & validace | K dispozici | Využít dle potřeby – sladit s laravel form request validací. |
| Tabulky & Datatables | K dispozici | Pozor na duplicitní JS inicializace – preferuj centralizovaný modul. |
| Grafy (Apexcharts, Flot, Chart.js, atd.) | Neaktivní v doménové logice | Aktivuj pouze potřebné knihovny (tree‑shake / lazy load). |
| Mapy (Google, Vector, Leaflet) | Leaflet integrace už v závislostech | Ostatní jen pokud přidáme funkci. |
| Víceúrovňové menu | Ponecháno (demo) | Zvaž redukci pro snížení kognitivního šumu. |

## Doporučený postup při přidávání nových obrazovek

1. Navrhni doménovou URL a route name (prefix `crm.` nebo `marketing.` nebo modul).  
2. Rozšiř `sidebar.blade.php` – minimalizuj vnoření (max 2 úrovně).  
3. Vytvoř Blade view v odpovídající složce (`resources/views/…`).  
4. Pokud potřebuješ JS plugin:  
   - Zkontroluj v dokumentaci Adminto (tabulka pluginů – níže).  
   - Pokud už máme moderní ekvivalent (např. nativní Date picker / Flatpickr), preferuj existující knihovnu.  
5. Stylování: Přidej SCSS proměnné do `resources/scss/_theme-default.scss` (nenahrazuj vendor CSS ručně).  
6. Aktualizuj generovanou navigaci (`php artisan docs:refresh`) a případně popisy v `docs/_meta/menu.php`.  
7. Přidej záznam do changelogu (TYPE `ADDED` / `CHANGED`).

## Přizpůsobení (Customization) – jádro

Z dokumentace (soubor `customization.html`):

> Úprava palety barev probíhá změnou SCSS proměnných v `_theme-default.scss` (u nás: `resources/scss/`). Po úpravě spustit build Vite (`npm run dev` nebo `vite build`).

Další layout varianty (top navigation, horizontal) lze aktivovat úpravou atributu `data-layout` na `<html>` + odebráním include sidebaru a přidáním horizontální navigace (viz originální kroky). Aktuálně zůstáváme u vertikálního layoutu.

## Pluginy – mapping potřebných assetů

Z originální tabulky (zkrácený výběr běžně relevantních):

| Funkce | CSS | JS | Custom Init |
|--------|-----|----|-------------|
| Datatables | dataTables.bootstrap4.min.css … | jquery.dataTables.min.js … | datatables.init.js |
| Dropzone Upload | dropzone.min.css | dropzone.min.js | – |
| Summernote WYSIWYG | summernote-bs4.css | summernote-bs4.min.js | form-summernote.init.js |
| Quill Editor | quill.*.css | quill.min.js (+ katex.min.js) | form-quilljs.init.js |
| Range Slider | ion.rangeSlider.css | ion.rangeSlider.min.js | range-sliders.init.js |
| ApexCharts | – | apexcharts.min.js | apexcharts.init.js |
| SweetAlert2 | sweetalert2.min.css | sweetalert2.min.js | sweet-alerts.init.js |
| Select2 | select2.min.css | select2.min.js | form-advanced.init.js |
| Parsley (form validation) | – | parsley.min.js | form-validation.init.js |
| Daterangepicker | daterangepicker.css | daterangepicker.js + moment.js | form-pickers.init.js |
| Dropzone | dropzone.min.css | dropzone.min.js | – |
| Leaflet Maps | (externí) | leaflet.js | (vlastní inicializace) |

Poznámky:
* Nepřebírej všechny pluginy do globální bundle – pouze importy na stránkách, kde jsou potřeba (Vite dynamic import).
* Upřednostňuj moderní alternativy (např. nativní Date picker vs. bootstrap-datepicker) pro redukci velikosti.

## Performance & Bundling Guidelines

- Každý větší plugin lazy‑load (dynamic `import()` v komponentě).
- Sdílené utility (SweetAlert wrapper, AJAX helper) centralizuj do `resources/js/lib/`.
- Sleduj velikost produkčního bundlu (`vite build --report` – případně doplnit plugin později).

## Bezpečnost & Údržba

- Sleduj upstream verze šablony (soubor `Documentation/changelog.html`).
- Při aktualizaci: diff proti našim přepsaným Blade view – začni jen layouty a partialy.
- Změny UI knihoven vždy s changelog položkou (`CHANGED` nebo `SECURITY` pokud oprava CVE pluginu).

## Redukce Demo Balastu (Doporučeno)

Krátkodobé kandidáty k odstranění / skrytí:
- Multi Level menu (ponechat jen pokud reálná potřeba).
- Nadbytečné grafy (ponechat 1 knihovnu – preferenčně ApexCharts).
- Duplicitní form pickery (sjednotit na jednu knihovnu + Quill jako default editor).

## Roadmap Integrace

| Milestone | Cíl | Stav |
|-----------|-----|------|
| M1 | Inventarizace použitých pluginů vs. dostupných | Proběhlo částečně (tento dokument) |
| M2 | Odstranit nevyužité demosekce z menu | TODO |
| M3 | Zavedeno lazy-loading pro těžké moduly (charts, editors) | TODO |
| M4 | Standardizovaný UI kit (komponenty jako Laravel Blade partials) | TODO |
| M5 | Dokumentovaný upgrade proces na novou verzi šablony | TODO |

---
Poslední úprava: {{ date('Y-m-d') }}