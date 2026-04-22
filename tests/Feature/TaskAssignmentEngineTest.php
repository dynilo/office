<?php

use App\Application\Tasks\Services\AssignTaskService;
use App\Domain\Agents\Enums\AgentStatus;
use App\Domain\Tasks\Enums\TaskPriority;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use App\Infrastructure\Persistence\Eloquent\Models\TaskAssignmentDecision;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('assigns a queued task deterministically to the first matching active agent', function (): void {
    $first = Agent::factory()->create([
        'code' => 'alpha_agent',
        'key' => 'alpha_agent',
        'role' => 'support',
        'status' => AgentStatus::Active,
        'capabilities' => ['triage', 'reply'],
    ]);
    $second = Agent::factory()->create([
        'code' => 'zeta_agent',
        'key' => 'zeta_agent',
        'role' => 'support',
        'status' => AgentStatus::Active,
        'capabilities' => ['triage', 'reply'],
    ]);
    $task = Task::factory()->create([
        'agent_id' => null,
        'status' => TaskStatus::Queued,
        'priority' => TaskPriority::High,
        'requested_agent_role' => 'support',
        'payload' => [
            'required_capabilities' => ['triage', 'reply'],
        ],
    ]);

    $decision = app(AssignTaskService::class)->assign($task->id);

    expect($decision->wasAssigned())->toBeTrue()
        ->and($decision->agentId)->toBe($first->id)
        ->and($decision->matchedBy)->toBe('role_then_capability')
        ->and($decision->consideredAgentIds)->toBe([$first->id, $second->id]);

    $task->refresh();

    expect($task->agent_id)->toBe($first->id);

    $this->assertDatabaseHas('task_assignment_decisions', [
        'task_id' => $task->id,
        'agent_id' => $first->id,
        'outcome' => 'assigned',
        'matched_by' => 'role_then_capability',
    ]);
});

it('persists an unassigned reason when no active agent exists', function (): void {
    Agent::factory()->create([
        'status' => AgentStatus::Inactive,
        'role' => 'support',
    ]);
    $task = Task::factory()->create([
        'agent_id' => null,
        'status' => TaskStatus::Queued,
        'requested_agent_role' => 'support',
    ]);

    $decision = app(AssignTaskService::class)->assign($task->id);

    expect($decision->wasAssigned())->toBeFalse()
        ->and($decision->reasonCode)->toBe('no_active_agent');

    $task->refresh();

    expect($task->agent_id)->toBeNull();

    $this->assertDatabaseHas('task_assignment_decisions', [
        'task_id' => $task->id,
        'agent_id' => null,
        'outcome' => 'unassigned',
        'reason_code' => 'no_active_agent',
    ]);
});

it('persists an unassigned reason when active agents do not match the requested role', function (): void {
    $activeAgent = Agent::factory()->create([
        'status' => AgentStatus::Active,
        'role' => 'operations',
        'capabilities' => ['triage', 'reply'],
    ]);
    $task = Task::factory()->create([
        'agent_id' => null,
        'status' => TaskStatus::Queued,
        'requested_agent_role' => 'support',
        'payload' => [
            'required_capabilities' => ['triage'],
        ],
    ]);

    $decision = app(AssignTaskService::class)->assign($task->id);

    expect($decision->wasAssigned())->toBeFalse()
        ->and($decision->reasonCode)->toBe('role_mismatch')
        ->and($decision->consideredAgentIds)->toBe([$activeAgent->id]);

    $this->assertDatabaseHas('task_assignment_decisions', [
        'task_id' => $task->id,
        'reason_code' => 'role_mismatch',
    ]);
});

it('persists an unassigned reason when capabilities do not match', function (): void {
    $matchingRoleWrongCapability = Agent::factory()->create([
        'status' => AgentStatus::Active,
        'role' => 'support',
        'capabilities' => ['analysis'],
    ]);
    $task = Task::factory()->create([
        'agent_id' => null,
        'status' => TaskStatus::Queued,
        'requested_agent_role' => 'support',
        'payload' => [
            'required_capabilities' => ['triage'],
        ],
    ]);

    $decision = app(AssignTaskService::class)->assign($task->id);

    expect($decision->wasAssigned())->toBeFalse()
        ->and($decision->reasonCode)->toBe('capability_mismatch')
        ->and($decision->consideredAgentIds)->toBe([$matchingRoleWrongCapability->id]);

    $record = TaskAssignmentDecision::query()
        ->where('task_id', $task->id)
        ->latest('created_at')
        ->first();

    expect($record)->not->toBeNull()
        ->and($record?->reason_code)->toBe('capability_mismatch')
        ->and($record?->context['considered_agent_ids'] ?? null)->toBe([$matchingRoleWrongCapability->id]);
});
