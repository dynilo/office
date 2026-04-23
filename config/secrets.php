<?php

return [
    'redaction' => [
        'replacement' => '[REDACTED]',
        'key_patterns' => [
            'authorization',
            'api_key',
            'password',
            'private_key',
            'secret',
            'token',
            'openai-organization',
            'openai-project',
        ],
        'config_paths' => [
            'app.key',
            'providers.openai_compatible.api_key',
            'providers.openai_compatible.organization',
            'providers.openai_compatible.project',
            'providers.openai_compatible_secondary.api_key',
            'providers.openai_compatible_secondary.organization',
            'providers.openai_compatible_secondary.project',
            'providers.embeddings.openai_compatible.api_key',
            'providers.embeddings.openai_compatible.organization',
            'providers.embeddings.openai_compatible.project',
            'services.ses.key',
            'services.ses.secret',
            'mail.mailers.smtp.password',
            'database.connections.pgsql.password',
            'database.redis.default.password',
        ],
    ],
];
