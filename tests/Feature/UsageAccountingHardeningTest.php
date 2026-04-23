<?php

use App\Application\UsageAccounting\Services\UsageAccountingService;
use App\Domain\Agents\Enums\AgentStatus;
use App\Domain\Executions\Enums\ExecutionStatus;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use App\Infrastructure\Persistence\Eloquent\Models\ProviderUsageRecord;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use App\Infrastructure\Persistence\Eloquent\Models\UsageAccountingRecord;
use App\Support\Usage\UsageAccountingHardeningValidation;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('records core usage events idempotently when a dedupe key is provided', function (): void {
    $service = app(UsageAccountingService::class);
    $agent = Agent::factory()->create([
        'status' => AgentStatus::Active,
    ]);
    $task = Task::factory()->for($agent)->create([
        'status' => TaskStatus::Queued,
    ]);
    $execution = Execution::factory()->for($agent)->for($task)->create([
        'status' => ExecutionStatus::Pending,
    ]);

    $first = $service->record(
        metricKey: 'executions.created',
        agentId: $agent->id,
        taskId: $task->id,
        executionId: $execution->id,
        dedupeKey: 'execution-created:'.$execution->id,
        metadata: ['attempt' => 1],
    );

    $second = $service->record(
        metricKey: 'executions.created',
        agentId: $agent->id,
        taskId: $task->id,
        executionId: $execution->id,
        dedupeKey: 'execution-created:'.$execution->id,
        metadata: ['attempt' => 1],
    );

    expect($first->id)->toBe($second->id)
        ->and(UsageAccountingRecord::query()->count())->toBe(1);
});

it('reports accounting hardening as ready when usage and provider records are internally consistent', function (): void {
    UsageAccountingRecord::factory()->create([
        'metric_key' => 'tasks.created',
        'dedupe_key' => 'task-created:01JSHARDENING00000000000002',
        'quantity' => 1,
    ]);

    ProviderUsageRecord::factory()->create([
        'input_tokens' => 100,
        'output_tokens' => 20,
        'total_tokens' => 120,
    ]);

    $report = app(UsageAccountingHardeningValidation::class)->report();

    expect($report['ready'])->toBeTrue()
        ->and($report['checks']['usage_accounting_dedupe_key_present'])->toBeTrue()
        ->and($report['checks']['usage_quantities_positive'])->toBeTrue()
        ->and($report['checks']['usage_dedupe_keys_unique'])->toBeTrue()
        ->and($report['checks']['provider_token_math_valid'])->toBeTrue();
});

it('fails safely when usage quantities or provider token math are invalid', function (): void {
    UsageAccountingRecord::factory()->create([
        'metric_key' => 'tasks.created',
        'dedupe_key' => 'task-created:01JSHARDENING00000000000003',
        'quantity' => 0,
    ]);

    ProviderUsageRecord::factory()->create([
        'input_tokens' => 10,
        'output_tokens' => 5,
        'total_tokens' => 99,
    ]);

    $report = app(UsageAccountingHardeningValidation::class)->report();

    expect($report['ready'])->toBeFalse()
        ->and($report['checks']['usage_quantities_positive'])->toBeFalse()
        ->and($report['checks']['provider_token_math_valid'])->toBeFalse()
        ->and($report['fallback']['safe'])->toBeTrue();
});
