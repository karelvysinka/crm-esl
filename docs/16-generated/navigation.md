# Navigace (generováno)

_Automatický přehled položek CRM menu (GET routy s prefixem `/crm`). Popisy lze doplnit v `docs/_meta/menu.php`._

## Dashboard

Výchozí přehled (widgety, stav synchronizací, klíčové KPI).

| Cesta | Route name | Popis |
|-------|------------|-------|
| `/` | crm.dashboard | Hlavní přehled a rychlé akce. |

## Firmy

Evidence firem (profil, revenue, průmysl) – vstup do detailu vztahů.

| Cesta | Route name | Popis |
|-------|------------|-------|
| `/companies` | companies.index | _(popis chybí)_ |
| `/companies/create` | companies.create | _(popis chybí)_ |
| `/companies/{company}` | companies.show | _(popis chybí)_ |
| `/companies/{company}/edit` | companies.edit | _(popis chybí)_ |

## Kontakty

Evidence osob navázaných na firmy, segmentace a marketing status.

| Cesta | Route name | Popis |
|-------|------------|-------|
| `/contacts` | contacts.index | _(popis chybí)_ |
| `/contacts/create` | contacts.create | _(popis chybí)_ |
| `/contacts/{contact}` | contacts.show | _(popis chybí)_ |
| `/contacts/{contact}/edit` | contacts.edit | _(popis chybí)_ |

## Leady

Potenciální příležitosti před kvalifikací (kanban/pipeline varianty).

| Cesta | Route name | Popis |
|-------|------------|-------|
| `/leads` | leads.index | _(popis chybí)_ |
| `/leads/create` | leads.create | _(popis chybí)_ |
| `/leads/{lead}` | leads.show | _(popis chybí)_ |
| `/leads/{lead}/edit` | leads.edit | _(popis chybí)_ |

## Příležitosti

Obchodní příležitosti s fázemi, pravděpodobností a predikcí closingu.

| Cesta | Route name | Popis |
|-------|------------|-------|
| `/opportunities` | opportunities.index | _(popis chybí)_ |
| `/opportunities/create` | opportunities.create | _(popis chybí)_ |
| `/opportunities/{opportunity}` | opportunities.show | _(popis chybí)_ |
| `/opportunities/{opportunity}/edit` | opportunities.edit | _(popis chybí)_ |

## Úkoly

Operativní práce navázaná na entity (polymorfní taskable).

| Cesta | Route name | Popis |
|-------|------------|-------|
| `/tasks` | tasks.index | _(popis chybí)_ |
| `/tasks/create` | tasks.create | _(popis chybí)_ |
| `/tasks/{task}` | tasks.show | _(popis chybí)_ |
| `/tasks/{task}/edit` | tasks.edit | _(popis chybí)_ |

## Projekty

Dlouhodobější iniciativy agregující úkoly a obchodní kontext.

| Cesta | Route name | Popis |
|-------|------------|-------|
| `/projects` | projects.index | _(popis chybí)_ |
| `/projects/create` | projects.create | _(popis chybí)_ |
| `/projects/{project}` | projects.show | _(popis chybí)_ |
| `/projects/{project}/edit` | projects.edit | _(popis chybí)_ |

## Knowledge Base

Znalostní báze (dokumenty, chunking, vektorové vyhledávání).

| Cesta | Route name | Popis |
|-------|------------|-------|
| `/knowledge` | knowledge.index | _(popis chybí)_ |
| `/knowledge/create` | knowledge.create | _(popis chybí)_ |
| `/knowledge/search/ajax` | knowledge.search | _(popis chybí)_ |
| `/knowledge/{knowledge}/edit` | knowledge.edit | _(popis chybí)_ |

## Marketing

Strategie, exekuce, analytika, nastavení marketingu.

| Cesta | Route name | Popis |
|-------|------------|-------|
| `/marketing` | marketing.dashboard | _(popis chybí)_ |
| `/marketing/analytika/ab-testovani` | marketing.analytics.ab | _(popis chybí)_ |
| `/marketing/analytika/ai-sentiment` | marketing.analytics.sentiment | _(popis chybí)_ |
| `/marketing/analytika/atribuce` | marketing.analytics.attribution | _(popis chybí)_ |
| `/marketing/analytika/cross-channel` | marketing.analytics.cross | _(popis chybí)_ |
| `/marketing/analytika/seo` | marketing.analytics.seo | _(popis chybí)_ |
| `/marketing/cileni/databaze-kontaktu` | marketing.target.contacts | _(popis chybí)_ |
| `/marketing/cileni/lead-nurturing` | marketing.target.nurturing | _(popis chybí)_ |
| `/marketing/cileni/segmentace` | marketing.target.segments | _(popis chybí)_ |
| `/marketing/exekuce/automatizace` | marketing.exec.automation | _(popis chybí)_ |
| `/marketing/exekuce/email-marketing` | marketing.exec.email | _(popis chybí)_ |
| `/marketing/exekuce/influenceri-partneri` | marketing.exec.influencers | _(popis chybí)_ |
| `/marketing/exekuce/kampane` | marketing.exec.campaigns | _(popis chybí)_ |
| `/marketing/exekuce/knihovna-obsahu` | marketing.exec.content | _(popis chybí)_ |
| `/marketing/exekuce/landing-pages` | marketing.exec.landing | _(popis chybí)_ |
| `/marketing/exekuce/reklamy` | marketing.exec.ads | _(popis chybí)_ |
| `/marketing/nastaveni/ai-sablony` | marketing.settings.ai | _(popis chybí)_ |
| `/marketing/nastaveni/importy` | settings.imports | _(popis chybí)_ |
| `/marketing/nastaveni/integrace-api` | marketing.settings.integrations | _(popis chybí)_ |
| `/marketing/nastaveni/lead-scoring` | marketing.settings.scoring | _(popis chybí)_ |
| `/marketing/nastaveni/role-prava` | marketing.settings.roles | _(popis chybí)_ |
| `/marketing/strategie/budget` | marketing.strategy.budget | _(popis chybí)_ |
| `/marketing/strategie/kalendar` | marketing.strategy.calendar | _(popis chybí)_ |
| `/marketing/strategie/persony` | marketing.strategy.personas | _(popis chybí)_ |
| `/marketing/strategie/swot` | marketing.strategy.swot | _(popis chybí)_ |
| `/marketing/strategie/trendy-ai` | marketing.strategy.trends | _(popis chybí)_ |

## Chat

Konverzační AI / uživatelské relace.

| Cesta | Route name | Popis |
|-------|------------|-------|
| `/chat` | crm.chat | _(popis chybí)_ |
| `/chat/sessions` |  | _(popis chybí)_ |
| `/chat/sessions/{id}/messages` |  | _(popis chybí)_ |
| `/chat/stream` |  | _(popis chybí)_ |

## System

Admin konfigurace (ActiveCampaign, Backup, Qdrant, Tools, Chat, integrace).

| Cesta | Route name | Popis |
|-------|------------|-------|
| `/system/activecampaign` | system.ac.index | _(popis chybí)_ |
| `/system/activecampaign/runs` | system.ac.runs | _(popis chybí)_ |
| `/system/apps` | system.apps.index | _(popis chybí)_ |
| `/system/backup` | system.backup.index | _(popis chybí)_ |
| `/system/backup/download/{path}` | system.backup.download | _(popis chybí)_ |
| `/system/chat` | system.chat.index | _(popis chybí)_ |
| `/system/chat/diagnostics` | system.chat.diagnostics | _(popis chybí)_ |
| `/system/chat/lookup` | system.chat.lookup | _(popis chybí)_ |
| `/system/qdrant` | system.qdrant.index | _(popis chybí)_ |
| `/system/tools` | system.tools.index | _(popis chybí)_ |

## Deals

| Cesta | Route name | Popis |
|-------|------------|-------|
| `/deals` | deals.index | _(popis chybí)_ |
| `/deals/create` | deals.create | _(popis chybí)_ |
| `/deals/{deal}` | deals.show | _(popis chybí)_ |
| `/deals/{deal}/edit` | deals.edit | _(popis chybí)_ |

## Knowledge docs

| Cesta | Route name | Popis |
|-------|------------|-------|
| `/knowledge-docs` | knowledge.docs.index | _(popis chybí)_ |
| `/knowledge-docs/create` | knowledge.docs.create | _(popis chybí)_ |

## Leads kanban

| Cesta | Route name | Popis |
|-------|------------|-------|
| `/leads-kanban` | leads.kanban | _(popis chybí)_ |

## Opportunities pipeline

| Cesta | Route name | Popis |
|-------|------------|-------|
| `/opportunities-pipeline` | opportunities.pipeline | _(popis chybí)_ |

## Ops

| Cesta | Route name | Popis |
|-------|------------|-------|
| `/ops` | ops.dashboard | _(popis chybí)_ |
| `/ops/history` | ops.history.index | _(popis chybí)_ |
| `/ops/metrics` | ops.metrics | _(popis chybí)_ |

## Orders

| Cesta | Route name | Popis |
|-------|------------|-------|
| `/orders/{order}/items` |  | _(popis chybí)_ |

## Products

| Cesta | Route name | Popis |
|-------|------------|-------|
| `/products` | products.index | _(popis chybí)_ |
| `/products/{product}` | products.show | _(popis chybí)_ |

## Search

| Cesta | Route name | Popis |
|-------|------------|-------|
| `/search/customers` | search.customers | _(popis chybí)_ |
| `/search/taskables` | search.taskables | _(popis chybí)_ |

