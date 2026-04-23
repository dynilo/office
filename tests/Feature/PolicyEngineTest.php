<?php

use App\Application\Executions\Services\ExecutionLifecycleService;
use App\Application\Organizations\Services\OrganizationSettingsService;
use App\Application\Tasks\Services\AssignTaskService;
use App\Domain\Agents\Enums\AgentStatus;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use App\Models\Organization;
use App\Support\Exceptions\InvalidStateException;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('denies assignment when organization policy requires agent capabilities that are missing', function (): void {
    $organization = Organization::factory()->create();
    app(OrganizationSettingsService::class)->store($organization, [
        'policy' => [
            'assignment_required_agent_capabilities' => ['trusted'],
        ],
    ]);

    $task = app(TenantContext::class)->run($organization, function (): Task {
        Agent::factory()->create([
            'role' => 'support',
            'status' => AgentStatus::Active,
            'capabilities' => ['triage'],
        ]);

        return Task::factory()->create([
            'agent_id' => null,
            'status' => TaskStatus::Queued,
            'requested_agent_role' => 'support',
            'payload' => [
                'required_capabilities' => ['triage'],
            ],
        ]);
    });

    $decision = app(AssignTaskService::class)->assign($task->id);

    expect($decision->outcome)->toBe('unassigned')
        ->and($decision->reasonCode)->toBe('policy_assignment_required_agent_capabilities')
        ->and($task->fresh()->agent_id)->toBeNull();
});

it('allows assignment when organization assignment policy capabilities are satisfied', function (): void {
    $organization = Organization::factory()->create();
    app(OrganizationSettingsService::class)->store($organization, [
        'policy' => [
            'assignment_required_agent_capabilities' => ['trusted'],
        ],
    ]);

    $task = app(TenantContext::class)->run($organization, function (): Task {
        $agent = Agent::factory()->create([
            'role' => 'support',
            'status' => AgentStatus::Active,
            'capabilities' => ['triage', 'trusted'],
        ]);

        return Task::factory()->for($agent, 'agent')->create([
            'agent_id' => null,
            'status' => TaskStatus::Queued,
            'requested_agent_role' => 'support',
            'payload' => [
                'required_capabilities' => ['triage'],
            ],
        ]);
    });

    $decision = app(AssignTaskService::class)->assign($task->id);

    expect($decision->outcome)->toBe('assigned')
        ->and($decision->reasonCode)->toBeNull()
        ->and($task->fresh()->agent_id)->not->toBeNull();
});

it('blocks execution creation when organization policy requires execution capabilities', function (): void {
    $organization = Organization::factory()->create();
    app(OrganizationSettingsService::class)->store($organization, [
        'policy' => [
            'execution_required_agent_capabilities' => ['execute'],
        ],
    ]);

    $task = app(TenantContext::class)->run($organization, function (): Task {
        $agent = Agent::factory()->create([
            'status' => AgentStatus::Active,
            'capabilities' => ['analysis'],
        ]);

        return Task::factory()->for($agent)->create([
            'status' => TaskStatus::Queued,
        ]);
    });

    expect(fn () => app(ExecutionLifecycleService::class)->createPendingForAssignedTask(
        $task->id,
        'task-'.$task->id.'-attempt-1',
    ))->toThrow(
        InvalidStateException::class,
        'Agent is blocked by execution policy. Missing required policy capabilities: execute.',
    );
});

it('allows execution creation when organization execution policy capabilities are satisfied', function (): void {
    $organization = Organization::factory()->create();
    app(OrganizationSettingsService::class)->store($organization, [
        'policy' => [
            'execution_required_agent_capabilities' => ['execute'],
        ],
    ]);

    $task = app(TenantContext::class)->run($organization, function (): Task {
        $agent = Agent::factory()->create([
            'status' => AgentStatus::Active,
            'capabilities' => ['analysis', 'execute'],
        ]);

        return Task::factory()->for($agent)->create([
            'status' => TaskStatus::Queued,
        ]);
    });

    $execution = app(ExecutionLifecycleService::class)->createPendingForAssignedTask(
        $task->id,
        'task-'.$task->id.'-attempt-1',
    );

    expect($execution->task_id)->toBe($task->id)
        ->and($execution->agent_id)->toBe($task->agent_id);
});
