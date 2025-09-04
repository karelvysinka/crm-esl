#!/usr/bin/env python3
import sys, json, csv, re
from pathlib import Path
import pandas as pd

SRC = Path('/root/crm.esl.cz/data_import/data_firmy_import.xlsx')
OUT = Path(__file__).resolve().parents[3] / 'storage/app/import_reports'
ETL = Path(__file__).resolve().parents[0]
OUT_DATA = ETL / 'out'
OUT_DATA.mkdir(parents=True, exist_ok=True)

EMAIL_RE = re.compile(r"^[^@\s]+@[^@\s]+\.[^@\s]+$")
PHONE_RE = re.compile(r"[+0-9][0-9 \-()]{6,}")

def norm_phone(v:str|float|int|None):
    if v is None:
        return None
    s = str(v).strip()
    s = re.sub(r"[^0-9+]", "", s)
    return s or None

def valid_email(v):
    if not v: return False
    return bool(EMAIL_RE.match(str(v).strip()))

def find_email_from_row(r: dict) -> str | None:
    # Prefer known columns, then scan the row for the first valid email-looking value
    preferred_cols = [
        'eshop - mail', 'eshop- mail', 'eshop_mail',
        'e-mail', 'E-mail', 'Email', 'email', 'mail', 'Mail', 'e-mail 1', 'E-mail 1', 'email 1', 'Email 1',
        'e-mail 2', 'E-mail 2', 'email 2', 'Email 2', 'kontakt e-mail', 'Kontakt e-mail', 'Kontakt email',
    ]
    for col in preferred_cols:
        if col in r and valid_email(r.get(col, '')):
            return str(r.get(col)).strip()
    # fallback scan: iterate through all columns
    for k, v in r.items():
        s = str(v).strip()
        if valid_email(s):
            return s
    return None


def main():
    df = pd.read_excel(SRC, sheet_name='Zdroj-Kontakty')
    df = df.fillna('')
    contacts = []
    companies = {}
    custom_fields = []
    tags = []

    for _, r in df.iterrows():
        legacy_id = str(r.get('Číslo', '')).strip() or None
        first = str(r.get('Jméno', '')).strip()
        last = str(r.get('Příjmení', '')).strip()
        full_name = (first + ' ' + last).strip()
        company_name = str(r.get('Název', '')).strip() or None
        email = find_email_from_row(r)
        phone = (
            norm_phone(r.get('Spojení'))
            or norm_phone(r.get('Spojení.1'))
            or norm_phone(r.get('Spojení.2'))
        )
        eshop_reg = str(r.get('Je registrován na e-shopu?', '')).strip()
        eshop_reg_bool = '1' if eshop_reg.lower() in ['ano','yes','y','true','1'] else '0'
        activity = str(r.get('Druh činnosti - prodej', '')).strip()

        if company_name:
            companies[company_name] = {'name': company_name}

        contacts.append({
            'legacy_external_id': legacy_id or '',
            'first_name': first or (full_name if full_name else ''),
            'last_name': last or '',
            'email': email or '',
            'phone': phone or '',
            'company_name': company_name or '',
        })

        if eshop_reg:
            custom_fields.append({'legacy_external_id': legacy_id or '', 'key': 'eshop_registered', 'value': eshop_reg_bool})
        if activity:
            tags.append({'legacy_external_id': legacy_id or '', 'tag': activity})

    # Write CSVs
    for name, rows, headers in [
        ('companies.csv', [{'name': n} for n in companies.keys()], ['name']),
        ('contacts.csv', contacts, ['legacy_external_id','first_name','last_name','email','phone','company_name']),
        ('contact_custom_fields.csv', custom_fields, ['legacy_external_id','key','value']),
        ('contact_tags.csv', tags, ['legacy_external_id','tag']),
    ]:
        with open(OUT_DATA / name, 'w', newline='') as f:
            w = csv.DictWriter(f, fieldnames=headers)
            w.writeheader()
            w.writerows(rows)

    print(json.dumps({'ok': True, 'out_dir': str(OUT_DATA)}))

if __name__ == '__main__':
    main()
