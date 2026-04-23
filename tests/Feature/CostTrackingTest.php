<?php

use App\Application\Executions\Services\ExecutionLifecycleService;
use App\Domain\Agents\Enums\AgentStatus;
use App\Domain\Executions\Enums\ExecutionStatus;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use App\Infrastructure\Persistence\Eloquent\Models\ProviderUsageRecord;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('persists provider usage and estimated cost by execution task and agent', function (): void {
    config()->set('costs.currency', 'USD');
    config()->set('costs.provider_rates.fake_provider', [
        'gpt-cost-test' => [
            'input_per_million_tokens' => 2.0,
            'output_per_million_tokens' => 6.0,
        ],
    ]);

    $agent = Agent::factory()->create([
        'status' => AgentStatus::Active,
        'role' => 'research',
    ]);
    $task = Task::factory()->for($agent)->create([
        'status' => TaskStatus::InProgress,
    ]);
    $execution = Execution::factory()->for($agent)->for($task)->create([
        'status' => ExecutionStatus::Running,
    ]);

    app(ExecutionLifecycleService::class)->markSucceeded(
        executionId: $execution->id,
        outputPayload: ['summary' => 'tracked'],
        providerResponse: [
            'provider' => 'fake_provider',
            'response_id' => 'resp_cost_1',
            'model' => 'gpt-cost-test',
            'content' => 'tracked',
            'finish_reason' => 'stop',
            'input_tokens' => 1000,
            'output_tokens' => 500,
            'request_id' => 'req_cost_1',
        ],
    );

    $record = ProviderUsageRecord::query()->sole();

    expect($record->execution_id)->toBe($execution->id)
        ->and($record->task_id)->toBe($task->id)
        ->and($record->agent_id)->toBe($agent->id)
        ->and($record->provider)->toBe('fake_provider')
        ->and($record->model)->toBe('gpt-cost-test')
        ->and($record->response_id)->toBe('resp_cost_1')
        ->and($record->input_tokens)->toBe(1000)
        ->and($record->output_tokens)->toBe(500)
        ->and($record->total_tokens)->toBe(1500)
        ->and($record->estimated_cost_micros)->toBe(5000)
        ->and($record->currency)->toBe('USD')
        ->and($record->pricing_source)->toBe('fake_provider:gpt-cost-test')
        ->and($record->metadata)->toMatchArray([
            'request_id' => 'req_cost_1',
            'finish_reason' => 'stop',
        ])
        ->and($record->execution->is($execution))->toBeTrue()
        ->and($record->task->is($task))->toBeTrue()
        ->and($record->agent->is($agent))->toBeTrue();
});

it('keeps cost tracking disabled when configured off', function (): void {
    config()->set('costs.tracking_enabled', false);

    $agent = Agent::factory()->create();
    $task = Task::factory()->for($agent)->create([
        'status' => TaskStatus::InProgress,
    ]);
    $execution = Execution::factory()->for($agent)->for($task)->create([
        'status' => ExecutionStatus::Running,
    ]);

    app(ExecutionLifecycleService::class)->markSucceeded(
        executionId: $execution->id,
        outputPayload: ['summary' => 'not tracked'],
        providerResponse: [
            'provider' => 'fake_provider',
            'model' => 'gpt-cost-test',
            'input_tokens' => 100,
            'output_tokens' => 25,
        ],
    );

    expect(ProviderUsageRecord::query()->count())->toBe(0);
});

it('adds the provider usage records schema', function (): void {
    expect(Schema::hasTable('provider_usage_records'))->toBeTrue()
        ->and(Schema::hasColumns('provider_usage_records', [
            'id',
            'execution_id',
            'task_id',
            'agent_id',
            'provider',
            'model',
            'response_id',
            'input_tokens',
            'output_tokens',
            'total_tokens',
            'estimated_cost_micros',
            'currency',
            'pricing_source',
            'metadata',
        ]))->toBeTrue();
});
