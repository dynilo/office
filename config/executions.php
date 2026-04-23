<?php

return [
    'retry' => [
        'max_retries' => (int) env('EXECUTION_MAX_RETRIES', 2),
        'backoff_seconds' => [60, 300, 900],
    ],
];
