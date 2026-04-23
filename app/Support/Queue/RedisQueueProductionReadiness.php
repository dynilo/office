<?php

namespace App\Support\Queue;

use RuntimeException;

final class RedisQueueProductionReadiness
{
    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        $defaultConnection = (string) config('queue.default');
        $default = config("queue.connections.{$defaultConnection}", []);
        $redis = config('queue.connections.redis', []);
        $redisConnection = (string) data_get($redis, 'connection', 'default');
        $redisDatabase = config("database.redis.{$redisConnection}", []);
        $production = config('app.env') === 'production';

        $checks = [
            'default_queue_is_redis' => $defaultConnection === 'redis' && data_get($default, 'driver') === 'redis',
            'redis_queue_connection_defined' => data_get($redis, 'driver') === 'redis',
            'redis_database_connection_defined' => is_array($redisDatabase) && $redisDatabase !== [],
            'redis_endpoint_configured' => filled(data_get($redisDatabase, 'url')) || filled(data_get($redisDatabase, 'host')),
            'redis_queue_name_configured' => filled(data_get($redis, 'queue')),
            'redis_retry_after_positive' => (int) data_get($redis, 'retry_after', 0) > 0,
            'redis_block_for_configured' => ! $this->requiresBlockingPop() || data_get($redis, 'block_for') !== null,
            'runtime_execution_queue_configured' => filled(config('queue.runtime.execution_queue')),
            'runtime_execution_tries_positive' => (int) config('queue.runtime.execution_tries', 0) > 0,
            'production_requirement_satisfied' => ! $production || ! $this->enforcesRedis() || $defaultConnection === 'redis',
        ];

        return [
            'environment' => (string) config('app.env'),
            'default_connection' => $defaultConnection,
            'default_driver' => data_get($default, 'driver'),
            'redis' => [
                'queue_connection' => $redisConnection,
                'queue' => data_get($redis, 'queue'),
                'retry_after' => data_get($redis, 'retry_after'),
                'block_for' => data_get($redis, 'block_for'),
                'after_commit' => data_get($redis, 'after_commit'),
                'host_configured' => filled(data_get($redisDatabase, 'host')) || filled(data_get($redisDatabase, 'url')),
            ],
            'runtime' => [
                'execution_connection' => config('queue.runtime.execution_connection'),
                'execution_queue' => config('queue.runtime.execution_queue'),
                'execution_tries' => config('queue.runtime.execution_tries'),
                'execution_backoff' => config('queue.runtime.execution_backoff'),
            ],
            'checks' => $checks,
            'ready' => ! in_array(false, $checks, true),
            'unavailable_reason' => $this->firstFailedCheck($checks),
        ];
    }

    public function assertProductionSafe(): void
    {
        if (config('app.env') !== 'production' || ! $this->enforcesRedis()) {
            return;
        }

        $report = $this->report();

        if ($report['ready'] === true) {
            return;
        }

        throw new RuntimeException('Redis queue production readiness failed: '.$report['unavailable_reason'].'.');
    }

    private function enforcesRedis(): bool
    {
        return (bool) config('queue.production.enforce_redis', true);
    }

    private function requiresBlockingPop(): bool
    {
        return (bool) config('queue.production.require_blocking_pop', true);
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
