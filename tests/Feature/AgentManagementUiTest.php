<?php

use App\Domain\Agents\Enums\AgentStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\AgentProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the agent management page with initial agent data and form controls', function (): void {
    $agent = Agent::factory()->create([
        'name' => 'Research Analyst',
        'code' => 'research_analyst',
        'role' => 'research',
        'status' => AgentStatus::Active,
        'capabilities' => ['analysis', 'reporting'],
    ]);
    AgentProfile::factory()->for($agent)->create([
        'model_preference' => 'gpt-5.4',
        'temperature_policy' => [
            'mode' => 'bounded',
            'value' => 0.4,
            'min' => 0.2,
            'max' => 0.7,
        ],
    ]);

    $response = $this->get('/admin/agents');

    $response->assertOk()
        ->assertSee('Agent management active')
        ->assertSee('Research Analyst')
        ->assertSee('research_analyst')
        ->assertSee('analysis')
        ->assertSee('Create agent')
        ->assertSee('Model preference')
        ->assertSee('Temperature mode')
        ->assertSee('Set agent active');
});

it('exposes agent api integration bootstrap on the management page', function (): void {
    $response = $this->get('/admin/agents');

    $response->assertOk()
        ->assertSee('window.OfficeAdmin', false)
        ->assertSee('agentManagement', false)
        ->assertSee('\/api\/admin\/agents', false)
        ->assertSee('\/api\/agents', false)
        ->assertSee('Create agent')
        ->assertSee('agent-refresh');
});
