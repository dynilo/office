<?php

use App\Domain\Executions\Enums\ExecutionStatus;
use App\Domain\Tasks\Enums\TaskPriority;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\AgentCommunicationLog;
use App\Infrastructure\Persistence\Eloquent\Models\Artifact;
use App\Infrastructure\Persistence\Eloquent\Models\AuditEvent;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use App\Infrastructure\Persistence\Eloquent\Models\ExecutionLog;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the task queue page with initial task data and inspection details', function (): void {
    $sender = Agent::factory()->create([
        'name' => 'Coordinator',
        'role' => 'coordinator',
    ]);
    $recipient = Agent::factory()->create([
        'name' => 'Research Analyst',
        'role' => 'research',
    ]);
    $task = Task::factory()->for($recipient, 'agent')->create([
        'title' => 'Map competitor funding signals',
        'summary' => 'Research funding movements before the weekly brief.',
        'description' => 'Inspect recent market activity and summarize signal quality.',
        'status' => TaskStatus::Queued,
        'priority' => TaskPriority::High,
        'source' => 'admin',
        'requested_agent_role' => 'research',
        'payload' => [
            'request' => 'Find funding signals',
            'market' => 'enterprise AI',
        ],
    ]);
    $execution = Execution::factory()->for($recipient, 'agent')->for($task)->create([
        'status' => ExecutionStatus::Succeeded,
        'attempt' => 2,
        'output_payload' => ['summary' => 'Funding signal quality is high.'],
    ]);
    ExecutionLog::factory()->for($execution)->create([
        'sequence' => 1,
        'level' => 'info',
        'message' => 'execution.succeeded',
    ]);
    Artifact::factory()->for($task)->for($execution)->create([
        'kind' => 'json',
        'name' => 'structured_result',
        'content_json' => ['summary' => 'Funding signal quality is high.'],
    ]);
    AuditEvent::query()->create([
        'event_name' => 'task.created',
        'auditable_type' => 'task',
        'auditable_id' => $task->id,
        'actor_type' => 'user',
        'actor_id' => 'admin-user',
        'source' => 'ui-test',
        'metadata' => ['state' => 'queued'],
        'occurred_at' => now(),
    ]);
    AgentCommunicationLog::factory()->create([
        'sender_agent_id' => $sender->id,
        'recipient_agent_id' => $recipient->id,
        'task_id' => $task->id,
        'message_type' => 'handoff.request',
        'subject' => 'Research handoff',
        'body' => 'Please map funding signals.',
    ]);

    $user = User::factory()->create();
    $user->assignRole(Role::OPERATOR);

    $response = $this->actingAs($user)->get('/admin/tasks');

    $response->assertOk()
        ->assertSee('Task queue active')
        ->assertSee('Map competitor funding signals')
        ->assertSee('Research funding movements before the weekly brief.')
        ->assertSee('queued')
        ->assertSee('high')
        ->assertSee('research')
        ->assertSee('Task details')
        ->assertSee('Payload JSON')
        ->assertSee('enterprise AI')
        ->assertSee('Executions')
        ->assertSee('attempt 2')
        ->assertSee('Artifacts')
        ->assertSee('structured_result')
        ->assertSee('Audit events')
        ->assertSee('task.created')
        ->assertSee('Communication history')
        ->assertSee('handoff.request')
        ->assertSee('Research handoff');
});

it('renders task create controls for draft and queued intake', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::OPERATOR);

    $response = $this->actingAs($user)->get('/admin/tasks');

    $response->assertOk()
        ->assertSee('Create task')
        ->assertSee('Title')
        ->assertSee('Summary')
        ->assertSee('Description')
        ->assertSee('Priority')
        ->assertSee('Initial state')
        ->assertSee('Queued')
        ->assertSee('Draft')
        ->assertSee('Requested agent role')
        ->assertSee('Payload JSON');
});

it('exposes task api integration bootstrap on the queue page', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::OPERATOR);

    $response = $this->actingAs($user)->get('/admin/tasks');

    $response->assertOk()
        ->assertSee('window.OfficeAdmin', false)
        ->assertSee('taskQueue', false)
        ->assertSee('initialTasks', false)
        ->assertSee('\/api\/admin\/tasks', false)
        ->assertSee('\/api\/tasks', false)
        ->assertSee('task-refresh')
        ->assertSee('task-create-form')
        ->assertSee('executions', false)
        ->assertSee('artifacts', false)
        ->assertSee('audit_events', false)
        ->assertSee('communications', false);
});
