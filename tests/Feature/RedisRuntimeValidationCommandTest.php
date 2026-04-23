<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redis;

it('exposes live redis runtime validation through a console command', function (): void {
    config()->set('app.env', 'production');
    config()->set('queue.default', 'redis');
    config()->set('queue.connections.redis.driver', 'redis');
    config()->set('queue.connections.redis.connection', 'default');
    config()->set('queue.connections.redis.queue', 'default');
    config()->set('queue.connections.redis.retry_after', 90);
    config()->set('queue.connections.redis.block_for', 5);
    config()->set('queue.runtime.execution_queue', 'executions');
    config()->set('queue.runtime.execution_tries', 3);
    config()->set('cache.default', 'database');
    config()->set('broadcasting.default', 'log');
    config()->set('database.redis.default.host', '127.0.0.1');

    $connection = new class
    {
        public function command(): string
        {
            return 'PONG';
        }
    };

    Redis::shouldReceive('connection')->once()->with('default')->andReturn($connection);

    expect(Artisan::call('redis:validate-runtime'))->toBe(0);

    $output = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);

    expect($output['ready'])->toBeTrue()
        ->and($output['paths']['queue']['ready'])->toBeTrue()
        ->and($output['paths']['broadcast']['fallback_safe'])->toBeTrue();
});

it('documents the redis runtime validation command and fallback modes', function (): void {
    $document = File::get(base_path('docs/REDIS_QUEUE_PRODUCTION.md'));

    expect($document)->toContain('php artisan redis:validate-runtime')
        ->toContain('safe fallback')
        ->toContain('log` and `null`');
});
