# ADR-20250904: Adopce MkDocs + Material pro interní dokumentaci
Status: Accepted

## Kontext
Potřeba udržovat konzistentní, fulltextově prohledávatelnou dokumentaci. Původně pouze Markdown soubory bez strukturované navigace.

## Rozhodnutí
Použít MkDocs + Material theme. Uchovat zdroj v repozitáři (Markdown), generovat statický web (CI / Docker). Generované referenční soubory v `docs/16-generated`.

## Alternativy
- Docusaurus: těžší stack (React), nadbytečné pro čistě textové + několik diagramů.
- GitBook SaaS: vendor lock-in, méně kontroly nad CI.
- Jigsaw (PHP): menší ekosystém pluginů.

## Důsledky
Pozitivní: rychlá navigace, verzování, minimální runtime náklady (statika). Negativní: nutný build krok, závislost na Python image.

## Implementace
1. Přidán `mkdocs.yml`, struktura `docs/`.
2. Příkaz `docs:refresh` generuje routy, schedule, jobs, env matrix.
3. Build skript `scripts/docs/build.sh` pro Docker pipeline.

## Follow-up
- Přidat generátor ERD.
- Zprovoznit CI job publikace.
- Doplnit ADR pro Ops modul a Knowledge architekturu.
