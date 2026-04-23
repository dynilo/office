<?php

use App\Support\Workers\WorkerSupervisionReadiness;

it('reports the execution worker command from configuration without connecting to redis', function (): void {
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
    config()->set('workers.execution', [
        'name' => 'ai-office-execution-worker',
        'connection' => 'redis',
        'queue' => 'executions',
        'tries' => 3,
        'backoff' => [5, 30, 120],
        'sleep' => 3,
        'timeout' => 60,
        'max_jobs' => 500,
        'max_time' => 3600,
        'memory' => 256,
        'stop_when_empty' => false,
    ]);

    $report = app(WorkerSupervisionReadiness::class)->report();

    expect($report['ready'])->toBeTrue()
        ->and($report['worker']['name'])->toBe('ai-office-execution-worker')
        ->and($report['worker']['command'])->toContain('php artisan queue:work redis')
        ->and($report['worker']['command'])->toContain('--queue=executions')
        ->and($report['worker']['command'])->toContain('--max-jobs=500')
        ->and($report['worker']['command'])->toContain('--max-time=3600')
        ->and($report['worker']['command'])->toContain('--backoff=5,30,120');
});

it('marks readiness incomplete when the supervised worker queue is not aligned with runtime execution queue', function (): void {
    config()->set('queue.runtime.execution_connection', 'redis');
    config()->set('queue.runtime.execution_queue', 'executions');
    config()->set('workers.execution.connection', 'redis');
    config()->set('workers.execution.queue', 'default');

    $report = app(WorkerSupervisionReadiness::class)->report();

    expect($report['ready'])->toBeFalse()
        ->and($report['checks']['runtime_queue_aligned'])->toBeFalse()
        ->and($report['unavailable_reason'])->toBe('runtime_queue_aligned');
});

it('requires bounded worker restart limits for supervision safety', function (): void {
    config()->set('queue.runtime.execution_connection', 'redis');
    config()->set('queue.runtime.execution_queue', 'executions');
    config()->set('workers.execution.connection', 'redis');
    config()->set('workers.execution.queue', 'executions');
    config()->set('workers.execution.max_jobs', 0);
    config()->set('workers.execution.max_time', 0);

    $report = app(WorkerSupervisionReadiness::class)->report();

    expect($report['ready'])->toBeFalse()
        ->and($report['checks']['worker_limits_configured'])->toBeFalse()
        ->and($report['unavailable_reason'])->toBe('worker_limits_configured');
});

it('includes stop when empty in the command only when configured', function (): void {
    config()->set('workers.execution.stop_when_empty', true);

    expect(app(WorkerSupervisionReadiness::class)->command())->toContain('--stop-when-empty');

    config()->set('workers.execution.stop_when_empty', false);

    expect(app(WorkerSupervisionReadiness::class)->command())->not->toContain('--stop-when-empty');
});
