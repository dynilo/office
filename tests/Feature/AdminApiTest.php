<?php

use App\Domain\Agents\Enums\AgentStatus;
use App\Domain\Executions\Enums\ExecutionStatus;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\ApprovalRequest;
use App\Infrastructure\Persistence\Eloquent\Models\AuditEvent;
use App\Infrastructure\Persistence\Eloquent\Models\DeadLetterRecord;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use App\Infrastructure\Persistence\Eloquent\Models\ProviderUsageRecord;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::OBSERVER);

    $this->actingAs($user);
});

it('returns admin summary data', function (): void {
    $activeAgent = Agent::factory()->create(['status' => AgentStatus::Active]);
    $inactiveAgent = Agent::factory()->create(['status' => AgentStatus::Inactive]);
    $queuedTask = Task::factory()->create([
        'agent_id' => $activeAgent->id,
        'status' => TaskStatus::Queued,
    ]);
    $completedTask = Task::factory()->create([
        'agent_id' => $inactiveAgent->id,
        'status' => TaskStatus::Completed,
    ]);
    $pendingExecution = Execution::factory()->create([
        'task_id' => $queuedTask->id,
        'agent_id' => $activeAgent->id,
        'status' => ExecutionStatus::Pending,
    ]);
    $succeededExecution = Execution::factory()->create([
        'task_id' => $completedTask->id,
        'agent_id' => $inactiveAgent->id,
        'status' => ExecutionStatus::Succeeded,
    ]);
    ProviderUsageRecord::factory()->create([
        'execution_id' => $succeededExecution->id,
        'task_id' => $completedTask->id,
        'agent_id' => $inactiveAgent->id,
        'total_tokens' => 1234,
        'estimated_cost_micros' => 5678,
        'currency' => 'USD',
    ]);
    AuditEvent::query()->create([
        'event_name' => 'task.created',
        'auditable_type' => 'task',
        'auditable_id' => '01ARZ3NDEKTSV4RRFFQ69G5FAA',
        'actor_type' => 'system',
        'actor_id' => 'bootstrap',
        'source' => 'test',
        'metadata' => ['ok' => true],
        'occurred_at' => now(),
    ]);
    DeadLetterRecord::factory()
        ->for($queuedTask, 'task')
        ->for($activeAgent)
        ->for($pendingExecution, 'execution')
        ->create();
    ApprovalRequest::factory()->for($queuedTask, 'task')->for($activeAgent)->create();

    $this->getJson('/api/admin/summary')
        ->assertOk()
        ->assertJsonPath('data.agents.total', 2)
        ->assertJsonPath('data.agents.active', 1)
        ->assertJsonPath('data.tasks.total', 2)
        ->assertJsonPath('data.tasks.queued', 1)
        ->assertJsonPath('data.executions.total', 2)
        ->assertJsonPath('data.executions.succeeded', 1)
        ->assertJsonPath('data.audit.total', 1)
        ->assertJsonPath('data.costs.total_tokens', 1234)
        ->assertJsonPath('data.costs.estimated_cost_micros', 5678)
        ->assertJsonPath('data.costs.currency', 'USD')
        ->assertJsonPath('data.attention.failed_executions', 0)
        ->assertJsonPath('data.attention.dead_letters', 1)
        ->assertJsonPath('data.attention.pending_approvals', 1)
        ->assertJsonPath('data.attention.unassigned_queued_tasks', 0)
        ->assertJsonPath('data.attention.open_issues_total', 2);
});

it('lists agents with filtering pagination and sorting', function (): void {
    Agent::factory()->create([
        'name' => 'Zulu Agent',
        'role' => 'research',
        'status' => AgentStatus::Active,
    ]);
    Agent::factory()->create([
        'name' => 'Alpha Agent',
        'role' => 'support',
        'status' => AgentStatus::Inactive,
    ]);
    Agent::factory()->create([
        'name' => 'Bravo Agent',
        'role' => 'research',
        'status' => AgentStatus::Active,
    ]);

    $this->getJson('/api/admin/agents?status=active&role=research&sort=name&direction=asc&per_page=1&page=1')
        ->assertOk()
        ->assertJsonPath('meta.per_page', 1)
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('data.0.name', 'Bravo Agent')
        ->assertJsonPath('data.0.role', 'research')
        ->assertJsonPath('data.0.status', 'active');
});

it('lists tasks with filtering pagination and sorting', function (): void {
    $agent = Agent::factory()->create();
    $older = Task::factory()->create([
        'title' => 'Older Task',
        'agent_id' => $agent->id,
        'status' => TaskStatus::Queued,
        'requested_agent_role' => 'research',
        'created_at' => now()->subDay(),
    ]);
    $newer = Task::factory()->create([
        'title' => 'Newer Task',
        'agent_id' => $agent->id,
        'status' => TaskStatus::Queued,
        'requested_agent_role' => 'research',
        'created_at' => now(),
    ]);
    Task::factory()->create([
        'title' => 'Ignored Task',
        'status' => TaskStatus::Completed,
        'requested_agent_role' => 'support',
    ]);

    $this->getJson("/api/admin/tasks?status=queued&requested_agent_role=research&agent_id={$agent->id}&sort=created_at&direction=desc&per_page=2")
        ->assertOk()
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('data.0.id', $newer->id)
        ->assertJsonPath('data.1.id', $older->id)
        ->assertJsonPath('data.0.state', 'queued');
});

it('lists executions with filtering pagination and sorting', function (): void {
    $agent = Agent::factory()->create();
    $task = Task::factory()->for($agent)->create();
    $older = Execution::factory()->for($agent)->for($task)->create([
        'status' => ExecutionStatus::Failed,
        'attempt' => 1,
        'created_at' => now()->subHour(),
    ]);
    $newer = Execution::factory()->for($agent)->for($task)->create([
        'status' => ExecutionStatus::Failed,
        'attempt' => 2,
        'created_at' => now(),
    ]);
    Execution::factory()->create([
        'status' => ExecutionStatus::Succeeded,
    ]);

    $this->getJson("/api/admin/executions?status=failed&task_id={$task->id}&agent_id={$agent->id}&sort=attempt&direction=desc&per_page=2")
        ->assertOk()
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('data.0.id', $newer->id)
        ->assertJsonPath('data.0.attempt', 2)
        ->assertJsonPath('data.1.id', $older->id)
        ->assertJsonPath('data.1.attempt', 1);
});

it('lists audit events with filtering pagination and sorting', function (): void {
    $older = AuditEvent::query()->create([
        'event_name' => 'task.created',
        'auditable_type' => 'task',
        'auditable_id' => '01ARZ3NDEKTSV4RRFFQ69G5FAA',
        'actor_type' => 'system',
        'actor_id' => 'bootstrap',
        'source' => 'system_boot',
        'metadata' => ['order' => 'older'],
        'occurred_at' => now()->subHour(),
    ]);
    $newer = AuditEvent::query()->create([
        'event_name' => 'task.created',
        'auditable_type' => 'task',
        'auditable_id' => '01ARZ3NDEKTSV4RRFFQ69G5FAB',
        'actor_type' => 'system',
        'actor_id' => 'bootstrap',
        'source' => 'system_boot',
        'metadata' => ['order' => 'newer'],
        'occurred_at' => now(),
    ]);
    AuditEvent::query()->create([
        'event_name' => 'execution.failed',
        'auditable_type' => 'execution',
        'auditable_id' => '01ARZ3NDEKTSV4RRFFQ69G5FAC',
        'actor_type' => 'agent',
        'actor_id' => '01ARZ3NDEKTSV4RRFFQ69G5FAD',
        'source' => 'worker',
        'metadata' => ['ignored' => true],
        'occurred_at' => now()->subMinutes(5),
    ]);

    $this->getJson('/api/admin/audit-events?event_name=task.created&auditable_type=task&actor_type=system&source=system_boot&sort=occurred_at&direction=desc&per_page=2')
        ->assertOk()
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('data.0.id', $newer->id)
        ->assertJsonPath('data.0.event_name', 'task.created')
        ->assertJsonPath('data.1.id', $older->id)
        ->assertJsonPath('data.1.source', 'system_boot');
});
