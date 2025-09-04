<?php

return [
    'enabled' => env('QDRANT_ENABLED', false),
    'url' => env('QDRANT_URL', 'http://qdrant:6333'),
    'api_key' => env('QDRANT_API_KEY'),
    'collection' => env('QDRANT_COLLECTION', 'crm_knowledge'),
    'timeout' => (int) env('QDRANT_TIMEOUT', 10),
    // Embeddings
    'embeddings' => [
    // provider: openai|gemini|openrouter|local
    'provider' => env('EMBEDDINGS_PROVIDER', 'openai'),
        'model' => env('EMBEDDINGS_MODEL', 'text-embedding-3-small'),
        'openai_api_key' => env('OPENAI_API_KEY'),
        'gemini_api_key' => env('GOOGLE_GEMINI_API_KEY'),
    // OpenRouter headers are recommended: HTTP-Referer and X-Title
    'openrouter_api_key' => env('OPENROUTER_API_KEY'),
    'openrouter_referer' => env('OPENROUTER_SITE_URL'),
    'openrouter_title' => env('OPENROUTER_APP_NAME', 'CRM ESL'),
    // Local embedder URL (internal)
    'local_url' => env('EMBEDDINGS_LOCAL_URL', 'http://embedder:8080'),
    // Optional explicit dimension override; if null we infer from first embedding
    'dimension' => env('EMBEDDINGS_DIMENSION') ? (int) env('EMBEDDINGS_DIMENSION') : null,
    ],
];
