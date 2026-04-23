<?php

return [
    'enabled' => (bool) env('OBSERVABILITY_ENABLED', true),
    'log_channel' => env('OBSERVABILITY_LOG_CHANNEL', env('LOG_CHANNEL', 'stack')),
    'metrics' => [
        'enabled' => (bool) env('OBSERVABILITY_METRICS_ENABLED', true),
    ],
    'tracing' => [
        'enabled' => (bool) env('OBSERVABILITY_TRACING_ENABLED', true),
    ],
];
