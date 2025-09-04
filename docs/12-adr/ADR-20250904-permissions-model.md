# ADR: Permissions Model

Status: Accepted
Datum: 2025-09-04

## Kontext
Aplikace potřebuje jemnozrnná oprávnění nad operacemi (čtení/zápis entit, provozní akce). Použit je balík spatie/laravel-permission. Aktuálně neexistuje formalizovaný seznam rolí.

## Rozhodnutí
Použijeme dynamické načítání oprávnění z DB a generování přehledu (`docs:refresh` -> `permissions.md`). Role budou definovány v migracích / seed skriptech (budoucí) a dokumentace se regeneruje. Uživatelé mají navíc boolean `is_admin` pro full bypass.

## Důsledky
+ Rychlá údržba (UI / seed) bez potřeby měnit kód.
+ Dokumentace oprávnění je vždy po běhu generátoru up-to-date.
- Potřeba runtime DB při generování listu (fallback zpráva pokud není dostupné).

## Alternativy
1. Hardcodovat policy map v kódu (méně flexibilní).
2. Pouze `is_admin` + hrubé ACL (nedostačující pro audit/least-privilege).

## Další kroky
- Definovat baseline role (např. `sales_rep`, `sales_manager`, `ops_admin`).
- Přidat test ověřující že každé permission má přiřazenou alespoň jednu roli (po zavedení rolí).
