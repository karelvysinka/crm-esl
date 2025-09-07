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

## 11. Krok za krokem: První privátní GitHub nasazení

Pokud nemůže asistovat automatizace, postupuj ručně podle těchto přesných kroků.

### Předpoklady
- Na serveru je projekt (už inicializovaný `git init` a první commit – hotovo).
- Máš GitHub účet a právo vytvářet repozitáře v organizaci / pod svým účtem.

### Varianta A: Push z lokální stanice (doporučeno)
1. Stáhni si projekt ze serveru (nebo vyvíjej lokálně) – ověř že složka obsahuje `.git`.
2. Na GitHubu vytvoř repozitář:
	- Web UI: `+` (vpravo nahoře) → `New repository`.
	- `Repository name`: např. `crm-esl`.
	- Visibility: `Private`.
	- Nezaškrtávej auto‐inicializační README / .gitignore (už je v projektu).
	- Vytvoř.
3. Přidej remote a push:
	```bash
	git remote add origin git@github.com:ORG_OR_USER/crm-esl.git
	git push -u origin main
	```
4. Ověř v UI, že soubory jsou online.

### Varianta B: Push přímo ze serveru (deploy key)
1. Na serveru vygeneruj dedikovaný klíč:
	```bash
	ssh-keygen -t ed25519 -C "crm-esl-deploy" -f ~/.ssh/id_crm_esl
	```
2. Přidej do `~/.ssh/config`:
	```
	Host github-crm
	  HostName github.com
	  User git
	  IdentityFile ~/.ssh/id_crm_esl
	  IdentitiesOnly yes
	```
3. Zobraz public klíč a zkopíruj:
	```bash
	cat ~/.ssh/id_crm_esl.pub
	```
4. GitHub repo → Settings → Deploy keys → Add deploy key (název např. `server-initial`) — povol *Write access* (jen pokud ze serveru budeš pushovat).
5. Přidej remote a push:
	```bash
	git remote add origin git@github-crm:ORG_OR_USER/crm-esl.git
	git push -u origin main
	```

### (Alternativa) HTTPS s PAT
Použij jen pokud SSH není možné:
```bash
git remote add origin https://github.com/ORG_OR_USER/crm-esl.git
git push -u origin main
```
Při promptu zadej Personal Access Token (PAT) místo hesla (doporuč rozsah `repo` + expiration). 

### Po prvním push – základní hardening
1. Repo → Settings → General → "Allow forking" vypnout (pokud nechceš forky).
2. Repo → Settings → Branches → Add rule `main`:
	- Require pull request before merging.
	- Require status checks (později až testy běží).
3. Repo → Settings → Actions → General → Povolit Actions pro privátní.
4. Repo → Settings → Secrets and variables → Actions → Add secrets (např. `PROD_SSH_HOST`, `PROD_SSH_USER`, atd. – dle budoucího CD).

### Tagování první verze
```bash
git tag -a v0.1.1 -m "Initial private release"
git push origin v0.1.1
```
Na GitHubu → Releases → Draft new release → vyber tag `v0.1.1`. 
Changelog poslední položku získáš:
```bash
awk '/^### \[/ {if (c++) exit} {print}' docs/01-intro/changelog.md
```
Výstup vlož do release notes (můžeš odstranit interní sekce pokud chceš kratší text pro veřejné uživatele).

### Přidání nové změny do changelogu před další verzí
```bash
php artisan changelog:add ADDED "Nový modul X" --details="Backend + UI" --impact="Uživatel: nové funkce; Provoz: žádný"
git add docs/01-intro/changelog.md
git commit -m "docs: changelog pro modul X"
```

### Rychlá kontrola že nic citlivého neuniklo
```bash
grep -R "API_KEY" -n . | head
grep -R "SECRET" -n . | head
grep -R "PASSWORD" -n . | head
```
Pokud najdeš reálné hodnoty (ne placeholdery), odeber a přidej do `.env`.

### Minimální rollback (pokud něco rozbiješ v main)
```bash
git revert <SHA-problematic-commit>
git push origin main
```
Pokud šlo o release tag, vytvoř následně novou PATCH verzi s opravou (např. v0.1.2).

### Nejčastější chyby & řešení
| Problém | Příčina | Oprava |
|---------|---------|--------|
| Permission denied (publickey) | Špatný klíč nebo není na GitHubu | Zkontroluj `ssh -T git@github.com` |
| PAT authentication failed | Špatný scope nebo expirovaný token | Vytvoř nový PAT se scope `repo` |
| CI nevidí `.env` | Chybí `.env.example` | Přítomno – doplnit nové klíče dle potřeby |
| Changelog gate selže | Nesprávný TYPE / pořadí | Spusť validator lokálně `php scripts/validate-changelog.php` |

### Co NEcommitovat nikdy
- Skutečné API klíče, hesla, privátní SSH klíče.
- Dumpy databází (použij artefakt / priv storage).
- Velké binárky (>100MB) – použij Git LFS nebo externí storage.

---
Tento návod rozšířil původní sekce – při změně procesu aktualizuj souběžně Release Checklist.
