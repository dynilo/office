<?php

return [
    'chunking' => [
        'max_characters' => (int) env('KNOWLEDGE_CHUNK_MAX_CHARACTERS', 500),
    ],
];
