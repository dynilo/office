<?php

use App\Application\Executions\Services\ExecutionLifecycleService;
use App\Application\Executions\Services\ExecutionRetryService;
use App\Application\Executions\Services\ExecutionSlaService;
use App\Application\Organizations\Services\OrganizationSettingsService;
use App\Application\Providers\Exceptions\LlmProviderException;
use App\Domain\Agents\Enums\AgentStatus;
use App\Domain\Executions\Enums\ExecutionStatus;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\DeadLetterRecord;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use App\Models\Organization;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates dead letter persistence for terminal execution failures', function (): void {
    expect(Schema::hasTable('dead_letter_records'))->toBeTrue()
        ->and(Schema::hasColumns('dead_letter_records', [
            'organization_id',
            'task_id',
            'agent_id',
            'execution_id',
            'reason_code',
            'error_message',
            'payload',
            'captured_at',
        ]))->toBeTrue();
});

it('marks expired pending work as failed and captures a dead letter', function (): void {
    $organization = Organization::factory()->create();
    app(OrganizationSettingsService::class)->store($organization, [
        'runtime_defaults' => [
            'sla' => [
                'pending_expiration_seconds' => 60,
            ],
        ],
    ]);

    $execution = app(TenantContext::class)->run($organization, function (): Execution {
        $agent = Agent::factory()->create(['status' => AgentStatus::Active]);
        $task = Task::factory()->for($agent)->create([
            'status' => TaskStatus::Queued,
        ]);

        return Execution::factory()->for($task)->for($agent)->create([
            'status' => ExecutionStatus::Pending,
            'started_at' => null,
            'finished_at' => null,
            'created_at' => now()->subMinutes(3),
            'updated_at' => now()->subMinutes(3),
        ]);
    });

    $processed = app(ExecutionSlaService::class)->failExpiredPendingExecutions();

    $execution = $execution->refresh();
    $deadLetter = DeadLetterRecord::query()->sole();

    expect($processed)->toBe(1)
        ->and($execution->status)->toBe(ExecutionStatus::Failed)
        ->and($execution->failure_classification)->toBe('expired_work')
        ->and($deadLetter->execution_id)->toBe($execution->id)
        ->and($deadLetter->reason_code)->toBe('expired_work')
        ->and(data_get($deadLetter->payload, 'pending_expiration_seconds'))->toBe(60);
});

it('marks stuck running work as failed and captures a dead letter', function (): void {
    $organization = Organization::factory()->create();
    app(OrganizationSettingsService::class)->store($organization, [
        'runtime_defaults' => [
            'sla' => [
                'running_timeout_seconds' => 120,
            ],
        ],
    ]);

    $execution = app(TenantContext::class)->run($organization, function (): Execution {
        $agent = Agent::factory()->create(['status' => AgentStatus::Active]);
        $task = Task::factory()->for($agent)->create([
            'status' => TaskStatus::Queued,
        ]);

        return Execution::factory()->for($task)->for($agent)->create([
            'status' => ExecutionStatus::Running,
            'started_at' => now()->subMinutes(10),
            'finished_at' => null,
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(10),
        ]);
    });

    $processed = app(ExecutionSlaService::class)->failTimedOutRunningExecutions();

    $execution = $execution->refresh();
    $deadLetter = DeadLetterRecord::query()->sole();

    expect($processed)->toBe(1)
        ->and($execution->status)->toBe(ExecutionStatus::Failed)
        ->and($execution->failure_classification)->toBe('execution_timeout')
        ->and($deadLetter->reason_code)->toBe('execution_timeout')
        ->and(data_get($deadLetter->payload, 'running_timeout_seconds'))->toBe(120);
});

it('ignores work that is still within sla thresholds', function (): void {
    $organization = Organization::factory()->create();
    app(OrganizationSettingsService::class)->store($organization, [
        'runtime_defaults' => [
            'sla' => [
                'pending_expiration_seconds' => 600,
                'running_timeout_seconds' => 600,
            ],
        ],
    ]);

    [$pending, $running] = app(TenantContext::class)->run($organization, function (): array {
        $agent = Agent::factory()->create(['status' => AgentStatus::Active]);
        $pendingTask = Task::factory()->for($agent)->create(['status' => TaskStatus::Queued]);
        $runningTask = Task::factory()->for($agent)->create(['status' => TaskStatus::Queued]);

        return [
            Execution::factory()->for($pendingTask)->for($agent)->create([
                'status' => ExecutionStatus::Pending,
                'started_at' => null,
                'finished_at' => null,
                'created_at' => now()->subMinutes(2),
                'updated_at' => now()->subMinutes(2),
            ]),
            Execution::factory()->for($runningTask)->for($agent)->create([
                'status' => ExecutionStatus::Running,
                'started_at' => now()->subMinutes(2),
                'finished_at' => null,
                'created_at' => now()->subMinutes(2),
                'updated_at' => now()->subMinutes(2),
            ]),
        ];
    });

    $expiredProcessed = app(ExecutionSlaService::class)->failExpiredPendingExecutions();
    $timedOutProcessed = app(ExecutionSlaService::class)->failTimedOutRunningExecutions();

    expect($expiredProcessed)->toBe(0)
        ->and($timedOutProcessed)->toBe(0)
        ->and($pending->refresh()->status)->toBe(ExecutionStatus::Pending)
        ->and($running->refresh()->status)->toBe(ExecutionStatus::Running)
        ->and(DeadLetterRecord::query()->count())->toBe(0);
});

it('captures a dead letter when a failure is terminal and not retried', function (): void {
    $agent = Agent::factory()->create(['status' => AgentStatus::Active]);
    $task = Task::factory()->create([
        'agent_id' => $agent->id,
        'status' => TaskStatus::Queued,
    ]);

    $lifecycle = app(ExecutionLifecycleService::class);
    $execution = $lifecycle->createPendingForAssignedTask($task->id, 'task-'.$task->id.'-attempt-1');
    $lifecycle->markRunning($execution->id);

    $decision = app(ExecutionRetryService::class)->handleFailure(
        executionId: $execution->id,
        errorMessage: 'Invalid request payload.',
        throwable: LlmProviderException::response(
            provider: 'fake',
            message: 'Invalid request payload.',
            statusCode: 400,
            errorCode: 'invalid_request',
            retriable: false,
        ),
        context: ['status_code' => 400],
    );

    $deadLetter = DeadLetterRecord::query()->sole();

    expect($decision->shouldRetry)->toBeFalse()
        ->and($deadLetter->execution_id)->toBe($execution->id)
        ->and($deadLetter->reason_code)->toBe('provider_validation_failure')
        ->and(data_get($deadLetter->payload, 'status_code'))->toBe(400);
});
