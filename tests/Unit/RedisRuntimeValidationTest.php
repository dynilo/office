<?php

use App\Support\Redis\RedisRuntimeValidation;
use Illuminate\Support\Facades\Redis;

it('reports successful live redis validation for queue cache and redis broadcast paths', function (): void {
    config()->set('app.env', 'production');
    config()->set('queue.default', 'redis');
    config()->set('queue.connections.redis.driver', 'redis');
    config()->set('queue.connections.redis.connection', 'default');
    config()->set('queue.connections.redis.queue', 'default');
    config()->set('queue.connections.redis.retry_after', 90);
    config()->set('queue.connections.redis.block_for', 5);
    config()->set('queue.runtime.execution_queue', 'executions');
    config()->set('queue.runtime.execution_tries', 3);
    config()->set('cache.default', 'redis');
    config()->set('cache.stores.redis.driver', 'redis');
    config()->set('cache.stores.redis.connection', 'cache');
    config()->set('cache.stores.redis.lock_connection', 'default');
    config()->set('broadcasting.default', 'redis');
    config()->set('broadcasting.connections.redis.driver', 'redis');
    config()->set('broadcasting.connections.redis.connection', 'default');
    config()->set('database.redis.default.host', '127.0.0.1');
    config()->set('database.redis.default.database', '0');
    config()->set('database.redis.cache.host', '127.0.0.1');
    config()->set('database.redis.cache.database', '1');

    $defaultConnection = new class
    {
        public function command(string $command): string
        {
            expect($command)->toBe('ping');

            return 'PONG';
        }
    };

    $cacheConnection = new class
    {
        public function command(string $command): bool
        {
            expect($command)->toBe('ping');

            return true;
        }
    };

    Redis::shouldReceive('connection')->once()->with('default')->andReturn($defaultConnection);
    Redis::shouldReceive('connection')->once()->with('cache')->andReturn($cacheConnection);

    $report = app(RedisRuntimeValidation::class)->report();

    expect($report['ready'])->toBeTrue()
        ->and($report['paths']['queue']['ready'])->toBeTrue()
        ->and($report['paths']['cache']['ready'])->toBeTrue()
        ->and($report['paths']['broadcast']['ready'])->toBeTrue()
        ->and($report['connections']['default']['ready'])->toBeTrue()
        ->and($report['connections']['cache']['ready'])->toBeTrue();
});

it('treats log broadcast and non redis cache stores as safe fallbacks when redis is unavailable', function (): void {
    config()->set('app.env', 'local');
    config()->set('queue.default', 'sync');
    config()->set('queue.connections.sync.driver', 'sync');
    config()->set('queue.production.enforce_redis', true);
    config()->set('cache.default', 'database');
    config()->set('cache.stores.database.driver', 'database');
    config()->set('broadcasting.default', 'log');
    config()->set('broadcasting.connections.log.driver', 'log');

    Redis::shouldReceive('connection')->never();

    $report = app(RedisRuntimeValidation::class)->report();

    expect($report['ready'])->toBeTrue()
        ->and($report['paths']['queue']['fallback_safe'])->toBeTrue()
        ->and($report['paths']['cache']['fallback_safe'])->toBeTrue()
        ->and($report['paths']['broadcast']['fallback_safe'])->toBeTrue()
        ->and($report['connections'])->toBe([]);
});

it('fails safely with redacted connection errors when a required redis path cannot connect', function (): void {
    config()->set('app.env', 'production');
    config()->set('queue.default', 'redis');
    config()->set('queue.connections.redis.driver', 'redis');
    config()->set('queue.connections.redis.connection', 'default');
    config()->set('queue.connections.redis.queue', 'default');
    config()->set('queue.connections.redis.retry_after', 90);
    config()->set('queue.connections.redis.block_for', 5);
    config()->set('queue.runtime.execution_queue', 'executions');
    config()->set('queue.runtime.execution_tries', 3);
    config()->set('cache.default', 'redis');
    config()->set('cache.stores.redis.driver', 'redis');
    config()->set('cache.stores.redis.connection', 'cache');
    config()->set('cache.stores.redis.lock_connection', 'default');
    config()->set('broadcasting.default', 'log');
    config()->set('database.redis.default.host', '127.0.0.1');
    config()->set('database.redis.default.password', 'super-secret-redis-password');
    config()->set('database.redis.cache.host', '127.0.0.1');

    Redis::shouldReceive('connection')
        ->once()
        ->with('default')
        ->andThrow(new RuntimeException('redis auth failed for super-secret-redis-password'));
    Redis::shouldReceive('connection')
        ->once()
        ->with('cache')
        ->andThrow(new RuntimeException('redis auth failed for super-secret-redis-password'));

    $report = app(RedisRuntimeValidation::class)->report();

    expect($report['ready'])->toBeFalse()
        ->and($report['paths']['queue']['ready'])->toBeFalse()
        ->and($report['paths']['queue']['unavailable_reason'])->toBe('connection_default_unavailable')
        ->and($report['paths']['cache']['ready'])->toBeFalse()
        ->and($report['paths']['broadcast']['ready'])->toBeTrue()
        ->and($report['connections']['default']['connection_error'])->toContain('[REDACTED]')
        ->and($report['connections']['default']['connection_error'])->not->toContain('super-secret-redis-password');
});
