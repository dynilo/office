<?php

return [
    'retry' => [
        'max_retries' => (int) env('EXECUTION_MAX_RETRIES', 2),
        'backoff_seconds' => [60, 300, 900],
    ],
    'sla' => [
        'pending_expiration_seconds' => (int) env('EXECUTION_PENDING_EXPIRATION_SECONDS', 900),
        'running_timeout_seconds' => (int) env('EXECUTION_RUNNING_TIMEOUT_SECONDS', 1800),
    ],
    'dead_letter' => [
        'capture_enabled' => (bool) env('EXECUTION_DEAD_LETTER_CAPTURE_ENABLED', true),
    ],
];
