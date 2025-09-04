#!/usr/bin/env python3
import sys, json, csv
from pathlib import Path
import pandas as pd

SRC = Path('/root/crm.esl.cz/data_import/data_firmy_import.xlsx')
OUT = Path(__file__).resolve().parents[0] / 'out'
OUT.mkdir(parents=True, exist_ok=True)

def main():
    df = pd.read_excel(SRC, sheet_name='Zdroj-Skupiny zboží')
    df = df.fillna('')
    rows = []
    for _, r in df.iterrows():
        rows.append({
            'code': str(r.get('Skupina karet', '')).strip(),
            'name': str(r.get('Název', '')).strip(),
            'eshop_url': str(r.get('URL kategorie na e-shopu', '')).strip(),
        })
    with open(OUT / 'product_groups.csv', 'w', newline='') as f:
        w = csv.DictWriter(f, fieldnames=['code','name','eshop_url'])
        w.writeheader()
        w.writerows(rows)
    print(json.dumps({'ok': True, 'file': str(OUT / 'product_groups.csv')}))

if __name__ == '__main__':
    main()
