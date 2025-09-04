# CRM Dokumentace

> Tato stránka obsahuje okamžitý návod jak dokumentaci správně udržovat a také připravený AI prompt pro kontextové dotazy / generování.

## Jak udržovat dokumentaci

Principy:
1. Single Source of Truth: Generované reference v `docs/16-generated` NIKDY ručně needitovat (úpravy pouze přes kód / generátor v `DocsRefresh` nebo meta soubory).
2. Viditelnost Diffů: Každá změna modelů / rout / jobů → vždy `php artisan docs:refresh` před commitem (CI failne pokud chybí diff commit).
3. Popisy Menu: Texty sekcí a položek v CRM menu udržovat v `docs/_meta/menu.php` (generátor vytvoří tabulky v `navigation.md`).
4. Strukturované Rozhodnutí: Každé významné arch / procesní rozhodnutí → přidat ADR do `docs/12-adr` (použij šablonu `15-templates/adr-template.md`).
5. Modulová Dokumentace: Každý nový modul má vlastní soubor v `docs/04-modules` + doplnění do `mkdocs.yml` + případně sekci v "Jak CRM funguje".
6. Generované Artefakty: Přidání nového typu generovaného výstupu → rozšířit třídu `app/Console/Commands/DocsRefresh.php` a přidat do nav v `mkdocs.yml`.
7. Review Cadence: Minimálně 1× týdně projít (a) počet výskytů `_(popis chybí)_` v `navigation.md`, (b) zastaralé ADR (doplnit superseded), (c) změny schématu vs. DB Schema (gen).
8. Minimální Metadata Modelů: Při přidání modelu doplnit `$fillable`, `$casts` a stručný PHPDoc (první řádek se promítne v job katalogu / budoucích generátorech).
9. Alerting & Observability: Při přidání nové ops aktivity logovat přes `OpsActivity` pro budoucí metriky.
10. AI Ready: Preferovat krátké, deklarativní odstavce před dlouhými esejemi – usnadňuje extrakci kontextu pro AI.

Workflow (rychlý check‑list před PR):
- [ ] Změny kódu ovlivňují routy / joby / schedule / modely?
	- Ano → spustit `php artisan docs:refresh`.
- [ ] Přibyly nové menu položky? → aktualizovat `docs/_meta/menu.php`.
- [ ] Nové rozhodnutí? → ADR.
- [ ] Nový modul / integrace? → modulový soubor + `.env.example` proměnné + sekce v How it Works.
- [ ] Build statického webu (volitelné lokálně) `bash scripts/docs/build.sh`.

Nejčastější chyby & prevence:
| Chyba | Prevence |
|-------|----------|
| Chybějící commit generovaných souborů | Spustit `docs:refresh` před push (CI detekuje) |
| Rozbitá navigace (mkdocs) | Po editaci `mkdocs.yml` rychle spustit lokální build |
| Nezdokumentovaná nová route | Přidat popis do `menu.php` (jinak _(popis chybí)_) |
| Zapomenuté ADR | Před merge zkontrolovat difflog / PR konverzaci |

Základní adresářové role:
| Cesta | Účel |
|-------|------|
| `docs/01-intro` | Vysvětlující vstupní přehled (How it works) |
| `docs/02-architecture` | Architektonické diagramy / přehledy |
| `docs/03-domain` | Doménový model, ERD reference odkazy |
| `docs/04-modules` | Modulové dokumenty (ruční) |
| `docs/12-adr` | Architecture Decision Records |
| `docs/13-runbooks` | Operativní runbooky |
| `docs/14-checklists` | Kontrolní seznamy |
| `docs/15-templates` | Šablony (ADR, modul) |
| `docs/16-generated` | Auto generované reference |
| `docs/_meta` | Konfig / mapování pro generátory |

## AI Prompt (kopíruj & vlož do nástroje)

Použij tento prompt pro AI agenta, aby efektivně doplňoval / aktualizoval dokumentaci.

```
SYSTEM CONTEXT:
Jsi technický dokumentační asistent pro Laravel CRM projekt. Dodržuj existující strukturu MkDocs.

REPO DOKUMENTAČNÍ ZDROJE (priority):
1. How it Works: docs/01-intro/how-it-works.md (business & flow context)
2. Domain Model + ERD + Model Fields: docs/03-domain/*.md + docs/16-generated/erd.md + model-fields.md + db-schema.md
3. Menu / Navigace: docs/16-generated/navigation.md + meta popisy v docs/_meta/menu.php
4. Ops / Runbooks: docs/04-modules/ops.md + docs/13-runbooks/ops-runbook.md
5. ADR: docs/12-adr (rozhodnutí – respektuj, pokud nejsou superseded)
6. Generované reference: docs/16-generated (NEedituj ručně)

POŽADAVKY NA TVORBU / ÚPRAVY:
- Pokud změna vyžaduje generovaný soubor, navrhni úpravu kódu (DocsRefresh) místo ruční editace.
- Stručné věcné formulace, první odstavec každé nové sekce = summary pro embedding.
- Každý nový modul: soubor v docs/04-modules + aktualizace mkdocs.yml + případný záznam v How it Works.
- Nové rozhodnutí: vytvoř ADR dle šablony docs/15-templates/adr-template.md.
- U menu položek: pokud přidáváš nebo rozšiřuješ route, doplň popis do docs/_meta/menu.php.

KONTROLY PŘED HOTOVO:
1. Je potřeba spustit `php artisan docs:refresh`? (routy / modely / joby / schedule změny)
2. Byl upraven `mkdocs.yml` pokud přibyly ruční stránky?
3. Zůstaly někde placeholdery "(popis chybí)" které lze doplnit?
4. Potřebuje změna ADR (nové nebo superseded)?

VÝSTUP:
- Seznam přesných souborů k vytvoření/úpravě.
- Stručné shrnutí proč.
- Návrh patchů (diff styl) NEobsahující generované 16-generated*.md soubory.

Bezpečnost: Neunikat tajné hodnoty z .env ani credentials.
```

---

Profesní interní dokumentace generovaná z Markdown + automatických výpisů.

## Rychlý start
1. `php artisan docs:refresh` – regenerace referencí.
2. `bash scripts/docs/build.sh` – build HTML (vyžaduje Docker).
3. Otevři `site/index.html` nebo publikované `/crm-docs/`.

## Sekce (stav)
- Architektura – skeleton.
- Moduly – Ops hotovo; další TBD.
- Runtime – generované (routy, schedule, jobs, env matrix).
- Runbook – udržován v `docs/13-runbooks`.

## Konvence
- Složka `docs/16-generated` = auto output (commitovat). 
- Ručně psané soubory: modulové, ADR, architektura.

## Příkazy
| Účel | Příkaz |
|------|--------|
| Regenerace | `php artisan docs:refresh` |
| Build HTML | `bash scripts/docs/build.sh` |

## Další kroky
- Doplnit modul ActiveCampaign, Knowledge.
- Zavést ADR první sady.

