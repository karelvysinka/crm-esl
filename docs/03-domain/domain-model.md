# Doménový Model (Inicialní Skeleton)

 Tento dokument je doplněn: [ERD (generováno)](../16-generated/erd.md), [Model Fields](../16-generated/model-fields.md) a [DB Schema (generováno)](../16-generated/db-schema.md). Níže jsou shrnuté popisy doménových entit.

## Entity (z `app/Models`)

| Model | Popis |
|-------|-------|
| Company | Firma / účet; drží základní profil (odvětví, velikost, revenue) a agreguje kontakty, příležitosti, projekty a objednávky. |
| Contact | Osoba spojená s firmou; obsahuje normalizaci emailu/telefonu a marketing status. |
| ContactCustomField | Dynamické (klíč, hodnota, typ) rozšíření kontaktu + mapování na AC field. |
| ContactIdentity | Externí identity kontaktu (zdroj, external_id/hash) pro deduplikaci. |
| Deal | Dohoda navázaná na Opportunity (kontrakt / finální fáze) se stavem podpisu. |
| KnowledgeChunk | Jeden vektorový chunk dokumentu (text + embedding + metadata + qdrant_point_id). |
| KnowledgeDocument | Zdrojový dokument pro znalostní bázi (stav indexace, embedding meta, počty chunků). |
| KnowledgeNote | Ručně psaná poznámka uživatele s tagy (ne vždy vektorizováno). |
| Lead | (TBD – soubor není doplněn / zřejmě budoucí fáze) |
| Opportunity | Obchodní příležitost s fázemi (stage, probability, expected_close_date). |
| OpsActivity | Záznam o provozní aktivitě (backup, verifikace, atd.) včetně časového průběhu. |
| ProductGroup | (TBD – strom kategorií / produktových skupin – chybí model ve výpisu find) |
| Project | Projekt (časové období / definovaný cíl) navázaný na firmu/kontakt, agreguje úkoly. |
| SalesOrder | Prodejní objednávka (order_date, total_amount) navázaná na firmu a kontakt. |
| SalesOrderItem | Položka objednávky (ceny, slevy, skupiny) – decimal přesnost zachována pro analýzu. |
| SystemSetting | Runtime nastavení (key/value) s helpery `get` a `set`. |
| Tag | Štítky primárně pro kontakty (pivot contact_tag). |
| Task | Úkol přiřaditelný k polymorfní entitě (Company/Contact/Project) + priority/status. |
| User | Autentizovaný uživatel (is_admin flag). |
| AcSyncRun | Záznam běhu AC synchronizace (offset, counts, sample IDs, message). |
| AppLink | Konfigurovatelné odkazy v aplikaci (UI launch bord). |
| ChatToolAudit | Audit záznam interakce AI nástroje (payload + result_meta + duration). |

## Vztahy (výběr)
- Company 1:N Contact / Opportunity / Project / SalesOrder
- Contact 1:N Opportunity / Project / SalesOrder; M:N Tag
- Opportunity 1:1 Deal (logická návaznost; není cizí klíč v ukázce – ověřit při ERD)
- KnowledgeDocument 1:N KnowledgeChunk
- SalesOrder 1:N SalesOrderItem
- Task morphTo (Company|Contact|Project) + přímé project_id (dual linkage)
- AcSyncRun izolovaná tabulka logů (žádné FK) – navázání pouze logické přes čas

## Domain Rules (počáteční)
- Contact: normalizace emailu & telefonu při `saving` – odfiltrování placeholderů.
- Opportunity: fáze `won|lost` definují uzavřený stav (scopes `open/closed/won/lost`).
- SalesOrderItem: decimal přesnost pro ceny a slevy (finanční analýza; neagregovat v aplikační vrstvě s floaty).
- SystemSetting: jedinečnost klíče vynucena přes `updateOrCreate`.
- Knowledge* entity: embedding dimenze musí být konzistentní (při změně → reindex all).

## Plán doplnění
1. Vygenerovat ERD → `docs/16-generated/erd.svg` (script add: php artisan docs:erd nebo external tool – pending).
2. Sloupcové tabulky – hotovo (viz DB Schema (gen), INFORMATION_SCHEMA introspekce).
3. Přidat sekci "Lifecycle Events" (boot hooks: Contact normalizace).
4. Přidat business SLA metriky (lead → opportunity conversion, opportunity cycle time).
