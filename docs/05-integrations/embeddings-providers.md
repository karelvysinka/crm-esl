# Embeddings Providers

Shrnutí podporovaných poskytovatelů v `EmbeddingsService`.

| Provider | Auth | Endpoint | Omezení | Poznámky |
|----------|------|----------|---------|----------|
| openai | Bearer token | https://api.openai.com/v1/embeddings | Délka input ~8k tokenů | Standardní JSON formát (data[0].embedding) |
| gemini | API key v URL param | https://generativelanguage.googleapis.com/...:embedContent | Limit textu ~8k | Struktura embedding.embedding.values |
| openrouter | Bearer token + volitelné hlavičky | https://openrouter.ai/api/v1/embeddings | Model název s vendor prefixem | OpenAI-kompatibilní struktura |
| local | žádný (interní) | http://embedder:8080/embeddings | Podle vlastní služby | Vrací pole vectors[0] |

## Runtime přepínače (SystemSetting klíče)
| Klíč | Význam | Fallback |
|------|--------|----------|
| embeddings.provider | Aktivní provider | config/qdrant.php |
| embeddings.model | Název modelu | config |
| embeddings.openai_api_key | API klíč | env OPENAI_API_KEY |
| embeddings.gemini_api_key | API klíč | env GOOGLE_GEMINI_API_KEY |
| embeddings.openrouter_api_key | API klíč | env OPENROUTER_API_KEY |
| embeddings.local_url | URL lokální služby | env EMBEDDINGS_LOCAL_URL |
| embeddings.dimension | Fixní dimenze | config / inference |

## Chování při chybě
- Chybějící klíč → metoda vrací `null`.
- HTTP ne-200 → `null`.
- Nevalidní JSON / struktura → `null` (volající rozhodne o fallbacku nebo skipu).

## Doporučení
- Uchovat dimenzi embeddingů konzistentní po celou dobu (změna vyžaduje reindex kolekce).
- Logovat míru selhání embedding fetch (proměnné metriky budoucí rozšíření).
