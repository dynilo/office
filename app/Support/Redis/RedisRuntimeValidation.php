<?php

namespace App\Support\Redis;

use App\Support\Queue\RedisQueueProductionReadiness;
use App\Support\Security\SecretRedactor;
use Illuminate\Support\Facades\Redis;
use Throwable;

final readonly class RedisRuntimeValidation
{
    public function __construct(
        private RedisQueueProductionReadiness $queueReadiness,
        private SecretRedactor $redactor,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        $queueReadiness = $this->queueReadiness->report();
        $paths = [
            'queue' => $this->queuePath($queueReadiness),
            'cache' => $this->cachePath(),
            'broadcast' => $this->broadcastPath(),
        ];

        $connectionNames = [];

        foreach ($paths as $path) {
            foreach ($path['required_connections'] as $connection) {
                $connectionNames[] = $connection;
            }
        }

        $connections = [];

        foreach (array_values(array_unique($connectionNames)) as $connection) {
            $connections[$connection] = $this->validateConnection($connection);
        }

        foreach ($paths as $name => $path) {
            $paths[$name] = $this->finalizePath($path, $connections);
        }

        $checks = [
            'queue_ready' => (bool) $paths['queue']['ready'],
            'cache_ready' => (bool) $paths['cache']['ready'],
            'broadcast_ready' => (bool) $paths['broadcast']['ready'],
        ];

        return [
            'environment' => (string) config('app.env'),
            'redis_client' => (string) config('database.redis.client', 'phpredis'),
            'paths' => $paths,
            'connections' => $connections,
            'checks' => $checks,
            'ready' => ! in_array(false, $checks, true),
            'unavailable_reason' => $this->firstFailedCheck($checks),
        ];
    }

    /**
     * @param  array<string, mixed>  $queueReadiness
     * @return array<string, mixed>
     */
    private function queuePath(array $queueReadiness): array
    {
        $defaultConnection = (string) config('queue.default', '');
        $driver = (string) data_get(config("queue.connections.{$defaultConnection}", []), 'driver', '');
        $usesRedis = $driver === 'redis';
        $redisConnection = $usesRedis
            ? (string) data_get(config('queue.connections.redis', []), 'connection', 'default')
            : null;
        $fallbackSafe = ! $usesRedis && (! $this->isProduction() || ! $this->enforcesRedisQueue());

        return [
            'mode' => $defaultConnection,
            'uses_redis' => $usesRedis,
            'fallback_safe' => $fallbackSafe,
            'required_connections' => $usesRedis && $redisConnection !== null ? [$redisConnection] : [],
            'config_ready' => (bool) ($queueReadiness['ready'] ?? false),
            'ready' => false,
            'unavailable_reason' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function cachePath(): array
    {
        $defaultStore = (string) config('cache.default', '');
        $store = config("cache.stores.{$defaultStore}", []);
        $usesRedis = (string) data_get($store, 'driver', '') === 'redis';
        $connections = [];

        if ($usesRedis) {
            $connections[] = (string) data_get($store, 'connection', 'cache');
            $lockConnection = (string) data_get($store, 'lock_connection', '');

            if ($lockConnection !== '') {
                $connections[] = $lockConnection;
            }
        }

        return [
            'mode' => $defaultStore,
            'uses_redis' => $usesRedis,
            'fallback_safe' => ! $usesRedis,
            'required_connections' => array_values(array_unique(array_filter($connections))),
            'config_ready' => ! $usesRedis || data_get($store, 'connection') !== null,
            'ready' => false,
            'unavailable_reason' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function broadcastPath(): array
    {
        $defaultConnection = (string) config('broadcasting.default', '');
        $connection = config("broadcasting.connections.{$defaultConnection}", []);
        $usesRedis = (string) data_get($connection, 'driver', '') === 'redis';
        $fallbackSafe = ! $usesRedis && in_array($defaultConnection, ['log', 'null'], true);

        return [
            'mode' => $defaultConnection,
            'uses_redis' => $usesRedis,
            'fallback_safe' => $fallbackSafe,
            'required_connections' => $usesRedis ? [(string) data_get($connection, 'connection', 'default')] : [],
            'config_ready' => ! $usesRedis || data_get($connection, 'connection') !== null,
            'ready' => false,
            'unavailable_reason' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $path
     * @param  array<string, array<string, mixed>>  $connections
     * @return array<string, mixed>
     */
    private function finalizePath(array $path, array $connections): array
    {
        if (! $path['uses_redis']) {
            $path['ready'] = (bool) $path['fallback_safe'];
            $path['unavailable_reason'] = $path['ready'] ? null : 'fallback_not_safe';

            return $path;
        }

        if (! $path['config_ready']) {
            $path['ready'] = false;
            $path['unavailable_reason'] = 'config_not_ready';

            return $path;
        }

        foreach ($path['required_connections'] as $connection) {
            $report = $connections[$connection] ?? null;

            if (! is_array($report) || ($report['ready'] ?? false) !== true) {
                $path['ready'] = false;
                $path['unavailable_reason'] = "connection_{$connection}_unavailable";

                return $path;
            }
        }

        $path['ready'] = true;
        $path['unavailable_reason'] = null;

        return $path;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateConnection(string $connection): array
    {
        $config = config("database.redis.{$connection}", []);
        $hostConfigured = filled(data_get($config, 'host')) || filled(data_get($config, 'url'));

        $report = [
            'name' => $connection,
            'host_configured' => $hostConfigured,
            'database' => data_get($config, 'database'),
            'ready' => false,
            'ping' => null,
            'connection_error' => null,
        ];

        if (! is_array($config) || $config === [] || ! $hostConfigured) {
            $report['connection_error'] = 'redis_endpoint_not_configured';

            return $report;
        }

        try {
            $ping = Redis::connection($connection)->command('ping');

            $report['ping'] = is_scalar($ping) || $ping === null ? $ping : json_encode($ping, JSON_THROW_ON_ERROR);
            $report['ready'] = $this->normalizePing($ping);
            $report['connection_error'] = $report['ready'] ? null : 'unexpected_ping_response';
        } catch (Throwable $exception) {
            $report['connection_error'] = $this->redactor->redactString($exception->getMessage());
        }

        return $report;
    }

    private function normalizePing(mixed $ping): bool
    {
        if ($ping === true) {
            return true;
        }

        if (is_string($ping)) {
            return in_array(strtolower(trim($ping)), ['pong', '+pong'], true);
        }

        return false;
    }

    private function isProduction(): bool
    {
        return config('app.env') === 'production';
    }

    private function enforcesRedisQueue(): bool
    {
        return (bool) config('queue.production.enforce_redis', true);
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
