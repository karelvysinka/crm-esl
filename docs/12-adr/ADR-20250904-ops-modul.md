# ADR-20250904: Ops modul strategie záloh
Status: Draft

## Kontext
Potřeba standardizovat zálohování (DB dump + snapshot uploadů + verify restore) s auditní stopou a dashboardem.

## Rozhodnutí (návrh)
Centralizovat orchestrace do Ops modulu (Jobs + UI + tabulka `ops_activities`) místo skriptů mimo aplikaci.

## Alternativy
- Externí Cron + shell skripty (nižší integrace, obtížné auditování)
- Specializovaný backup SaaS (náklad, vendor lock)

## Důsledky
+ Integrace s permission modelem, audit log.
+ Snadné rozšíření o alerty.
- Aplikace musí mít přístup k DB & storage a nástrojům (mysql, restic).

## Implementace kroky
1. Jobs: RunDbBackupJob, RunStorageSnapshotJob, RunVerifyRestoreJob.
2. Audit persist do `ops_activities`.
3. Cron schedule + EvaluateBackupHealthJob.
4. UI dashboard + kontextová nápověda.

## Otevřené otázky
- Git tagging sidecar zůstává neimplementováno.
- Verify restore frekvence vs. velikost DB.

## Follow-up
- Přidat metriky Prometheus endpoint.
- ADR update po přijetí.
