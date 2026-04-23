<?php

use App\Application\Agents\Services\Specialists\FinanceAnalystAgent;
use App\Application\Agents\Services\Specialists\LegalComplianceReviewAgent;
use App\Application\Agents\Services\Specialists\ProductArchitectAgent;
use App\Application\Agents\Services\Specialists\StrategyAnalystAgent;
use App\Application\Providers\Contracts\LlmProvider;
use App\Application\Providers\Data\LlmRequestData;
use App\Application\Providers\Data\LlmResponseData;
use App\Domain\Agents\Enums\AgentStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\AgentProfile;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use App\Support\Exceptions\InvalidStateException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('resolves specialist agents and produces role-appropriate output shapes', function (string $serviceClass, string $role, string $outputType, string $shapeKey, array $providerPayload): void {
    $provider = new class($providerPayload) implements LlmProvider
    {
        public ?LlmRequestData $lastRequest = null;

        public function __construct(
            private readonly array $payload,
        ) {
        }

        public function generate(LlmRequestData $request): LlmResponseData
        {
            $this->lastRequest = $request;

            return new LlmResponseData(
                provider: 'fake',
                responseId: 'resp_'.$request->metadata['agent_role'],
                model: 'gpt-5.4-mini',
                content: json_encode($this->payload, JSON_THROW_ON_ERROR),
                finishReason: 'stop',
                inputTokens: 80,
                outputTokens: 32,
                requestId: 'req_'.$request->metadata['agent_role'],
            );
        }
    };
    app()->instance(LlmProvider::class, $provider);

    $agent = Agent::factory()->create([
        'name' => str_replace('_', ' ', $role).' specialist',
        'role' => $role,
        'status' => AgentStatus::Active,
        'capabilities' => ['analysis', 'structured_output'],
    ]);
    AgentProfile::factory()->for($agent)->create([
        'model_preference' => 'gpt-5.4-mini',
        'instructions' => ['Return concise structured specialist output.'],
        'temperature_policy' => ['mode' => 'fixed', 'value' => 0.2],
    ]);
    $task = Task::factory()->create([
        'title' => 'Evaluate specialist workstream',
        'summary' => 'Produce a specialist assessment.',
        'description' => 'Use the requested specialist frame and provide structured output.',
        'payload' => [
            'workstream' => $role,
        ],
        'context' => [
            'blocks' => ['Use the existing runtime context only.'],
        ],
    ]);

    $specialist = app($serviceClass);
    $result = $specialist->run($task, $agent);
    $messages = collect($provider->lastRequest?->messages ?? [])->pluck('content')->implode("\n\n");

    expect($specialist->supports($agent))->toBeTrue()
        ->and($specialist->role())->toBe($role)
        ->and($specialist->outputType())->toBe($outputType)
        ->and($result['structured_result']['role'])->toBe($role)
        ->and($result['structured_result']['output_type'])->toBe($outputType)
        ->and($result['structured_result'][$shapeKey])->toBe($providerPayload[$shapeKey])
        ->and($result['raw_response']['response_id'])->toBe('resp_'.$role)
        ->and($provider->lastRequest?->metadata['agent_role'])->toBe($role)
        ->and($provider->lastRequest?->metadata['specialist_output_type'])->toBe($outputType)
        ->and($messages)->toContain('Agent role: '.$role)
        ->and($messages)->toContain('Use the existing runtime context only.');
})->with([
    'strategy analyst' => [
        StrategyAnalystAgent::class,
        'strategy',
        'strategy_brief',
        'strategic_summary',
        [
            'strategic_summary' => 'Prioritize expansion where switching costs are high.',
            'opportunities' => ['Enterprise migration'],
            'risks' => ['Slow adoption'],
            'recommended_moves' => ['Run a focused pilot'],
        ],
    ],
    'finance analyst' => [
        FinanceAnalystAgent::class,
        'finance',
        'finance_analysis',
        'financial_summary',
        [
            'financial_summary' => 'The plan is viable if gross margin stays above target.',
            'assumptions' => ['Gross margin target holds'],
            'metrics' => ['CAC payback'],
            'financial_risks' => ['Budget variance'],
        ],
    ],
    'legal compliance review' => [
        LegalComplianceReviewAgent::class,
        'legal_compliance',
        'compliance_review',
        'review_summary',
        [
            'review_summary' => 'The proposal needs privacy and claims review.',
            'compliance_flags' => ['Privacy review'],
            'required_follow_up' => ['Confirm data retention'],
            'approval_recommendation' => 'needs_review',
        ],
    ],
    'product architect' => [
        ProductArchitectAgent::class,
        'product_architect',
        'product_architecture_plan',
        'architecture_summary',
        [
            'architecture_summary' => 'Split the workflow into intake, planning, and execution boundaries.',
            'components' => ['Intake API', 'Planning service'],
            'tradeoffs' => ['Speed versus extensibility'],
            'implementation_sequence' => ['Stabilize contracts'],
        ],
    ],
]);

it('normalizes non-json provider output into the specialist fallback shape', function (): void {
    app()->instance(LlmProvider::class, new class implements LlmProvider
    {
        public function generate(LlmRequestData $request): LlmResponseData
        {
            return new LlmResponseData(
                provider: 'fake',
                responseId: 'resp_strategy_text',
                model: 'gpt-5.4-mini',
                content: 'Focus on the highest-leverage strategic segment.',
                finishReason: 'stop',
                inputTokens: 20,
                outputTokens: 10,
                requestId: 'req_strategy_text',
            );
        }
    });

    $agent = Agent::factory()->create([
        'role' => 'strategy',
        'status' => AgentStatus::Active,
    ]);
    $task = Task::factory()->create([
        'title' => 'Summarize strategy direction',
    ]);

    $result = app(StrategyAnalystAgent::class)->run($task, $agent);

    expect($result['structured_result']['role'])->toBe('strategy')
        ->and($result['structured_result']['output_type'])->toBe('strategy_brief')
        ->and($result['structured_result']['strategic_summary'])->toBe('Focus on the highest-leverage strategic segment.')
        ->and($result['structured_result']['recommended_moves'])->toBe([]);
});

it('rejects agents with the wrong specialist role', function (): void {
    app()->instance(LlmProvider::class, new class implements LlmProvider
    {
        public function generate(LlmRequestData $request): LlmResponseData
        {
            throw new RuntimeException('Provider should not be called for role mismatch.');
        }
    });

    $agent = Agent::factory()->create([
        'role' => 'research',
        'status' => AgentStatus::Active,
    ]);
    $task = Task::factory()->create();

    expect(fn () => app(FinanceAnalystAgent::class)->run($task, $agent))
        ->toThrow(InvalidStateException::class, 'Specialist agent role must be [finance].');
});
