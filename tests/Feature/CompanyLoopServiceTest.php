<?php

use App\Application\Agents\Services\CoordinatorAgent;
use App\Application\CompanyLoop\Services\CompanyLoopService;
use App\Application\Providers\Contracts\LlmProvider;
use App\Application\Providers\Data\LlmRequestData;
use App\Application\Providers\Data\LlmResponseData;
use App\Domain\Agents\Enums\AgentStatus;
use App\Domain\Executions\Enums\ExecutionStatus;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\AgentCommunicationLog;
use App\Infrastructure\Persistence\Eloquent\Models\AgentProfile;
use App\Infrastructure\Persistence\Eloquent\Models\Artifact;
use App\Infrastructure\Persistence\Eloquent\Models\AuditEvent;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('runs an end-to-end mini company loop with coordinator decomposition and specialist outputs', function (): void {
    config()->set('prompts.default.version', 'specialist-prompt-v1');

    $coordinator = Agent::factory()->create([
        'name' => 'Office Coordinator',
        'role' => CoordinatorAgent::ROLE,
        'status' => AgentStatus::Active,
        'capabilities' => ['coordination', 'reporting'],
    ]);

    foreach (['strategy', 'finance', 'legal_compliance'] as $role) {
        $agent = Agent::factory()->create([
            'name' => $role.' agent',
            'role' => $role,
            'status' => AgentStatus::Active,
            'capabilities' => ['analysis', 'structured_output'],
        ]);
        AgentProfile::factory()->for($agent)->create([
            'model_preference' => 'gpt-5.4-mini',
            'temperature_policy' => ['mode' => 'fixed', 'value' => 0.2],
        ]);
    }

    $provider = new class implements LlmProvider
    {
        /** @var array<int, array<string, mixed>> */
        public array $requests = [];

        public function generate(LlmRequestData $request): LlmResponseData
        {
            $role = (string) $request->metadata['agent_role'];
            $this->requests[] = $request->metadata;

            $payload = match ($role) {
                'strategy' => [
                    'strategic_summary' => 'Enter the segment through a focused strategic wedge.',
                    'opportunities' => ['Enterprise pilot'],
                    'risks' => ['Competitive response'],
                    'recommended_moves' => ['Launch a narrow pilot'],
                ],
                'finance' => [
                    'financial_summary' => 'The loop is viable if adoption assumptions hold.',
                    'assumptions' => ['Pilot conversion reaches target'],
                    'metrics' => ['CAC payback', 'Gross margin'],
                    'financial_risks' => ['Budget overrun'],
                ],
                'legal_compliance' => [
                    'review_summary' => 'The plan needs claims and privacy review.',
                    'compliance_flags' => ['Privacy review'],
                    'required_follow_up' => ['Review external claims'],
                    'approval_recommendation' => 'needs_review',
                ],
                default => ['summary' => 'Unexpected role'],
            };

            return new LlmResponseData(
                provider: 'fake',
                responseId: 'resp_'.$role,
                model: 'gpt-5.4-mini',
                content: json_encode($payload, JSON_THROW_ON_ERROR),
                finishReason: 'stop',
                inputTokens: 100,
                outputTokens: 40,
                requestId: 'req_'.$role,
            );
        }
    };
    app()->instance(LlmProvider::class, $provider);

    $report = app(CompanyLoopService::class)->run(
        coordinator: $coordinator,
        goal: 'Launch a premium AI office package for regulated companies.',
        context: [
            'market' => 'regulated enterprise',
        ],
    );

    $parent = Task::query()
        ->where('source', 'coordinator')
        ->where('payload->type', 'coordinator_goal_intent')
        ->sole();
    $children = Task::query()
        ->where('parent_task_id', $parent->id)
        ->orderBy('decomposition_index')
        ->get();
    $executions = Execution::query()->whereIn('task_id', $children->pluck('id'))->get();
    $artifacts = Artifact::query()->get();

    expect($report->status)->toBe('completed')
        ->and($report->parentTaskId)->toBe($parent->id)
        ->and($report->childTaskCount)->toBe(3)
        ->and($report->childReports)->toHaveCount(3)
        ->and($report->summary)->toContain('strategy, finance, legal_compliance')
        ->and($report->finalReportArtifactId)->not->toBeNull();

    expect($children)->toHaveCount(3)
        ->and($children->pluck('requested_agent_role')->all())->toBe([
            'strategy',
            'finance',
            'legal_compliance',
        ])
        ->and($children->pluck('status')->all())->each->toBe(TaskStatus::Completed)
        ->and($children->pluck('agent_id')->filter())->toHaveCount(3);

    expect($executions)->toHaveCount(3)
        ->and($executions->pluck('status')->all())->each->toBe(ExecutionStatus::Succeeded)
        ->and($executions->pluck('output_payload.output_type')->sort()->values()->all())->toBe([
            'compliance_review',
            'finance_analysis',
            'strategy_brief',
        ])
        ->and($executions->pluck('provider_response.prompt.version')->unique()->values()->all())->toBe([
            'specialist-prompt-v1',
        ])
        ->and($executions->pluck('provider_response.prompt.fingerprint')->filter())->toHaveCount(3);

    expect($artifacts->where('name', 'structured_result'))->toHaveCount(3)
        ->and($artifacts->where('name', 'raw_response'))->toHaveCount(3)
        ->and($artifacts->where('name', 'coordinator_final_report'))->toHaveCount(1)
        ->and($artifacts->firstWhere('id', $report->finalReportArtifactId)?->content_json['status'] ?? null)->toBe('completed');

    expect(AgentCommunicationLog::query()->where('message_type', 'company_loop.handoff')->count())->toBe(3)
        ->and(AgentCommunicationLog::query()->where('message_type', 'company_loop.result')->count())->toBe(3)
        ->and(AuditEvent::query()->where('event_name', 'artifact.stored')->count())->toBe(7)
        ->and($provider->requests)->toHaveCount(3)
        ->and(collect($provider->requests)->pluck('prompt.version')->unique()->values()->all())->toBe([
            'specialist-prompt-v1',
        ]);
});
