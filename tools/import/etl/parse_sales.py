#!/usr/bin/env python3
import sys, json, csv, re
from pathlib import Path
import pandas as pd
from datetime import datetime

SRC = Path('/root/crm.esl.cz/data_import/data_firmy_import.xlsx')
OUT = Path(__file__).resolve().parents[0] / 'out'
OUT.mkdir(parents=True, exist_ok=True)

def norm_date(v):
    if pd.isna(v) or v == '':
        return ''
    # pandas often yields Timestamp
    try:
        return pd.to_datetime(v).date().isoformat()
    except Exception:
        try:
            return datetime.strptime(str(v).strip(), '%d.%m.%Y').date().isoformat()
        except Exception:
            return ''

def safe_float(v):
    try:
        if v in (None, ''):
            return 0.0
        return float(str(v).replace(',', '.'))
    except Exception:
        return 0.0

def norm_space(s: str) -> str:
    return re.sub(r"\s+", " ", s or "").strip()

TITLE_RE = re.compile(r"\b(Ing\.|Bc\.|Mgr\.|MgA\.|Ph\.D\.|PhD\.|JUDr\.|RNDr\.|MUDr\.|MDDr\.|BcA\.|DiS\.|MBA|LL\.M\.|doc\.|prof\.|PhDr\.|ThDr\.|ThLic\.|Dr\.|DrSc\.)\b", re.IGNORECASE)

def strip_titles(n: str) -> str:
    s = norm_space(n)
    s = TITLE_RE.sub("", s)
    s = norm_space(s)
    return s

def norm_name(n: str) -> str:
    return norm_space(strip_titles(n)).lower()

EMAIL_RE = re.compile(r"^[^@\s]+@[^@\s]+\.[^@\s]+$")

def valid_email(v):
    if not v:
        return False
    return bool(EMAIL_RE.match(str(v).strip()))

def find_email_from_row(row: dict) -> str:
    # Try common columns first, then scan all string values
    preferred_cols = [
        'Kontakt e-mail', 'Kontakt email', 'kontakt e-mail', 'kontakt email',
        'E-mail', 'Email', 'email', 'mail', 'Mail',
    ]
    for col in preferred_cols:
        if col in row and valid_email(row.get(col, '')):
            return str(row.get(col)).strip()
    for k, v in row.items():
        s = str(v).strip()
        if valid_email(s):
            return s
    return ''

def main():
    df = pd.read_excel(SRC, sheet_name='Zdroj-Prodeje').fillna('')

    # Build contact lookup from Zdroj-Kontakty: (company, name)-> email/legacy
    cdf = pd.read_excel(SRC, sheet_name='Zdroj-Kontakty').fillna('')
    contact_map = {}
    for _, r in cdf.iterrows():
        company = norm_space(str(r.get('Název', '')).strip())
        first = norm_space(str(r.get('Jméno', '')).strip())
        last = norm_space(str(r.get('Příjmení', '')).strip())
        pre = norm_space(str(r.get('Titul před jménem', '')).strip())
        post = norm_space(str(r.get('Titul za jménem', '')).strip())
        email = norm_space(str(r.get('eshop - mail', '')).strip()).lower()
        legacy = norm_space(str(r.get('Číslo', '')).strip())
        if not company:
            continue
        # Construct variants of full name
        base1 = norm_space((first + ' ' + last).strip())
        base2 = norm_space((last + ' ' + first).strip())
        with_titles1 = norm_space((pre + ' ' + base1 + ' ' + post).strip())
        with_titles2 = norm_space((pre + ' ' + base2 + ' ' + post).strip())
        for nm in {base1, base2, with_titles1, with_titles2}:
            key = (company.lower(), norm_name(nm))
            if key not in contact_map and (email or legacy):
                contact_map[key] = {'email': email if email else '', 'legacy': legacy if legacy else ''}

    orders = {}
    items = []

    for _, r in df.iterrows():
        # Map to real columns: 'Číslo zakázky' is the order number, 'Název' company, 'Kontakt. osoba' contact
        ext = str(r.get('Číslo zakázky', '')).strip()
        if not ext or ext.lower() in {'nan','null','none','0'}:
            continue
        comp = norm_space(str(r.get('Název', '')).strip())
        cont_raw = norm_space(str(r.get('Kontakt. osoba', '')).strip())
        cont_norm = norm_name(cont_raw)
        # Lookup contact email/legacy by company + name
        c_email = ''
        c_legacy = ''
        if comp and cont_norm:
            hit = contact_map.get((comp.lower(), cont_norm))
            if hit:
                c_email = hit.get('email', '')
                c_legacy = hit.get('legacy', '')
        # fallback: detect email directly from the sales row if mapping failed
        if not c_email:
            direct = find_email_from_row(r)
            if direct:
                c_email = direct
        ord_date = norm_date(r.get('Datum případu', ''))
        author = str(r.get('Autor', '')).strip()
        source = 'helios'
        note = ''

        if ext not in orders:
            orders[ext] = {
                'external_order_no': ext,
                'company_name': comp,
                'contact_name': cont_raw,
                'contact_email': c_email,
                'contact_legacy_id': c_legacy,
                'order_date': ord_date,
                'source': source,
                'author': author,
                'notes': note,
                'total_amount': 0.0,
                'currency': 'CZK',
            }

        qty = safe_float(r.get('Množství', 1))
        unit_price = safe_float(r.get('JC bez daní', 0))
        unit_price_disc = safe_float(r.get('JC bez daní po slevě', 0))
        cc_total_disc = safe_float(r.get('CC bez daní po slevě', 0))
        discount_card = safe_float(r.get('Slevy na kartu', 0))
        discount_group = safe_float(r.get('Sleva ke skupině karet', 0))

        # Determine line total: prefer provided total, else qty * discounted unit price, else qty * unit price
        line_total = cc_total_disc if cc_total_disc > 0 else (qty * (unit_price_disc if unit_price_disc > 0 else unit_price))

        item = {
            'external_order_no': ext,
            'sku': '',
            'alt_code': str(r.get('Doplňkový kód', '')).strip(),
            'name': str(r.get('Název 1', '')).strip(),
            'qty': qty,
            'unit_price': unit_price,
            'discount_percent': discount_card + discount_group,
            'line_total': line_total,
            'product_group_code': str(r.get('SK', '')).strip(),
            'eshop_category_url': '',
            'vat_percent': '',
        }
        items.append(item)
        orders[ext]['total_amount'] += line_total

    # write orders
    with open(OUT / 'orders.csv', 'w', newline='') as f:
        fields = ['external_order_no','company_name','contact_name','contact_email','contact_legacy_id','order_date','source','author','notes','total_amount','currency']
        w = csv.DictWriter(f, fieldnames=fields)
        w.writeheader()
        w.writerows(orders.values())

    # write items
    with open(OUT / 'order_items.csv', 'w', newline='') as f:
        fields = ['external_order_no','sku','alt_code','name','qty','unit_price','discount_percent','line_total','product_group_code','eshop_category_url','vat_percent']
        w = csv.DictWriter(f, fieldnames=fields)
        w.writeheader()
        w.writerows(items)

    print(json.dumps({'ok': True, 'orders': str(OUT / 'orders.csv'), 'items': str(OUT / 'order_items.csv')}))

if __name__ == '__main__':
    main()
