<?php

return [
    'pgvector' => [
        'dimensions' => (int) env('MEMORY_EMBEDDING_DIMENSIONS', 1536),
        'distance' => env('MEMORY_VECTOR_DISTANCE', 'cosine'),
        'extension' => env('PGVECTOR_EXTENSION_NAME', 'vector'),
        'require_in_production' => env('PGVECTOR_REQUIRE_IN_PRODUCTION', false),
        'index' => [
            'enabled' => env('PGVECTOR_INDEX_ENABLED', true),
            'method' => env('PGVECTOR_INDEX_METHOD', 'hnsw'),
            'name' => env('PGVECTOR_INDEX_NAME', 'knowledge_items_embedding_hnsw_idx'),
        ],
    ],
];
