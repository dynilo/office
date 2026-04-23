<?php

use App\Application\Executions\Services\ExecutionLifecycleService;
use App\Application\Runtime\Events\ExecutionCreated;
use App\Application\Runtime\Events\ExecutionStatusChanged;
use App\Application\Runtime\Events\TaskStatusChanged;
use App\Application\Tasks\Services\TaskLifecycleService;
use App\Domain\Agents\Enums\AgentStatus;
use App\Domain\Executions\Enums\ExecutionStatus;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('emits a runtime event when a task status changes', function (): void {
    $agent = Agent::factory()->create([
        'status' => AgentStatus::Active,
    ]);
    $task = Task::factory()->create([
        'agent_id' => $agent->id,
        'status' => TaskStatus::Draft,
    ]);

    Event::fake([TaskStatusChanged::class]);

    app(TaskLifecycleService::class)->transition($task, TaskStatus::Queued);

    Event::assertDispatched(
        TaskStatusChanged::class,
        fn (TaskStatusChanged $event): bool => $event->taskId === $task->id
            && $event->agentId === $agent->id
            && $event->from === TaskStatus::Draft->value
            && $event->to === TaskStatus::Queued->value
            && $event->broadcastAs() === 'task.status.changed'
            && $event->broadcastWith()['type'] === 'task.status.changed',
    );
});

it('emits runtime events when executions are created and transition state', function (): void {
    $agent = Agent::factory()->create([
        'status' => AgentStatus::Active,
    ]);
    $task = Task::factory()->create([
        'agent_id' => $agent->id,
        'status' => TaskStatus::Queued,
    ]);

    Event::fake([
        ExecutionCreated::class,
        ExecutionStatusChanged::class,
    ]);

    $service = app(ExecutionLifecycleService::class);
    $execution = $service->createPendingForAssignedTask($task->id, 'task-'.$task->id.'-attempt-1');
    $service->markRunning($execution->id);

    Event::assertDispatched(
        ExecutionCreated::class,
        fn (ExecutionCreated $event): bool => $event->executionId === $execution->id
            && $event->taskId === $task->id
            && $event->agentId === $agent->id
            && $event->status === ExecutionStatus::Pending->value
            && $event->attempt === 1
            && $event->broadcastAs() === 'execution.created',
    );

    Event::assertDispatched(
        ExecutionStatusChanged::class,
        fn (ExecutionStatusChanged $event): bool => $event->executionId === $execution->id
            && $event->from === ExecutionStatus::Pending->value
            && $event->to === ExecutionStatus::Running->value
            && $event->broadcastWith()['type'] === 'execution.status.changed',
    );
});

it('keeps broadcast events disabled safely when the null transport is configured', function (): void {
    config(['broadcasting.default' => 'null']);

    $event = new ExecutionStatusChanged(
        executionId: 'execution-01',
        taskId: 'task-01',
        agentId: 'agent-01',
        from: ExecutionStatus::Running->value,
        to: ExecutionStatus::Succeeded->value,
        attempt: 1,
    );

    expect($event->broadcastWhen())->toBeFalse();

    config(['broadcasting.default' => 'log']);

    expect($event->broadcastWhen())->toBeTrue();
});

it('exposes realtime shell bootstrap and fallback listener integration', function (): void {
    $response = $this->get('/admin');

    $response->assertOk()
        ->assertSee('realtime', false)
        ->assertSee('OfficeRuntimeEvents', false)
        ->assertSee('office:realtime-fallback', false)
        ->assertSee('office:runtime-event', false)
        ->assertSee('task.status.changed', false)
        ->assertSee('execution.created', false)
        ->assertSee('execution.status.changed', false);
});
