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

## Lokální Build (Docker – preferováno)
Používáme oficiální image Material MkDocs pro konzistentní výsledek (viz `scripts/build-docs.sh`).

Pinned base image: `squidfunk/mkdocs-material:9.5.18` (měň pouze vědomě + changelog poznámka).
Link checking se spouští jen v CI pomocí separátního konfigu `mkdocs-linkcheck.yml` – lokální build je rychlý (bez pluginu). To zabraňuje problémům s lokální instalací pluginů uvnitř kontejneru.

```bash
./scripts/build-docs.sh
```

Co skript dělá:
1. Spustí `php artisan docs:refresh` (ignoruje chyby, aby build nepadl kvůli částečnému selhání).
2. Spustí `docker run squidfunk/mkdocs-material:<verze> build --strict --site-dir public/crm-docs`.
3. Výstup jde přímo do `public/crm-docs` (nepoužíváme výchozí `site/`).

## Alternativa (lokální Python prostředí)
Pouze pokud Docker není k dispozici:
```bash
python3 -m venv .venv_docs
. .venv_docs/bin/activate
pip install -U pip
pip install mkdocs mkdocs-material pymdown-extensions
php artisan docs:refresh
mkdocs build --clean --site-dir public/crm-docs
```
Pozn: `--site-dir public/crm-docs` zarovná výstup s produkční strukturou.

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
        run: mkdocs build --clean --site-dir public/crm-docs
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
- Commituj generované `16-generated` markdowny (auditní stopa + reproducibilita).
- Commituj také `public/crm-docs` (instantní publikace bez dalšího build kroku na serveru).
- Při větších úpravách přidej položku do changelogu (`DOCS`).
- Udržuj verzi Docker image v synchronu s dokumentací (případně pin na minor).

## Link Checking (pouze CI)
Používáme separátní konfigurační soubor:

```
mkdocs-linkcheck.yml (INHERIT: mkdocs.yml + plugin linkcheck)
```

Pipeline provede:
```bash
pip install mkdocs-linkcheck
mkdocs build -f mkdocs-linkcheck.yml --strict
```
Lokálně plugin není vyžadován (rychlejší iterace). Chyby odkazů se objeví pouze v CI.

## Plánované Rozšíření
- Automatická validace odkazů (plugin `linkcheck`).
- Generování ERD pomocí PlantUML (CI krok).
