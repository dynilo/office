<?php

use App\Application\Tasks\Services\TaskLifecycleService;
use App\Domain\Agents\Enums\AgentStatus;
use App\Domain\Executions\Enums\ExecutionStatus;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows valid task transitions deterministically', function (): void {
    $agent = Agent::factory()->create([
        'status' => AgentStatus::Active,
    ]);
    $task = Task::factory()->create([
        'agent_id' => $agent->id,
        'status' => TaskStatus::Draft,
    ]);

    $service = app(TaskLifecycleService::class);

    $queued = $service->transition($task, TaskStatus::Queued);
    expect($queued->status)->toBe(TaskStatus::Queued);

    $inProgress = $service->transition($queued->fresh(), TaskStatus::InProgress);
    expect($inProgress->status)->toBe(TaskStatus::InProgress);

    Execution::factory()->create([
        'task_id' => $task->id,
        'agent_id' => $agent->id,
        'status' => ExecutionStatus::Succeeded,
    ]);

    $completed = $service->transition($inProgress->fresh(), TaskStatus::Completed);
    expect($completed->status)->toBe(TaskStatus::Completed);
});

it('blocks invalid direct transitions', function (): void {
    $task = Task::factory()->create([
        'agent_id' => null,
        'status' => TaskStatus::Draft,
    ]);

    expect(fn () => app(TaskLifecycleService::class)->transition($task, TaskStatus::Completed))
        ->toThrow(\App\Support\Exceptions\InvalidStateException::class, 'Invalid state transition for Task from [draft] to [completed].');
});

it('blocks moving a queued task to in progress without an assigned agent', function (): void {
    $task = Task::factory()->create([
        'agent_id' => null,
        'status' => TaskStatus::Queued,
    ]);

    expect(fn () => app(TaskLifecycleService::class)->transition($task, TaskStatus::InProgress))
        ->toThrow(\App\Support\Exceptions\InvalidStateException::class, 'Task must be assigned before it can move to in_progress.');
});

it('blocks completion without a succeeded execution', function (): void {
    $agent = Agent::factory()->create([
        'status' => AgentStatus::Active,
    ]);
    $task = Task::factory()->create([
        'agent_id' => $agent->id,
        'status' => TaskStatus::InProgress,
    ]);

    Execution::factory()->create([
        'task_id' => $task->id,
        'agent_id' => $agent->id,
        'status' => ExecutionStatus::Running,
    ]);

    expect(fn () => app(TaskLifecycleService::class)->transition($task->fresh(), TaskStatus::Completed))
        ->toThrow(\App\Support\Exceptions\InvalidStateException::class, 'Task requires a succeeded execution before completion.');
});

it('blocks failure without a failed execution', function (): void {
    $agent = Agent::factory()->create([
        'status' => AgentStatus::Active,
    ]);
    $task = Task::factory()->create([
        'agent_id' => $agent->id,
        'status' => TaskStatus::InProgress,
    ]);

    Execution::factory()->create([
        'task_id' => $task->id,
        'agent_id' => $agent->id,
        'status' => ExecutionStatus::Succeeded,
    ]);

    expect(fn () => app(TaskLifecycleService::class)->transition($task->fresh(), TaskStatus::Failed))
        ->toThrow(\App\Support\Exceptions\InvalidStateException::class, 'Task requires a failed execution before it can fail.');
});
