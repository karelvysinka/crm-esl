# Jak CRM funguje (Business & Technický Přehled)

Tento přehled propojuje obchodní procesy s implementovanými moduly. Slouží jako vstupní bod pro nové členy týmu.

## 1. Základní Doménové Toky

| Tok | Start | Kroky (zkráceně) | Konec / Artefakt |
|-----|-------|------------------|------------------|
| Lead → Opportunity → Deal | Lead (import / formulář) | Kvalifikace → konverze na Opportunity → Pipeline pohyb | Deal (uzavření Won/Lost) |
| Company & Contact Enrichment | Company / Contact vytvoření | Normalizace + identita + AC sync doplnění | Aktualizovaný profil + segmentace |
| Sales Order Lifecycle | Opportunity (Won) | Ruční / automat. založení SalesOrder + položky | Fakturační / reporting výstupy |
| Task Management | Ruční / systémový trigger | Přiřazení (user), status změny, dokončení | Aktivita v historii + SLA metriky |
| Knowledge Ingestion | Upload / Note | Chunking → Embedding → Uložení v Qdrant | Fulltext + vektorové vyhledávání |
| ActiveCampaign Sync | Cron (job) | Incremental fetch contacts → normalizace → upsert | Kontakty aktuální vůči AC |
| Backup & Verify | Naplánované joby | Záloha → Verify restore test → Report | OpsActivity + report + alerting |

## 2. Moduly a Odpovědnosti
- Companies / Contacts: Centrální jádro identit. Kontakty normalizují email/telefon a ukládají marketing status.
- Opportunities & Deals: Pipeline + pravděpodobnost + důvody uzavření (close reason) → predikce.
- Tasks & Projects: Krátkodobé vs. dlouhodobé práce. Task polymorfní (taskable) navázání na Company/Contact/Project.
- Sales Orders: Monetizace – hodnoty & položky (SalesOrderItem) – základ pro revenue reporting.
- Knowledge Base: Dokumenty → KnowledgeDocument → KnowledgeChunk (embedding + metadata) → vyhledávání.
- Ops: Zálohy, verifikace obnovy, alerty, metriky, audit (`ops_activities`).
- Integrace (ActiveCampaign, Qdrant, Embeddings): Oddělené konfig sekce, testy a udržitelné parametry.

## 3. Hlavní Automatizace
| Automatizace | Mechanika | Frekvence | Výsledek |
|--------------|----------|-----------|----------|
| ActiveCampaignSyncJob | Incremental contacts fetch (limit/offset) | 1 min | Aktualizace kontaktů |
| RunBackupJob + Verify | Spatie backup + restore test | denně | Validovaný dump |
| EvaluateBackupHealthJob | Kontrola stáří dumpů/snapshotů/verify | 10 min | Alert / Slack zpráva |
| Qdrant Reindex (manuální) | Recreate + purge | ad-hoc | Konsistentní vektorový index |

## 4. Datové Principy
- Fillable + casts = základ pro heuristické generování ERD & Model Fields.
- INFORMATION_SCHEMA generuje přesné typy / NULL / indexy (viz DB Schema).
- Normalizace (email, phone) – minimalizuje duplicity pro segmentaci.
- Embedding dimenze konzistentní (změna vyžaduje full reindex).

## 5. Navigace & UX Logika
Viz generované: Navigace (gen). Struktura menu odvozená z rout `/crm/*`. Popisy se udržují v `docs/_meta/menu.php` (single source). CI zajišťuje, aby generovaný soubor byl aktuální.

## 6. Alerting & Observability
- Prometheus endpoint: `/crm/ops/metrics` (chráněno oprávněním) – export metrik výkonu a čerstvosti.
- Slack webhook (`ALERT_SLACK_WEBHOOK`) – odeslání strukturálních alertů (STALE/FAIL/Overdue).
- Audit: `ops_activities` – datová základna pro report a historické diagnózy.

## 7. Rozšiřitelnost
| Oblast | Doporučený Pattern |
|--------|--------------------|
| Nová entita | Eloquent model + `$fillable` + migrace + doplnit doc blok (popis) |
| Nový modul | Přidat sekci v `docs/_meta/menu.php` + ADR (decision) |
| Nová integrace | Konfig klíče do `.env.example` + modulový README + health test route |
| Další metriky | Přidat gauge/counter do `MetricsController` + update ops.md |
| Extra ACL | Spatie permission seeding + permissions (gen) refresh |

## 8. Quality Gates Dokumentace
- `php artisan docs:refresh` spustit lokálně před PR.
- CI job failne, pokud `docs/16-generated` diff ≠ čisté.
- Minimální pokrytí popisů menu: doporučeno >90% (sledovat počet "_(popis chybí)_").

## 9. Budoucí Vylepšení
- Automatický screenshot vybraných UI (vizuální regresní příloha dokumentace).
- Generace sekce Analytics (konverzní poměry) → pipeline výpočet cronem.
- Mermaid sekce stavového stroje Opportunity (fáze / přechody).

---
Aktualizujte při změně obchodní logiky, nových modulech nebo změně datových pravidel.
