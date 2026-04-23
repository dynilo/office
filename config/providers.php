<?php

return [
    'default' => env('LLM_PROVIDER', 'openai_compatible'),

    'openai_compatible' => [
        'base_url' => env('OPENAI_COMPATIBLE_BASE_URL', 'https://api.openai.com/v1'),
        'api_key' => env('OPENAI_API_KEY'),
        'organization' => env('OPENAI_ORGANIZATION'),
        'project' => env('OPENAI_PROJECT'),
        'model' => env('OPENAI_MODEL', 'gpt-5'),
        'timeout' => (int) env('OPENAI_TIMEOUT_SECONDS', 30),
        'retry_times' => (int) env('OPENAI_RETRY_TIMES', 2),
        'retry_sleep_ms' => (int) env('OPENAI_RETRY_SLEEP_MS', 200),
        'store' => env('OPENAI_STORE', false),
    ],

    'embeddings' => [
        'default' => env('EMBEDDING_PROVIDER', 'openai_compatible'),

        'openai_compatible' => [
            'base_url' => env('OPENAI_COMPATIBLE_BASE_URL', 'https://api.openai.com/v1'),
            'api_key' => env('OPENAI_API_KEY'),
            'organization' => env('OPENAI_ORGANIZATION'),
            'project' => env('OPENAI_PROJECT'),
            'model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
            'timeout' => (int) env('OPENAI_EMBEDDING_TIMEOUT_SECONDS', env('OPENAI_TIMEOUT_SECONDS', 30)),
            'retry_times' => (int) env('OPENAI_EMBEDDING_RETRY_TIMES', env('OPENAI_RETRY_TIMES', 2)),
            'retry_sleep_ms' => (int) env('OPENAI_EMBEDDING_RETRY_SLEEP_MS', env('OPENAI_RETRY_SLEEP_MS', 200)),
        ],

        'null' => [
            'model' => 'null-embedding-generator',
        ],
    ],
];
