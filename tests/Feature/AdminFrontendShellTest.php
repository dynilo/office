<?php

use App\Domain\Agents\Enums\AgentStatus;
use App\Domain\Executions\Enums\ExecutionStatus;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\ApprovalRequest;
use App\Infrastructure\Persistence\Eloquent\Models\DeadLetterRecord;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use App\Infrastructure\Persistence\Eloquent\Models\ProviderUsageRecord;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the admin dashboard shell', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::OBSERVER);
    $agent = Agent::factory()->create([
        'name' => 'Research Desk',
        'status' => AgentStatus::Active,
    ]);
    $queuedTask = Task::factory()->for($agent)->create([
        'title' => 'Map market signal shifts',
        'status' => TaskStatus::Queued,
        'requested_agent_role' => 'research',
    ]);
    $completedTask = Task::factory()->for($agent)->create([
        'title' => 'Summarize customer notes',
        'status' => TaskStatus::Completed,
    ]);
    $execution = Execution::factory()->for($agent)->for($completedTask)->create([
        'status' => ExecutionStatus::Succeeded,
    ]);
    $failedExecution = Execution::factory()->for($agent)->for($queuedTask)->create([
        'status' => ExecutionStatus::Failed,
    ]);
    ProviderUsageRecord::factory()->for($agent)->for($completedTask, 'task')->for($execution)->create([
        'total_tokens' => 1500,
        'estimated_cost_micros' => 2500,
        'currency' => 'USD',
    ]);
    DeadLetterRecord::factory()->for($queuedTask, 'task')->for($agent)->for($failedExecution, 'execution')->create();
    ApprovalRequest::factory()->for($queuedTask, 'task')->for($agent)->create();

    $response = $this->actingAs($user)->get('/admin');

    $response->assertOk()
        ->assertSee('Admin Shell')
        ->assertSee('Dashboard metrics active')
        ->assertSee('Runtime command board.')
        ->assertSee('Active agents')
        ->assertSee('Queued tasks')
        ->assertSee('Execution success')
        ->assertSee('Estimated provider cost')
        ->assertSee('Operator attention')
        ->assertSee('Failed executions')
        ->assertSee('Dead letters')
        ->assertSee('Pending approvals')
        ->assertSee('Unassigned queued tasks')
        ->assertSee('Runtime recency')
        ->assertSee('Map market signal shifts')
        ->assertSee('Summarize customer notes')
        ->assertSee('USD 0.0025')
        ->assertSee('window.OfficeAdmin', false)
        ->assertSee('window.OfficeDashboard', false)
        ->assertSee('initialSummary', false)
        ->assertSee($queuedTask->id)
        ->assertSee('\/api\/admin\/summary', false)
        ->assertSee('\/api\/admin\/audit-events', false);
});

it('renders scaffolded admin pages with stable navigation', function (): void {
    $pages = [
        '/admin/agents' => 'Agents',
        '/admin/tasks' => 'Tasks',
        '/admin/executions' => 'Executions',
        '/admin/documents' => 'Documents',
        '/admin/conversations' => 'Conversations',
        '/admin/company-loop' => 'Company Loop',
        '/admin/audit' => 'Audit',
    ];

    $user = User::factory()->create();
    $user->assignRole(Role::OBSERVER);

    foreach ($pages as $uri => $title) {
        $this->actingAs($user)->get($uri)
            ->assertOk()
            ->assertSee('Admin Shell')
            ->assertSee($title)
            ->assertSee('\/api\/admin\/summary', false);
    }
});
