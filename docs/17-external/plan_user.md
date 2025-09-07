# Plán pro import uživatelů z ESL Kontakty do CRM

Tento dokument popisuje postup, jak automatizovaně vytvořit uživatelské účty v CRM na základě seznamu kontaktů v souboru `esl-kontakty.md`, vygenerovat jedinečná hesla pro každého uživatele a zpětně je zanést do původního seznamu.

---

## 1. Extrakce dat ze souboru esl-kontakty.md
1. Načíst soubor `esl-kontakty.md` a separsovat seznam kontaktů (jméno, email, oddělení, pozice).
2. Vytvořit interní strukturu (např. pole asociativních záznamů) pro další zpracování.

## 2. Generování náhodných hesel
1. Pro každý záznam vygenerovat silné heslo (např. 12–16 znaků, kombinace písmen, číslic a speciálních znaků).
2. Hesla udržet v paměti jako mapu uživatel → heslo.

## 3. Příprava importního CSV
1. Vytvořit CSV soubor `esl-users-import.csv` s hlavičkami:
   - `name` (zřetězené jméno)
   - `email`
   - `password`
   - další sloupce podle potřeby (např. oddělení, pozice)
2. Naplnit řádky daty včetně vygenerovaných hesel.

## 4. Hromadný import uživatelů do CRM
1. Použít Laravel příkaz (`php artisan users:import esl-users-import.csv`) nebo vlastní skript:
   - Načítá CSV a pro každý řádek zavolá Eloquent model `User::create([...])`.
   - Heslo se uloží přímo (model cast ho zahashuje).
2. Ověřit výpisem nebo logy, že všichni uživatelé byli úspěšně vytvořeni.

## 5. Aktualizace souboru esl-kontakty.md
1. Po úspěšném importu otevřít `esl-kontakty.md`.
2. Ke každé položce kontaktu přidat řádek:
   ```
   - Heslo: <vygenerované heslo>
   ```
3. Uložit změny a commitnout do repozitáře.

## 6. Rozeslání uvítácích e-mailů
1. Připravit šablonu e-mailu s přihlašovacími údaji (login, výchozí heslo, odkaz na změnu hesla).
2. Rozeslat každému novému uživateli na jeho e‑mail.
3. Vyzvat uživatele ke změně hesla při prvním přihlášení.

## 7. Validace a úklid
1. Zkontrolovat, že všichni uživatelé mají funkční přístup.
2. Ujistit se, že hesla v `esl-kontakty.md` odpovídají skutečně vygenerovaným hodnotám.
3. Odstranit dočasné soubory (CSV, skripty) po dokončení.

---

*Datum vytvoření: 7. září 2025*
