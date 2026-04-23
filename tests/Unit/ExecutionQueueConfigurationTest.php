<?php

use App\Application\Executions\Jobs\StartExecutionJob;

it('routes execution jobs to the configured redis runtime queue', function (): void {
    config()->set('queue.runtime.execution_connection', 'redis');
    config()->set('queue.runtime.execution_queue', 'executions');
    config()->set('queue.runtime.execution_tries', 4);
    config()->set('queue.runtime.execution_backoff', [10, 60]);

    $job = new StartExecutionJob('execution-01');

    expect($job->connection)->toBe('redis')
        ->and($job->queue)->toBe('executions')
        ->and($job->tries())->toBe(4)
        ->and($job->backoff())->toBe([10, 60]);
});
