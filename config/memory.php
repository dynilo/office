<?php

return [
    'pgvector' => [
        'dimensions' => (int) env('MEMORY_EMBEDDING_DIMENSIONS', 1536),
        'distance' => env('MEMORY_VECTOR_DISTANCE', 'cosine'),
    ],
];
