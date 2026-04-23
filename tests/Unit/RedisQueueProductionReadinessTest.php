<?php

use App\Support\Queue\RedisQueueProductionReadiness;

it('reports redis queue readiness from configuration without connecting to redis', function (): void {
    config()->set('app.env', 'production');
    config()->set('queue.default', 'redis');
    config()->set('queue.connections.redis.driver', 'redis');
    config()->set('queue.connections.redis.connection', 'default');
    config()->set('queue.connections.redis.queue', 'default');
    config()->set('queue.connections.redis.retry_after', 90);
    config()->set('queue.connections.redis.block_for', 5);
    config()->set('database.redis.default.host', '127.0.0.1');
    config()->set('queue.runtime.execution_queue', 'executions');
    config()->set('queue.runtime.execution_tries', 3);

    $report = app(RedisQueueProductionReadiness::class)->report();

    expect($report['ready'])->toBeTrue()
        ->and($report['default_connection'])->toBe('redis')
        ->and($report['redis']['queue'])->toBe('default')
        ->and($report['redis']['block_for'])->toBe(5)
        ->and($report['runtime']['execution_queue'])->toBe('executions');
});

it('fails fast in production when redis is enforced but queue default is not redis', function (): void {
    config()->set('app.env', 'production');
    config()->set('queue.default', 'sync');
    config()->set('queue.connections.sync.driver', 'sync');
    config()->set('queue.production.enforce_redis', true);

    expect(fn () => app(RedisQueueProductionReadiness::class)->assertProductionSafe())
        ->toThrow(RuntimeException::class, 'default_queue_is_redis');
});

it('allows sync queues outside production as a safe test fallback', function (): void {
    config()->set('app.env', 'testing');
    config()->set('queue.default', 'sync');
    config()->set('queue.connections.sync.driver', 'sync');

    app(RedisQueueProductionReadiness::class)->assertProductionSafe();

    expect(true)->toBeTrue();
});

it('marks redis queue readiness incomplete when blocking pop is required but disabled', function (): void {
    config()->set('app.env', 'production');
    config()->set('queue.default', 'redis');
    config()->set('queue.connections.redis.driver', 'redis');
    config()->set('queue.connections.redis.connection', 'default');
    config()->set('queue.connections.redis.queue', 'default');
    config()->set('queue.connections.redis.retry_after', 90);
    config()->set('queue.connections.redis.block_for', null);
    config()->set('database.redis.default.host', '127.0.0.1');
    config()->set('queue.production.require_blocking_pop', true);

    $report = app(RedisQueueProductionReadiness::class)->report();

    expect($report['ready'])->toBeFalse()
        ->and($report['unavailable_reason'])->toBe('redis_block_for_configured');
});
