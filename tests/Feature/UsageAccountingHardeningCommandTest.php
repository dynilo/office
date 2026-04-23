<?php

use App\Application\Executions\Services\ExecutionLifecycleService;
use App\Domain\Agents\Enums\AgentStatus;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

it('exposes usage accounting hardening validation through a console command', function (): void {
    config()->set('costs.tracking_enabled', true);
    config()->set('costs.provider_rates.fake_provider', [
        'gpt-usage-hardening' => [
            'input_per_million_tokens' => 1.0,
            'output_per_million_tokens' => 2.0,
        ],
    ]);

    $agent = Agent::factory()->create([
        'status' => AgentStatus::Active,
    ]);
    $task = Task::factory()->for($agent)->create([
        'status' => TaskStatus::Queued,
    ]);

    $execution = app(ExecutionLifecycleService::class)->createPendingForAssignedTask($task->id, 'usage-hardening-'.$task->id);
    app(ExecutionLifecycleService::class)->markRunning($execution->id);
    app(ExecutionLifecycleService::class)->markSucceeded($execution->id, ['summary' => 'ok'], [
        'provider' => 'fake_provider',
        'model' => 'gpt-usage-hardening',
        'input_tokens' => 100,
        'output_tokens' => 25,
    ]);

    $exitCode = Artisan::call('usage-accounting:validate-runtime');
    $output = Artisan::output();

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('"ready": true')
        ->and($output)->toContain('"usage_dedupe_keys_unique": true')
        ->and($output)->toContain('"provider_token_math_valid": true');
});

it('documents usage accounting production expectations', function (): void {
    $contents = file_get_contents(base_path('docs/USAGE_ACCOUNTING_PRODUCTION.md'));

    expect($contents)->toContain('php artisan usage-accounting:validate-runtime')
        ->and($contents)->toContain('dedupe keys')
        ->and($contents)->toContain('provider_usage_records');
});
