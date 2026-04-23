<?php

use App\Domain\Agents\Enums\AgentStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\Document;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use App\Models\Organization;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates organization identity and user membership links', function (): void {
    $organization = Organization::factory()->create([
        'name' => 'Dynilo Labs',
        'slug' => 'dynilo-labs',
    ]);
    $user = User::factory()->create();

    $user->joinOrganization($organization, makeCurrent: true);

    expect($user->fresh()->currentOrganization?->is($organization))->toBeTrue()
        ->and($organization->users()->whereKey($user->id)->exists())->toBeTrue();
});

it('adds tenant linkage columns to core runtime tables', function (): void {
    foreach ([
        'agents',
        'agent_profiles',
        'tasks',
        'task_dependencies',
        'task_assignment_decisions',
        'executions',
        'execution_logs',
        'documents',
        'knowledge_items',
        'artifacts',
        'agent_communication_logs',
        'audit_events',
        'provider_usage_records',
    ] as $table) {
        expect(Schema::hasColumn($table, 'organization_id'))->toBeTrue($table.' must have organization_id');
    }
});

it('assigns tenant identity from context and scopes runtime queries by organization', function (): void {
    $tenant = app(TenantContext::class);
    $alpha = Organization::factory()->create(['slug' => 'alpha-company']);
    $beta = Organization::factory()->create(['slug' => 'beta-company']);

    $alphaAgent = $tenant->run($alpha, fn (): Agent => Agent::factory()->create([
        'name' => 'Alpha Research',
        'status' => AgentStatus::Active,
    ]));
    $tenant->run($alpha, fn (): Task => Task::factory()->for($alphaAgent)->create([
        'title' => 'Alpha tenant task',
    ]));
    $tenant->run($alpha, fn (): Document => Document::factory()->create([
        'title' => 'Alpha operating memo',
    ]));

    $betaAgent = $tenant->run($beta, fn (): Agent => Agent::factory()->create([
        'name' => 'Beta Research',
        'status' => AgentStatus::Active,
    ]));
    $tenant->run($beta, fn (): Task => Task::factory()->for($betaAgent)->create([
        'title' => 'Beta tenant task',
    ]));
    $tenant->run($beta, fn (): Document => Document::factory()->create([
        'title' => 'Beta operating memo',
    ]));

    expect($alphaAgent->organization_id)->toBe($alpha->id)
        ->and($betaAgent->organization_id)->toBe($beta->id);

    $tenant->run($alpha, function () use ($alphaAgent): void {
        expect(Agent::query()->pluck('name')->all())->toBe(['Alpha Research'])
            ->and(Task::query()->pluck('title')->all())->toBe(['Alpha tenant task'])
            ->and(Document::query()->pluck('title')->all())->toBe(['Alpha operating memo'])
            ->and(Agent::query()->find($alphaAgent->id)?->name)->toBe('Alpha Research');
    });

    $tenant->run($beta, function () use ($alphaAgent): void {
        expect(Agent::query()->pluck('name')->all())->toBe(['Beta Research'])
            ->and(Task::query()->pluck('title')->all())->toBe(['Beta tenant task'])
            ->and(Document::query()->pluck('title')->all())->toBe(['Beta operating memo'])
            ->and(Agent::query()->find($alphaAgent->id))->toBeNull();
    });

    expect(Agent::query()->withoutGlobalScope('organization')->count())->toBe(2);
});

it('supports explicit organization queries independent of the active tenant context', function (): void {
    $tenant = app(TenantContext::class);
    $alpha = Organization::factory()->create();
    $beta = Organization::factory()->create();

    $alphaTask = $tenant->run($alpha, fn (): Task => Task::factory()->create([
        'title' => 'Alpha scoped task',
    ]));
    $tenant->run($beta, fn (): Task => Task::factory()->create([
        'title' => 'Beta scoped task',
    ]));

    $tenant->run($beta, function () use ($alpha, $alphaTask): void {
        expect(Task::query()->forOrganization($alpha)->pluck('id')->all())->toBe([$alphaTask->id]);
    });
});

it('propagates tenant context to execution records created inside a tenant', function (): void {
    $organization = Organization::factory()->create();
    $execution = app(TenantContext::class)->run($organization, function (): Execution {
        $agent = Agent::factory()->create(['status' => AgentStatus::Active]);
        $task = Task::factory()->for($agent)->create();

        return Execution::factory()->for($agent)->for($task)->create();
    });

    expect($execution->organization_id)->toBe($organization->id)
        ->and($execution->task->organization_id)->toBe($organization->id)
        ->and($execution->agent->organization_id)->toBe($organization->id);
});
