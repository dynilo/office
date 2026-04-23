<?php

use App\Application\Agents\Services\CoordinatorAgent;
use App\Application\Providers\Contracts\LlmProvider;
use App\Application\Providers\Data\LlmRequestData;
use App\Application\Providers\Data\LlmResponseData;
use App\Domain\Agents\Enums\AgentStatus;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\AgentProfile;
use App\Infrastructure\Persistence\Eloquent\Models\Artifact;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the company loop run surface', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::OBSERVER);
    Agent::factory()->create([
        'name' => 'Office Coordinator',
        'role' => CoordinatorAgent::ROLE,
        'status' => AgentStatus::Active,
        'capabilities' => ['coordination'],
    ]);

    $this->actingAs($user)->get('/admin/company-loop')
        ->assertOk()
        ->assertSee('Run a coordinated company loop.')
        ->assertSee('Goal intake')
        ->assertSee('Runtime prerequisites')
        ->assertSee('Office Coordinator')
        ->assertSee('window.OfficeCompanyLoop', false)
        ->assertSee('companyLoopRun', false);
});

it('runs a company loop from the admin ui and displays the report', function (): void {
    config()->set('prompts.default.version', 'company-loop-ui-test-v1');

    $user = User::factory()->create();
    $user->assignRole(Role::OPERATOR);
    createCompanyLoopAgents();

    app()->instance(LlmProvider::class, new class implements LlmProvider
    {
        public function generate(LlmRequestData $request): LlmResponseData
        {
            $role = (string) $request->metadata['agent_role'];
            $payload = match ($role) {
                'strategy' => [
                    'strategic_summary' => 'Use a focused beachhead strategy.',
                    'opportunities' => ['Regulated enterprise pilot'],
                    'risks' => ['Slow procurement'],
                    'recommended_moves' => ['Package compliance proof points'],
                ],
                'finance' => [
                    'financial_summary' => 'The plan is viable with controlled pilot scope.',
                    'assumptions' => ['Three design partners'],
                    'metrics' => ['Pipeline value'],
                    'financial_risks' => ['Services margin drag'],
                ],
                'legal_compliance' => [
                    'review_summary' => 'Run privacy and claims review before launch.',
                    'compliance_flags' => ['Privacy review'],
                    'required_follow_up' => ['Review marketing claims'],
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
                outputTokens: 50,
                requestId: 'req_'.$role,
            );
        }
    });

    $response = $this->actingAs($user)->post('/admin/company-loop', [
        'goal' => 'Launch a premium AI office package for regulated companies.',
        'context_json' => json_encode([
            'market' => 'regulated enterprise',
            'time_horizon' => 'next quarter',
        ], JSON_THROW_ON_ERROR),
    ]);

    $response->assertOk()
        ->assertSee('Company loop report')
        ->assertSee('Company loop completed goal')
        ->assertSee('strategy_brief')
        ->assertSee('finance_analysis')
        ->assertSee('compliance_review')
        ->assertSee('focused beachhead strategy')
        ->assertSee('window.OfficeCompanyLoop', false)
        ->assertSee('lastReport', false);

    $parent = Task::query()
        ->where('source', 'coordinator')
        ->where('payload->type', 'coordinator_goal_intent')
        ->sole();

    expect(Task::query()->where('parent_task_id', $parent->id)->count())->toBe(3)
        ->and(Task::query()->where('parent_task_id', $parent->id)->pluck('status')->all())->each->toBe(TaskStatus::Completed)
        ->and(Artifact::query()->where('name', 'coordinator_final_report')->count())->toBe(1);
});

it('shows a safe error when no active coordinator can run the loop', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::OPERATOR);

    $this->actingAs($user)->post('/admin/company-loop', [
        'goal' => 'Prepare a coordinated market launch readiness report.',
        'context_json' => '{}',
    ])
        ->assertOk()
        ->assertSee('No active coordinator agent is available.')
        ->assertSee('Company loop report');

    expect(Task::query()->count())->toBe(0);
});

function createCompanyLoopAgents(): void
{
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
            'name' => str_replace('_', ' ', $role).' agent',
            'role' => $role,
            'status' => AgentStatus::Active,
            'capabilities' => ['analysis', 'structured_output'],
        ]);
        AgentProfile::factory()->for($agent)->create([
            'model_preference' => 'gpt-5.4-mini',
            'temperature_policy' => ['mode' => 'fixed', 'value' => 0.2],
        ]);
    }
}
