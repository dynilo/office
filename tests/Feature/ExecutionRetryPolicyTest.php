<?php

use App\Application\Executions\Services\ExecutionRetryService;
use App\Application\Providers\Exceptions\LlmProviderException;
use App\Domain\Agents\Enums\AgentStatus;
use App\Domain\Executions\Enums\ExecutionStatus;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('schedules a retry only for eligible retriable failures', function (): void {
    $agent = Agent::factory()->create(['status' => AgentStatus::Active]);
    $task = Task::factory()->create([
        'agent_id' => $agent->id,
        'status' => TaskStatus::Queued,
    ]);

    $lifecycle = app(\App\Application\Executions\Services\ExecutionLifecycleService::class);
    $execution = $lifecycle->createPendingForAssignedTask($task->id, 'task-'.$task->id.'-attempt-1');
    $lifecycle->markRunning($execution->id);

    $decision = app(ExecutionRetryService::class)->handleFailure(
        executionId: $execution->id,
        errorMessage: 'Temporary upstream failure.',
        throwable: LlmProviderException::response(
            provider: 'fake',
            message: 'Temporary upstream failure.',
            statusCode: 503,
            errorCode: 'service_unavailable',
            retriable: true,
        ),
        context: ['status_code' => 503],
    );

    $failed = Execution::query()->find($execution->id);
    $retry = Execution::query()->where('retry_of_execution_id', $execution->id)->first();

    expect($decision->shouldRetry)->toBeTrue()
        ->and($decision->classification)->toBe('transient_provider_failure')
        ->and($decision->retryCount)->toBe(1)
        ->and($failed?->status)->toBe(ExecutionStatus::Failed)
        ->and($failed?->failure_classification)->toBe('transient_provider_failure')
        ->and($failed?->next_retry_at)->not->toBeNull()
        ->and($retry)->not->toBeNull()
        ->and($retry?->status)->toBe(ExecutionStatus::Pending)
        ->and($retry?->attempt)->toBe(2)
        ->and($retry?->retry_count)->toBe(1);
});

it('does not schedule a retry for non retriable failures', function (): void {
    $agent = Agent::factory()->create(['status' => AgentStatus::Active]);
    $task = Task::factory()->create([
        'agent_id' => $agent->id,
        'status' => TaskStatus::Queued,
    ]);

    $lifecycle = app(\App\Application\Executions\Services\ExecutionLifecycleService::class);
    $execution = $lifecycle->createPendingForAssignedTask($task->id, 'task-'.$task->id.'-attempt-1');
    $lifecycle->markRunning($execution->id);

    $decision = app(ExecutionRetryService::class)->handleFailure(
        executionId: $execution->id,
        errorMessage: 'Invalid request payload.',
        throwable: LlmProviderException::response(
            provider: 'fake',
            message: 'Invalid request payload.',
            statusCode: 400,
            errorCode: 'invalid_request',
            retriable: false,
        ),
        context: ['status_code' => 400],
    );

    $failed = Execution::query()->find($execution->id);

    expect($decision->shouldRetry)->toBeFalse()
        ->and($decision->classification)->toBe('provider_validation_failure')
        ->and($failed?->status)->toBe(ExecutionStatus::Failed)
        ->and($failed?->failure_classification)->toBe('provider_validation_failure')
        ->and($failed?->next_retry_at)->toBeNull();

    expect(Execution::query()->where('retry_of_execution_id', $execution->id)->exists())->toBeFalse();
});
