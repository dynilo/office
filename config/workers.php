<?php

$workerBackoff = array_values(array_filter(array_map(
    static fn (string $value): int => (int) trim($value),
    explode(',', env('WORKER_BACKOFF_SECONDS', env('RUNTIME_EXECUTION_BACKOFF_SECONDS', '5,30,120'))),
), static fn (int $value): bool => $value >= 0));

return [
    /*
    |--------------------------------------------------------------------------
    | Runtime Worker Supervision
    |--------------------------------------------------------------------------
    |
    | These values define the queue worker process template used by host-level
    | process managers. The application does not assume systemd, Supervisor,
    | containers, Horizon, or any other specific supervisor in this slice.
    |
    */

    'execution' => [
        'name' => env('WORKER_EXECUTION_NAME', 'ai-office-execution-worker'),
        'connection' => env('WORKER_QUEUE_CONNECTION', env('RUNTIME_EXECUTION_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'redis'))),
        'queue' => env('WORKER_QUEUE', env('RUNTIME_EXECUTION_QUEUE', 'executions')),
        'tries' => (int) env('WORKER_TRIES', env('RUNTIME_EXECUTION_TRIES', 3)),
        'backoff' => $workerBackoff,
        'sleep' => (int) env('WORKER_SLEEP_SECONDS', 3),
        'timeout' => (int) env('WORKER_TIMEOUT_SECONDS', 60),
        'max_jobs' => (int) env('WORKER_MAX_JOBS', 500),
        'max_time' => (int) env('WORKER_MAX_TIME_SECONDS', 3600),
        'memory' => (int) env('WORKER_MEMORY_MB', 256),
        'stop_when_empty' => (bool) env('WORKER_STOP_WHEN_EMPTY', false),
    ],

    'validation' => [
        'supervisor_driver' => env('WORKER_SUPERVISION_DRIVER', 'none'),
        'require_in_production' => env('WORKER_REQUIRE_SUPERVISION_IN_PRODUCTION', false),
        'status_output_path' => env('WORKER_SUPERVISOR_STATUS_PATH'),
        'process_snapshot_path' => env('WORKER_PROCESS_SNAPSHOT_PATH'),
    ],
];
