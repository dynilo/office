<?php

use App\Application\Agents\Services\CoordinatorAgent;
use App\Application\Providers\Contracts\LlmProvider;
use App\Application\Providers\Data\LlmRequestData;
use App\Application\Providers\Data\LlmResponseData;
use App\Domain\Agents\Enums\AgentStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\AgentProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

it('exposes company loop production acceptance through a console command', function (): void {
    config()->set('prompts.default.version', 'company-loop-production-v1');

    $coordinator = Agent::factory()->create([
        'name' => 'Office Coordinator',
        'role' => CoordinatorAgent::ROLE,
        'status' => AgentStatus::Active,
        'capabilities' => ['coordination', 'reporting'],
    ]);
    AgentProfile::factory()->for($coordinator)->create([
        'model_preference' => 'gpt-5.4-mini',
    ]);

    foreach (['strategy', 'finance', 'legal_compliance'] as $role) {
        $agent = Agent::factory()->create([
            'name' => $role.' specialist',
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
            $role = (string) ($request->metadata['agent_role'] ?? 'unknown');

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

    $exitCode = Artisan::call('company-loop:validate-production');
    $output = Artisan::output();

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('"ready": true')
        ->and($output)->toContain('"probe_completed": true')
        ->and($output)->toContain('"probe_rolls_back_cleanly": true');
});

it('documents company loop production acceptance and rollback-safe validation behavior', function (): void {
    $contents = file_get_contents(base_path('docs/COMPANY_LOOP_PRODUCTION_ACCEPTANCE.md'));

    expect($contents)->toContain('php artisan company-loop:validate-production')
        ->and($contents)->toContain('rolled back')
        ->and($contents)->toContain('coordinator')
        ->and($contents)->toContain('legal_compliance');
});
