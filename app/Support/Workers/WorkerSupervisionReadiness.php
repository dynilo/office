<?php

namespace App\Support\Workers;

use App\Support\Queue\RedisQueueProductionReadiness;

final readonly class WorkerSupervisionReadiness
{
    public function __construct(
        private RedisQueueProductionReadiness $redisReadiness,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        $worker = config('workers.execution', []);
        $runtimeConnection = (string) config('queue.runtime.execution_connection', '');
        $runtimeQueue = (string) config('queue.runtime.execution_queue', '');
        $redisReport = $this->redisReadiness->report();

        $checks = [
            'worker_name_configured' => filled(data_get($worker, 'name')),
            'worker_connection_configured' => filled(data_get($worker, 'connection')),
            'worker_queue_configured' => filled(data_get($worker, 'queue')),
            'worker_tries_positive' => (int) data_get($worker, 'tries', 0) > 0,
            'worker_timeout_positive' => (int) data_get($worker, 'timeout', 0) > 0,
            'worker_memory_positive' => (int) data_get($worker, 'memory', 0) > 0,
            'worker_limits_configured' => (int) data_get($worker, 'max_jobs', 0) > 0
                || (int) data_get($worker, 'max_time', 0) > 0,
            'runtime_connection_aligned' => (string) data_get($worker, 'connection') === $runtimeConnection,
            'runtime_queue_aligned' => (string) data_get($worker, 'queue') === $runtimeQueue,
            'redis_queue_readiness_known' => array_key_exists('ready', $redisReport),
        ];

        return [
            'environment' => (string) config('app.env'),
            'worker' => [
                'name' => (string) data_get($worker, 'name', ''),
                'connection' => (string) data_get($worker, 'connection', ''),
                'queue' => (string) data_get($worker, 'queue', ''),
                'tries' => (int) data_get($worker, 'tries', 0),
                'backoff' => array_values((array) data_get($worker, 'backoff', [])),
                'sleep' => (int) data_get($worker, 'sleep', 0),
                'timeout' => (int) data_get($worker, 'timeout', 0),
                'max_jobs' => (int) data_get($worker, 'max_jobs', 0),
                'max_time' => (int) data_get($worker, 'max_time', 0),
                'memory' => (int) data_get($worker, 'memory', 0),
                'stop_when_empty' => (bool) data_get($worker, 'stop_when_empty', false),
                'command' => $this->command(),
            ],
            'runtime' => [
                'execution_connection' => $runtimeConnection,
                'execution_queue' => $runtimeQueue,
            ],
            'redis' => [
                'ready' => (bool) data_get($redisReport, 'ready', false),
                'unavailable_reason' => data_get($redisReport, 'unavailable_reason'),
            ],
            'checks' => $checks,
            'ready' => ! in_array(false, $checks, true),
            'unavailable_reason' => $this->firstFailedCheck($checks),
        ];
    }

    public function command(): string
    {
        $worker = config('workers.execution', []);
        $parts = [
            'php artisan queue:work',
            (string) data_get($worker, 'connection', 'redis'),
            '--queue='.(string) data_get($worker, 'queue', 'executions'),
            '--tries='.(int) data_get($worker, 'tries', 3),
            '--sleep='.(int) data_get($worker, 'sleep', 3),
            '--timeout='.(int) data_get($worker, 'timeout', 60),
            '--max-jobs='.(int) data_get($worker, 'max_jobs', 500),
            '--max-time='.(int) data_get($worker, 'max_time', 3600),
            '--memory='.(int) data_get($worker, 'memory', 256),
        ];

        $backoff = array_values((array) data_get($worker, 'backoff', []));

        if ($backoff !== []) {
            $parts[] = '--backoff='.implode(',', $backoff);
        }

        if ((bool) data_get($worker, 'stop_when_empty', false)) {
            $parts[] = '--stop-when-empty';
        }

        return implode(' ', $parts);
    }

    /**
     * @param  array<string, bool>  $checks
     */
    private function firstFailedCheck(array $checks): ?string
    {
        foreach ($checks as $check => $passed) {
            if (! $passed) {
                return $check;
            }
        }

        return null;
    }
}
