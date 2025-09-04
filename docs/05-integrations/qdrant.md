# Integrace: Qdrant

Vektorový vyhledávací backend pro znalostní bázi.

## Účel
Umožňuje semantické vyhledávání přes embeddings dokumentů (notes, files) pro interní znalostní asistenci.

## Komponenty
- `QdrantClient` – HTTP wrapper (kolekce, upsert, search, count, deleteByFilter)
- `EmbeddingsService` – abstrakce nad poskytovateli embeddingů (OpenAI / Gemini / OpenRouter / Local)
- `IndexKnowledgeDocumentJob` (odhad dle názvu) – indexace obsahu

## Konfigurace (`config/qdrant.php`)
| Klíč | Význam |
|------|--------|
| enabled | Globální přepínač |
| url | Základní URL Qdrant API |
| api_key | API klíč pokud vyžadováno |
| collection | Název kolekce |
| embeddings.provider | openai / gemini / openrouter / local |
| embeddings.model | Model jména dle provideru |
| embeddings.dimension | Volitelné přepsání dimenze |

## Embedding Providers
| Provider | Volání | Poznámka |
|----------|--------|----------|
| openai | POST /v1/embeddings | Vyžaduje OPENAI_API_KEY |
| gemini | POST generativelanguage.googleapis.com ...:embedContent | Rate limit odlišný |
| openrouter | POST /api/v1/embeddings | Normalizace modelu s vendor prefixem |
| local | POST /embeddings (internal service) | Normalizace vectoru (normalize=true) |

## Failure Modes
| Scénář | Detekce | Chování |
|--------|---------|---------|
| Embedding provider bez klíče | Vráceno null | Dokument přeskočen / log debug |
| Timeout HTTP | Http::timeout() výjimka | Vráceno null → vynechaný embedding |
| Chybná kolekce (neexistuje) | ensureCollection false | Recreate nebo log warning |
| Upsert selže | !ok odpověď | Log warning + pokračování |

## Bezpečnost
- API klíče držet v env / SystemSetting.
- Omezit přístup Qdrant jen na interní síť (docker network `crm-internal`).

## Rozšíření
- Přidat batch upsert s retry.
- Přidat metriky (počet vektorů, latency).
