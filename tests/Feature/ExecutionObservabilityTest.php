<?php

use App\Application\Executions\Services\ExecutionLifecycleService;
use App\Domain\Agents\Enums\AgentStatus;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

it('emits structured execution transition metrics', function (): void {
    Log::spy();

    $agent = Agent::factory()->create([
        'status' => AgentStatus::Active,
    ]);
    $task = Task::factory()->create([
        'agent_id' => $agent->id,
        'status' => TaskStatus::Queued,
    ]);

    $service = app(ExecutionLifecycleService::class);

    $execution = $service->createPendingForAssignedTask($task->id, 'task-'.$task->id.'-attempt-1');
    $service->markRunning($execution->id);
    $service->markSucceeded($execution->id, ['summary' => 'done']);

    Log::shouldHaveReceived('info')
        ->with(
            'observability.metric',
            Mockery::on(static function (array $context): bool {
                return $context['name'] === 'runtime.execution.transitions_total'
                    && $context['dimensions']['from'] === 'new'
                    && $context['dimensions']['to'] === 'pending';
            }),
        )
        ->once();

    Log::shouldHaveReceived('info')
        ->with(
            'observability.metric',
            Mockery::on(static function (array $context): bool {
                return $context['name'] === 'runtime.execution.transitions_total'
                    && $context['dimensions']['from'] === 'pending'
                    && $context['dimensions']['to'] === 'running';
            }),
        )
        ->once();

    Log::shouldHaveReceived('info')
        ->with(
            'observability.metric',
            Mockery::on(static function (array $context): bool {
                return $context['name'] === 'runtime.execution.transitions_total'
                    && $context['dimensions']['from'] === 'running'
                    && $context['dimensions']['to'] === 'succeeded';
            }),
        )
        ->once();
});

it('shows observability diagnostics through the console command', function (): void {
    config()->set('observability.enabled', true);
    config()->set('observability.metrics.enabled', true);
    config()->set('observability.tracing.enabled', true);
    config()->set('observability.log_channel', 'stack');

    expect(Artisan::call('observability:diagnose'))->toBe(0);

    $output = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);

    expect($output)->toBe([
        'enabled' => true,
        'metrics_enabled' => true,
        'tracing_enabled' => true,
        'log_channel' => 'stack',
    ]);
});
