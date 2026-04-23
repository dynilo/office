<?php

return [
    'default' => [
        'version' => env('PROMPT_VERSION', '2026-04-23.v1'),
        'template_strategy' => env('PROMPT_TEMPLATE_STRATEGY', 'agent-role-template'),
        'schema_version' => env('PROMPT_SCHEMA_VERSION', '1'),
    ],
];
