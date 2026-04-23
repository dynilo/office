<?php

use App\Support\Workers\QueueProcessValidation;
use Illuminate\Support\Facades\File;

it('reports a supervised worker process when process snapshot evidence matches the configured command', function (): void {
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
    config()->set('workers.validation.supervisor_driver', 'systemd');
    config()->set('workers.validation.require_in_production', true);
    config()->set('workers.validation.process_snapshot_path', '/tmp/worker-processes.txt');

    File::shouldReceive('exists')->once()->with('/tmp/worker-processes.txt')->andReturnTrue();
    File::shouldReceive('get')->once()->with('/tmp/worker-processes.txt')->andReturn(
        'php artisan queue:work redis --queue=executions --tries=3 --sleep=3 --timeout=60 --max-jobs=500 --max-time=3600 --memory=256 --backoff=5,30,120'
    );

    $report = app(QueueProcessValidation::class)->report();

    expect($report['ready'])->toBeTrue()
        ->and($report['supervision']['evidence_source'])->toBe('process_snapshot')
        ->and($report['process']['command_observed'])->toBeTrue();
});

it('allows a safe fallback when no supervisor evidence exists in a non production environment', function (): void {
    config()->set('app.env', 'local');
    config()->set('workers.validation.supervisor_driver', 'none');
    config()->set('workers.validation.require_in_production', false);
    config()->set('workers.validation.status_output_path', '/tmp/supervisor-status.txt');
    config()->set('workers.validation.process_snapshot_path', '/tmp/worker-processes.txt');

    File::shouldReceive('exists')->once()->with('/tmp/supervisor-status.txt')->andReturnFalse();
    File::shouldReceive('exists')->once()->with('/tmp/worker-processes.txt')->andReturnFalse();

    $report = app(QueueProcessValidation::class)->report();

    expect($report['ready'])->toBeTrue()
        ->and($report['process']['evidence_present'])->toBeFalse()
        ->and($report['process']['fallback_safe'])->toBeTrue();
});

it('fails when supervision is required in production and no worker process evidence is available', function (): void {
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
    config()->set('workers.execution.max_jobs', 500);
    config()->set('workers.execution.max_time', 3600);
    config()->set('workers.execution.memory', 256);
    config()->set('workers.execution.timeout', 60);
    config()->set('workers.validation.supervisor_driver', 'supervisor');
    config()->set('workers.validation.require_in_production', true);
    config()->set('workers.validation.status_output_path', '/tmp/supervisor-status.txt');

    File::shouldReceive('exists')->once()->with('/tmp/supervisor-status.txt')->andReturnFalse();

    $report = app(QueueProcessValidation::class)->report();

    expect($report['ready'])->toBeFalse()
        ->and($report['checks']['process_evidence_present'])->toBeFalse()
        ->and($report['checks']['fallback_safe_without_supervisor'])->toBeFalse()
        ->and($report['unavailable_reason'])->toBe('process_evidence_present');
});
