# MkDocs Build & Publikace

Tato sekce popisuje jak lokálně i v CI postavit a publikovat dokumentaci.

## Cíle
- Konzistentní reprodukovatelný build.
- Generované artefakty (statický web) dostupné v `public/crm-docs`.
- Možnost částečné regenerace (artisan `docs:refresh`) vs. plný MkDocs build.

## Složky & Zdroj
| Cesta | Účel |
|-------|------|
| `docs/` | Zdrojové markdown + generované podsložky `16-generated/` |
| `public/crm-docs/` | Výstup statického webu (build) |
| `mkdocs.yml` | Konfigurace navigace, theme, pluginy |
| `scripts/docs/build.sh` (pokud existuje) | Wrapper pro CI (volitelně) |

## Lokální Build
1. Vytvoř a aktivuj Python virtualenv (volitelné):
   ```bash
   python3 -m venv .venv
   . .venv/bin/activate
   pip install -U pip
   pip install mkdocs mkdocs-material
   ```
2. Regeneruj dynamické sekce (routy, env, atd.) přes Laravel command:
   ```bash
   php artisan docs:refresh
   ```
3. Spusť build:
   ```bash
   mkdocs build --clean
   ```
4. Zkopíruj nebo nasměruj výstup (`site/`) do `public/crm-docs` pokud není konfigurováno přímo (aktuálně výstup jde implicitně do `site/`; nasazení může používat symlink nebo kopii).

## Rychlý Náhled
```bash
mkdocs serve -a 0.0.0.0:8001
```
Otevři `http://localhost:8001`.

## Integrace s Artisan `docs:refresh`
- `php artisan docs:refresh` generuje obsah do `docs/16-generated/*.md`.
- Poté musí následovat MkDocs build, aby se změny propsaly do statického webu.

## CI Pipeline (příklad)
```yaml
name: Docs
on:
  push:
    paths:
      - 'docs/**'
      - 'mkdocs.yml'
      - 'app/Console/Commands/DocsRefresh.php'
jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: PHP deps
        run: |
          composer install --no-interaction --prefer-dist
          php artisan docs:refresh
      - name: Python deps
        run: |
          python -m pip install --upgrade pip
          pip install mkdocs mkdocs-material
      - name: Build docs
        run: mkdocs build --clean
      - name: Deploy (Pages / artifact)
        if: github.ref == 'refs/heads/main'
        run: |
          # např. rsync /site/ na server nebo upload artefaktu
```

## Aktualizace Cache / Assetů
- Pokud upravíš JS/CSS v `public/crm-docs/assets`, přidej verzi (query param) do `mkdocs.yml` (`extra_javascript`).

## Troubleshooting
| Problém | Příčina | Řešení |
|---------|---------|--------|
| Chybí generované soubory v navigaci | Nebyl spuštěn `php artisan docs:refresh` | Spusť command před buildem |
| Staré routy (obsahuje odstraněné middleware) | Neproběhl re-build MkDocs po změně | Spusť `mkdocs build --clean` |
| 404 na assets po deploy | Nesoulad cesty `site_url` vs. reverse proxy prefix | Ověř `site_url` v `mkdocs.yml` |
| Duplicitní položky v nav | Ruční edit + generátor přidal znovu | Slouč nebo uprav `mkdocs.yml` |

## Doporučení
- Commituj i generované `16-generated` markdowny (auditní stopa).
- V produkci servíruj statickou složku s dlouhými cache headers + hashované assety MkDocs.
- Při větších úpravách přidej položku do changelogu (`DOCS`).

## Plánované Rozšíření
- Automatická validace odkazů (plugin `linkcheck`).
- Generování ERD pomocí PlantUML (CI krok).
