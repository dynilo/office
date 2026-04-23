<?php

use App\Application\Executions\Services\ExecutionLifecycleService;
use App\Application\UsageAccounting\Services\UsageAccountingService;
use App\Domain\Agents\Enums\AgentStatus;
use App\Domain\Tasks\Enums\TaskPriority;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\ProviderUsageRecord;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use App\Infrastructure\Persistence\Eloquent\Models\UsageAccountingRecord;
use App\Models\Organization;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates usage accounting persistence distinct from provider cost tracking', function (): void {
    expect(Schema::hasTable('usage_accounting_records'))->toBeTrue()
        ->and(Schema::hasColumns('usage_accounting_records', [
            'organization_id',
            'user_id',
            'agent_id',
            'task_id',
            'execution_id',
            'metric_key',
            'quantity',
            'metadata',
            'recorded_at',
        ]))->toBeTrue();
});

it('records per-user and per-organization task intake usage', function (): void {
    $organization = Organization::factory()->create();
    $user = User::factory()->create();
    $user->joinOrganization($organization, makeCurrent: true);

    app(TenantContext::class)->run($organization, function () use ($user): void {
        $this->actingAs($user)->postJson('/api/tasks', [
            'title' => 'Prepare operating summary',
            'summary' => 'Create an internal summary.',
            'description' => 'Summarize the current operating position.',
            'payload' => ['scope' => 'internal'],
            'priority' => TaskPriority::High->value,
            'source' => 'api',
            'requested_agent_role' => 'research',
            'initial_state' => TaskStatus::Queued->value,
        ])->assertCreated();
    });

    $record = UsageAccountingRecord::query()->sole();
    $usage = app(UsageAccountingService::class);

    expect($record->metric_key)->toBe('tasks.created')
        ->and($record->organization_id)->toBe($organization->id)
        ->and($record->user_id)->toBe($user->id)
        ->and($usage->total('tasks.created'))->toBe(1)
        ->and($usage->total('tasks.created', organizationId: $organization->id))->toBe(1)
        ->and($usage->total('tasks.created', userId: $user->id))->toBe(1)
        ->and($usage->totalsByMetric(organizationId: $organization->id))->toBe([
            'tasks.created' => 1,
        ]);
});

it('records per-agent and runtime execution usage without relying on provider cost records', function (): void {
    $organization = Organization::factory()->create();

    [$agent, $task] = app(TenantContext::class)->run($organization, function (): array {
        $agent = Agent::factory()->create([
            'status' => AgentStatus::Active,
        ]);
        $task = Task::factory()->for($agent)->create([
            'status' => TaskStatus::Queued,
        ]);

        return [$agent, $task];
    });

    $lifecycle = app(ExecutionLifecycleService::class);
    $execution = $lifecycle->createPendingForAssignedTask($task->id, 'task-'.$task->id.'-attempt-1');
    $lifecycle->markRunning($execution->id);
    $lifecycle->markSucceeded($execution->id, ['summary' => 'completed internally']);

    $usage = app(UsageAccountingService::class);

    expect($usage->total('executions.created'))->toBe(1)
        ->and($usage->total('executions.created', organizationId: $organization->id))->toBe(1)
        ->and($usage->total('executions.created', agentId: $agent->id))->toBe(1)
        ->and($usage->total('executions.succeeded'))->toBe(1)
        ->and($usage->totalsByMetric(agentId: $agent->id))->toBe([
            'executions.created' => 1,
            'executions.succeeded' => 1,
        ])
        ->and(ProviderUsageRecord::query()->count())->toBe(0);
});

it('records failed execution usage with failure metadata', function (): void {
    $organization = Organization::factory()->create();

    $execution = app(TenantContext::class)->run($organization, function () {
        $agent = Agent::factory()->create([
            'status' => AgentStatus::Active,
        ]);
        $task = Task::factory()->for($agent)->create([
            'status' => TaskStatus::Queued,
        ]);

        $lifecycle = app(ExecutionLifecycleService::class);
        $execution = $lifecycle->createPendingForAssignedTask($task->id, 'task-'.$task->id.'-attempt-1');
        $lifecycle->markRunning($execution->id);

        return $lifecycle->markFailed(
            $execution->id,
            'Validation failed.',
            ['stage' => 'validation'],
            'validation_failure',
        );
    });

    $record = UsageAccountingRecord::query()
        ->where('metric_key', 'executions.failed')
        ->sole();

    expect($record->execution_id)->toBe($execution->id)
        ->and($record->agent_id)->toBe($execution->agent_id)
        ->and($record->metadata['failure_classification'] ?? null)->toBe('validation_failure')
        ->and(app(UsageAccountingService::class)->total('executions.failed', organizationId: $organization->id))->toBe(1);
});
