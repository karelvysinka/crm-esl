#!/usr/bin/env python3
import sys
import os
import json
from collections import Counter

try:
    import pandas as pd
except Exception as e:
    print(json.dumps({"ok": False, "error": f"Missing pandas: {e}"}))
    sys.exit(0)

INPUT_PATH = "/root/crm.esl.cz/data_import/data_firmy_import.xlsx"
REPORT_DIR = "/root/crm.esl.cz/src/storage/app/import_reports"


def summarize_df(df, sheet_name):
    info = {
        "sheet": sheet_name,
        "rows": int(df.shape[0]),
        "cols": int(df.shape[1]),
        "columns": [],
    }
    for col in df.columns:
        s = df[col]
        non_null = int(s.notna().sum())
        nulls = int(s.isna().sum())
        unique = int(s.nunique(dropna=True))
        sample = s.dropna().astype(str).head(5).tolist()
        dtype = str(s.dtype)
        info["columns"].append({
            "name": str(col),
            "dtype": dtype,
            "non_null": non_null,
            "nulls": nulls,
            "unique": unique,
            "sample_values": sample,
        })
    return info


def main():
    os.makedirs(REPORT_DIR, exist_ok=True)
    if not os.path.exists(INPUT_PATH):
        print(json.dumps({"ok": False, "error": f"File not found: {INPUT_PATH}"}))
        return

    xls = pd.ExcelFile(INPUT_PATH)
    report = {"ok": True, "sheets": [], "path": INPUT_PATH}

    for sheet in xls.sheet_names:
        try:
            df = xls.parse(sheet)
            report["sheets"].append(summarize_df(df, sheet))
        except Exception as e:
            report["sheets"].append({"sheet": sheet, "error": str(e)})

    out_json = os.path.join(REPORT_DIR, "excel_profile.json")
    with open(out_json, "w", encoding="utf-8") as f:
        json.dump(report, f, ensure_ascii=False, indent=2)

    # Minimal markdown summary
    out_md = os.path.join(REPORT_DIR, "excel_profile.md")
    with open(out_md, "w", encoding="utf-8") as f:
        f.write(f"# Excel Profiling — {os.path.basename(INPUT_PATH)}\n\n")
        for s in report["sheets"]:
            if "error" in s:
                f.write(f"## {s['sheet']} — ERROR: {s['error']}\n\n")
                continue
            f.write(f"## {s['sheet']} (rows: {s['rows']}, cols: {s['cols']})\n\n")
            for c in s["columns"]:
                f.write(f"- {c['name']} | dtype={c['dtype']} | non_null={c['non_null']} | unique={c['unique']}\n")
            f.write("\n")

    print(json.dumps({"ok": True, "json": out_json, "md": out_md}))


if __name__ == "__main__":
    main()
