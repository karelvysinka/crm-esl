---
title: Publikace na GitHub & Release Workflow
description: Jak připravit projekt pro bezpečné veřejné nebo privátní uložení na GitHub, včetně verzování a bezpečnostních kontrol.
---

# Publikace na GitHub & Release Workflow

Tato sekce definuje standard pro nahrání a údržbu repozitáře na GitHubu.

## 1. Cíle
- Reprodukovatelné buildy
- Auditovatelná historie (changelog, ADR)
- Ochrana tajemství (žádné klíče v git historii)
- Automatická validace (CI gates)

## 2. Příprava před prvním push
Kontrolní seznam:
1. `.gitignore` pokrývá `vendor/`, `node_modules/`, runtime a `.env` (hotovo – viz root).
2. `APP_KEY` není commitnut (jen v CI se generuje).
3. Žádné tajné tokeny ve zdrojích: grep `OPENROUTER_API_KEY`, `API_KEY`, `PASSWORD` – pouze placeholdery.
4. `docs/01-intro/changelog.md` má první verzi vX.Y.Z.
5. CI workflow `.github/workflows/ci.yml` existuje (testy, docs, changelog validace).
6. `LICENSE` (TODO pokud má být veřejné).
7. `README.md` (TODO – stručný přehled a odkaz na docs).

## 3. Inicializace repozitáře
```
git init
git add .
git commit -m "chore: initial commit"
git branch -M main
git remote add origin git@github.com:ORG/REPO.git
git push -u origin main
```

## 4. Verzování
Strategie: SemVer (MAJOR.MINOR.PATCH).
- PATCH: opravy bez dopadu na API / schéma
- MINOR: nové funkce kompatibilní
- MAJOR: nekompatibilní změny / migrační kroky

Tagging release:
```
VERSION=v0.1.1
git tag -a $VERSION -m "Release $VERSION"
git push origin $VERSION
```

## 5. Changelog Flow
1. Každý PR musí obsahovat příkaz (lokálně) `php artisan changelog:add TYPE "Shrnutí" ...`
2. CI validuje formát.
3. Při tagování se NEedituje minulá historie.

## 6. Release Checklist
| Krok | Popis | Stav |
|------|-------|------|
| Testy | `phpunit` zelené |  |
| Changelog | Nová položka přítomna |  |
| Dokumentace | `composer docs` bez diffu |  |
| Migrace | Nové migrace otestovány na staging |  |
| Bezpečnost | Žádné tajemství v diffu |  |
| Build assets | `npm run build` lokálně ověřeno |  |
| Záloha DB | Spuštěn manuální backup před deploymentem |  |

## 7. Ochrana tajemství
- Všechny runtime klíče pouze v `.env` / secret store (GitHub Actions secrets).
- Přidat později: skript `scripts/scan-secrets.sh` (TODO) + CI krok.

## 8. GitHub Actions – další doporučení
Možné budoucí kroky:
- Cache `node_modules` + build artefaktů
- Coverage report + badge
- Security scan (Trivy) pro Docker image

## 9. Rollback Strategie
- Minor release lze revertovat `git revert` merge commit.
- Před MAJOR: export DB schématu + záloha.
- Dokumentovat breaking changes v samostatné sekci changelogu.

## 10. TODO / Další rozšíření
- [ ] README doplnit
- [ ] LICENSE vybrat (MIT / proprietary)
- [ ] Secrets scanning (Gitleaks)
- [ ] Automatické generování release notes z changelogu

---
Aktualizace této stránky musí proběhnout při změně release procesu nebo bezpečnostních zásad.
