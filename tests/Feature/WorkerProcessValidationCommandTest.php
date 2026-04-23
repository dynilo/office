<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

it('exposes worker supervision and process validation through a console command', function (): void {
    config()->set('app.env', 'production');
    config()->set('queue.default', 'redis');
    config()->set('queue.connections.redis.driver', 'redis');
    config()->set('queue.connections.redis.connection', 'default');
    config()->set('queue.connections.redis.queue', 'default');
    config()->set('queue.connections.redis.retry_after', 90);
    config()->set('queue.connections.redis.block_for', 5);
    config()->set('database.redis.default.host', '127.0.0.1');
    config()->set('queue.runtime.execution_connection', 'redis');
    config()->set('queue.runtime.execution_queue', 'executions');
    config()->set('queue.runtime.execution_tries', 3);
    config()->set('workers.execution.connection', 'redis');
    config()->set('workers.execution.queue', 'executions');
    config()->set('workers.execution.tries', 3);
    config()->set('workers.execution.timeout', 60);
    config()->set('workers.execution.max_jobs', 500);
    config()->set('workers.execution.max_time', 3600);
    config()->set('workers.execution.memory', 256);
    config()->set('workers.validation.supervisor_driver', 'generic');
    config()->set('workers.validation.require_in_production', true);
    config()->set('workers.validation.process_snapshot_path', '/tmp/worker-processes.txt');

    File::shouldReceive('exists')->once()->with('/tmp/worker-processes.txt')->andReturnTrue();
    File::shouldReceive('get')->once()->with('/tmp/worker-processes.txt')->andReturn(
        'php artisan queue:work redis --queue=executions --tries=3 --sleep=3 --timeout=60 --max-jobs=500 --max-time=3600 --memory=256 --backoff=5,30,120'
    );

    expect(Artisan::call('workers:validate-runtime'))->toBe(0);

    $output = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);

    expect($output['ready'])->toBeTrue()
        ->and($output['supervision']['driver'])->toBe('generic')
        ->and($output['process']['command_observed'])->toBeTrue();
});

it('documents worker runtime validation and supervised fallback behavior', function (): void {
    $document = file_get_contents(base_path('docs/WORKER_SUPERVISION.md'));

    expect($document)->toContain('php artisan workers:validate-runtime')
        ->toContain('WORKER_SUPERVISION_DRIVER')
        ->toContain('Safe fallback behavior');
});
