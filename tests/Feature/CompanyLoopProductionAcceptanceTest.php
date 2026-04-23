<?php

use App\Application\Agents\Services\CoordinatorAgent;
use App\Application\Providers\Contracts\LlmProvider;
use App\Application\Providers\Data\LlmRequestData;
use App\Application\Providers\Data\LlmResponseData;
use App\Domain\Agents\Enums\AgentStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\AgentCommunicationLog;
use App\Infrastructure\Persistence\Eloquent\Models\AgentProfile;
use App\Infrastructure\Persistence\Eloquent\Models\Artifact;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use App\Support\CompanyLoop\CompanyLoopProductionAcceptance;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('reports company loop production readiness and rolls probe data back cleanly', function (): void {
    config()->set('prompts.default.version', 'company-loop-production-v1');

    $coordinator = Agent::factory()->create([
        'role' => CoordinatorAgent::ROLE,
        'status' => AgentStatus::Active,
        'capabilities' => ['coordination', 'reporting'],
    ]);
    AgentProfile::factory()->for($coordinator)->create([
        'model_preference' => 'gpt-5.4-mini',
    ]);

    foreach (['strategy', 'finance', 'legal_compliance'] as $role) {
        $agent = Agent::factory()->create([
            'role' => $role,
            'status' => AgentStatus::Active,
            'capabilities' => ['analysis', 'structured_output'],
        ]);
        AgentProfile::factory()->for($agent)->create([
            'model_preference' => 'gpt-5.4-mini',
        ]);
    }

    app()->instance(LlmProvider::class, new class implements LlmProvider
    {
        public function generate(LlmRequestData $request): LlmResponseData
        {
            $role = (string) $request->metadata['agent_role'];

            $payload = match ($role) {
                'strategy' => ['strategic_summary' => 'Validated strategy output.', 'recommended_moves' => ['Ship narrow']],
                'finance' => ['financial_summary' => 'Validated finance output.', 'metrics' => ['margin']],
                'legal_compliance' => ['review_summary' => 'Validated compliance output.', 'required_follow_up' => ['Review copy']],
                default => ['summary' => 'Unknown'],
            };

            return new LlmResponseData(
                provider: 'fake',
                responseId: 'resp_'.$role,
                model: 'gpt-5.4-mini',
                content: json_encode($payload, JSON_THROW_ON_ERROR),
                finishReason: 'stop',
                inputTokens: 42,
                outputTokens: 21,
                requestId: 'req_'.$role,
            );
        }
    });

    $baseline = [
        'tasks' => Task::query()->count(),
        'executions' => Execution::query()->count(),
        'artifacts' => Artifact::query()->count(),
        'communications' => AgentCommunicationLog::query()->count(),
    ];

    $report = app(CompanyLoopProductionAcceptance::class)->report();

    expect($report['ready'])->toBeTrue()
        ->and($report['checks']['probe_completed'])->toBeTrue()
        ->and($report['checks']['probe_child_tasks_completed'])->toBeTrue()
        ->and($report['checks']['probe_executions_succeeded'])->toBeTrue()
        ->and($report['checks']['probe_artifacts_persisted'])->toBeTrue()
        ->and($report['checks']['probe_communications_persisted'])->toBeTrue()
        ->and($report['checks']['probe_final_report_persisted'])->toBeTrue()
        ->and($report['checks']['probe_rolls_back_cleanly'])->toBeTrue()
        ->and($report['runtime']['probe']['child_task_ids'])->toHaveCount(3)
        ->and($report['runtime']['probe']['execution_ids'])->toHaveCount(3)
        ->and($report['runtime']['probe']['artifact_names'])->toContain('coordinator_final_report');

    expect(Task::query()->count())->toBe($baseline['tasks'])
        ->and(Execution::query()->count())->toBe($baseline['executions'])
        ->and(Artifact::query()->count())->toBe($baseline['artifacts'])
        ->and(AgentCommunicationLog::query()->count())->toBe($baseline['communications']);
});

it('fails safely when required specialist coverage is missing', function (): void {
    config()->set('prompts.default.version', 'company-loop-production-v1');

    $coordinator = Agent::factory()->create([
        'role' => CoordinatorAgent::ROLE,
        'status' => AgentStatus::Active,
        'capabilities' => ['coordination', 'reporting'],
    ]);
    AgentProfile::factory()->for($coordinator)->create([
        'model_preference' => 'gpt-5.4-mini',
    ]);

    $strategy = Agent::factory()->create([
        'role' => 'strategy',
        'status' => AgentStatus::Active,
        'capabilities' => ['analysis', 'structured_output'],
    ]);
    AgentProfile::factory()->for($strategy)->create([
        'model_preference' => 'gpt-5.4-mini',
    ]);

    $report = app(CompanyLoopProductionAcceptance::class)->report();

    expect($report['ready'])->toBeFalse()
        ->and($report['checks']['required_specialists_available'])->toBeFalse()
        ->and($report['fallback']['safe'])->toBeTrue()
        ->and($report['unavailable_reason'])->toBe('required_specialists_available')
        ->and(Task::query()->count())->toBe(0)
        ->and(Execution::query()->count())->toBe(0)
        ->and(Artifact::query()->count())->toBe(0)
        ->and(AgentCommunicationLog::query()->count())->toBe(0);
});
