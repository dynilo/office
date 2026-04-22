<?php

use App\Application\Executions\Jobs\StartExecutionJob;
use App\Application\Executions\Services\ExecutionLifecycleService;
use App\Domain\Agents\Enums\AgentStatus;
use App\Domain\Executions\Enums\ExecutionStatus;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates pending executions only for assigned tasks and protects idempotency', function (): void {
    $agent = Agent::factory()->create([
        'status' => AgentStatus::Active,
    ]);
    $task = Task::factory()->create([
        'agent_id' => $agent->id,
        'status' => TaskStatus::Queued,
    ]);

    $service = app(ExecutionLifecycleService::class);

    $first = $service->createPendingForAssignedTask($task->id, 'task-'.$task->id.'-attempt-1');
    $second = $service->createPendingForAssignedTask($task->id, 'task-'.$task->id.'-attempt-1');

    expect($first->id)->toBe($second->id)
        ->and($first->status)->toBe(ExecutionStatus::Pending)
        ->and($first->agent_id)->toBe($agent->id)
        ->and($first->logs)->toHaveCount(1);

    expect(Execution::query()->count())->toBe(1);
});

it('blocks execution creation for unassigned tasks', function (): void {
    $task = Task::factory()->create([
        'agent_id' => null,
        'status' => TaskStatus::Queued,
    ]);

    $service = app(ExecutionLifecycleService::class);

    expect(fn () => $service->createPendingForAssignedTask($task->id, 'task-'.$task->id.'-attempt-1'))
        ->toThrow(\App\Support\Exceptions\InvalidStateException::class, 'Task must be assigned before execution can be created.');
});

it('allows valid transitions and persists structured logs', function (): void {
    $agent = Agent::factory()->create([
        'status' => AgentStatus::Active,
    ]);
    $task = Task::factory()->create([
        'agent_id' => $agent->id,
        'status' => TaskStatus::Queued,
    ]);

    $service = app(ExecutionLifecycleService::class);

    $execution = $service->createPendingForAssignedTask($task->id, 'task-'.$task->id.'-attempt-1');

    (new StartExecutionJob($execution->id))->handle($service);
    $succeeded = $service->markSucceeded($execution->id, [
        'reference' => 'executions/'.$execution->id.'.json',
        'summary' => 'Execution completed',
    ]);

    expect($succeeded->status)->toBe(ExecutionStatus::Succeeded)
        ->and($succeeded->started_at)->not->toBeNull()
        ->and($succeeded->finished_at)->not->toBeNull()
        ->and($succeeded->logs)->toHaveCount(3)
        ->and($succeeded->logs->pluck('message')->all())->toBe([
            'execution.pending_created',
            'execution.running',
            'execution.succeeded',
        ])
        ->and($succeeded->logs->last()?->context['output_keys'] ?? null)->toBe(['reference', 'summary']);
});

it('allows failing a running execution and stores failure logs', function (): void {
    $agent = Agent::factory()->create([
        'status' => AgentStatus::Active,
    ]);
    $task = Task::factory()->create([
        'agent_id' => $agent->id,
        'status' => TaskStatus::Queued,
    ]);

    $service = app(ExecutionLifecycleService::class);

    $execution = $service->createPendingForAssignedTask($task->id, 'task-'.$task->id.'-attempt-1');
    $service->markRunning($execution->id);
    $failed = $service->markFailed($execution->id, 'Pipeline validation failed.', [
        'stage' => 'startup',
    ]);

    expect($failed->status)->toBe(ExecutionStatus::Failed)
        ->and($failed->error_message)->toBe('Pipeline validation failed.')
        ->and($failed->logs)->toHaveCount(3)
        ->and($failed->logs->last()?->message)->toBe('execution.failed')
        ->and($failed->logs->last()?->context['stage'] ?? null)->toBe('startup');
});

it('blocks invalid transitions', function (): void {
    $agent = Agent::factory()->create([
        'status' => AgentStatus::Active,
    ]);
    $task = Task::factory()->create([
        'agent_id' => $agent->id,
        'status' => TaskStatus::Queued,
    ]);

    $service = app(ExecutionLifecycleService::class);

    $execution = $service->createPendingForAssignedTask($task->id, 'task-'.$task->id.'-attempt-1');

    expect(fn () => $service->markSucceeded($execution->id, ['summary' => 'done']))
        ->toThrow(\App\Support\Exceptions\InvalidStateException::class, 'Invalid state transition for Execution from [pending] to [succeeded].');

    $service->markRunning($execution->id);
    $service->markSucceeded($execution->id, ['summary' => 'done']);

    expect(fn () => $service->markFailed($execution->id, 'late failure'))
        ->toThrow(\App\Support\Exceptions\InvalidStateException::class, 'Invalid state transition for Execution from [succeeded] to [failed].');
});
