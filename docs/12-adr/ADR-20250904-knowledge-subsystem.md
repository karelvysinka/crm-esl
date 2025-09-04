# ADR: Knowledge Subsystem

Status: Accepted
Datum: 2025-09-04

## Kontext
Potřebujeme semantické vyhledávání v interní znalostní bázi (poznámky + nahrané dokumenty). Požadavky: rychlá indexace, možnost více embedding providerů, jednoduché mazání a reindex.

## Rozhodnutí
Použijeme Qdrant jako vektorový store + vlastní `EmbeddingsService` abstrakci nad poskytovateli (OpenAI, Gemini, OpenRouter, Local). Dokument se rozseká na chunky (heuristická délka ~1k tokenů), každý chunk má embedding uložen v DB a v kolekci Qdrant. Metadata (provider, model, dimenze) ukládáme na úrovni dokumentu a chunku pro audit.

## Důsledky
+ Snadná výměna provideru bez migrace vektorů (pokud zachová dimenzi) – při změně dimenze reindex.
+ Qdrant kolekce centralizuje semantické dotazy.
- Závislost na externím komponentu (Qdrant) → monitoring nutný.
- Reindex při změně dimenze je nákladný (batch job).

## Alternativy
1. Použít pouze lokální embedding service + fulltext DB (nedostačující relevance).
2. Elastic + dense_vector pole (komplexnější správa, méně feature pro ANN varianty).

## Další kroky
- Přidat metriky (počty chunků, latency embedding providerů).
- Implementovat batch reindex command.
