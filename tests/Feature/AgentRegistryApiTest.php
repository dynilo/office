<?php

use App\Domain\Agents\Enums\AgentStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\AgentProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates an agent through the registry api', function (): void {
    $response = $this->postJson('/api/agents', [
        'name' => 'Support Agent',
        'code' => 'support_agent',
        'role' => 'support',
        'capabilities' => ['triage', 'reply'],
        'model_preference' => 'gpt-5.4',
        'temperature_policy' => [
            'mode' => 'fixed',
            'value' => 0.2,
        ],
        'active' => true,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Support Agent')
        ->assertJsonPath('data.code', 'support_agent')
        ->assertJsonPath('data.role', 'support')
        ->assertJsonPath('data.capabilities.0', 'triage')
        ->assertJsonPath('data.model_preference', 'gpt-5.4')
        ->assertJsonPath('data.active', true)
        ->assertJsonPath('data.status', 'active');

    $this->assertDatabaseHas('agents', [
        'code' => 'support_agent',
        'key' => 'support_agent',
        'role' => 'support',
        'status' => AgentStatus::Active->value,
    ]);

    $this->assertDatabaseHas('agent_profiles', [
        'model_preference' => 'gpt-5.4',
    ]);
});

it('lists agents through the registry api', function (): void {
    $agent = Agent::factory()->create([
        'name' => 'Operations Agent',
        'code' => 'operations_agent',
        'key' => 'operations_agent',
        'role' => 'operations',
    ]);
    AgentProfile::factory()->for($agent)->create([
        'model_preference' => 'gpt-5.4-mini',
    ]);

    $response = $this->getJson('/api/agents');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $agent->id)
        ->assertJsonPath('data.0.code', 'operations_agent')
        ->assertJsonPath('data.0.model_preference', 'gpt-5.4-mini');
});

it('shows a single agent through the registry api', function (): void {
    $agent = Agent::factory()->create([
        'code' => 'research_agent',
        'key' => 'research_agent',
        'role' => 'research',
    ]);
    $profile = AgentProfile::factory()->for($agent)->create([
        'model_preference' => 'gpt-5.4',
        'temperature_policy' => [
            'mode' => 'bounded',
            'value' => 0.3,
            'min' => 0.1,
            'max' => 0.5,
        ],
    ]);

    $response = $this->getJson("/api/agents/{$agent->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $agent->id)
        ->assertJsonPath('data.profile_id', $profile->id)
        ->assertJsonPath('data.temperature_policy.mode', 'bounded');
});

it('updates an agent through the registry api', function (): void {
    $agent = Agent::factory()->create([
        'code' => 'writer_agent',
        'key' => 'writer_agent',
        'name' => 'Writer Agent',
        'role' => 'drafting',
        'status' => AgentStatus::Inactive,
    ]);
    AgentProfile::factory()->for($agent)->create([
        'model_preference' => 'gpt-5.4-mini',
        'temperature_policy' => [
            'mode' => 'fixed',
            'value' => 0.1,
        ],
    ]);

    $response = $this->patchJson("/api/agents/{$agent->id}", [
        'name' => 'Senior Writer Agent',
        'code' => 'writer_agent_v2',
        'role' => 'writing',
        'capabilities' => ['draft', 'revise'],
        'model_preference' => 'gpt-5.4',
        'temperature_policy' => [
            'mode' => 'bounded',
            'value' => 0.4,
            'min' => 0.1,
            'max' => 0.7,
        ],
        'active' => true,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Senior Writer Agent')
        ->assertJsonPath('data.code', 'writer_agent_v2')
        ->assertJsonPath('data.role', 'writing')
        ->assertJsonPath('data.active', true);

    $this->assertDatabaseHas('agents', [
        'id' => $agent->id,
        'code' => 'writer_agent_v2',
        'key' => 'writer_agent_v2',
        'status' => AgentStatus::Active->value,
    ]);

    $this->assertDatabaseHas('agent_profiles', [
        'agent_id' => $agent->id,
        'model_preference' => 'gpt-5.4',
    ]);
});

it('activates an agent through the registry api', function (): void {
    $agent = Agent::factory()->create([
        'status' => AgentStatus::Inactive,
    ]);
    AgentProfile::factory()->for($agent)->create();

    $response = $this->patchJson("/api/agents/{$agent->id}/activate");

    $response->assertOk()
        ->assertJsonPath('data.active', true)
        ->assertJsonPath('data.status', 'active');

    $this->assertDatabaseHas('agents', [
        'id' => $agent->id,
        'status' => AgentStatus::Active->value,
    ]);
});

it('deactivates an agent through the registry api', function (): void {
    $agent = Agent::factory()->create([
        'status' => AgentStatus::Active,
    ]);
    AgentProfile::factory()->for($agent)->create();

    $response = $this->patchJson("/api/agents/{$agent->id}/deactivate");

    $response->assertOk()
        ->assertJsonPath('data.active', false)
        ->assertJsonPath('data.status', 'inactive');

    $this->assertDatabaseHas('agents', [
        'id' => $agent->id,
        'status' => AgentStatus::Inactive->value,
    ]);
});

it('returns validation errors for invalid create payloads', function (): void {
    Agent::factory()->create([
        'code' => 'support_agent',
        'key' => 'support_agent',
    ]);

    $response = $this->postJson('/api/agents', [
        'name' => '',
        'code' => 'support_agent',
        'role' => '',
        'capabilities' => [],
        'model_preference' => 123,
        'temperature_policy' => [
            'mode' => 'dynamic',
            'value' => 3,
        ],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors([
            'name',
            'code',
            'role',
            'capabilities',
            'model_preference',
            'temperature_policy.mode',
            'temperature_policy.value',
        ]);
});

it('returns validation errors for invalid update payloads', function (): void {
    $agent = Agent::factory()->create([
        'code' => 'valid_agent',
        'key' => 'valid_agent',
    ]);
    AgentProfile::factory()->for($agent)->create();

    $response = $this->patchJson("/api/agents/{$agent->id}", [
        'name' => 'Updated',
        'code' => 'bad code',
        'role' => 'updated',
        'capabilities' => ['a', 'a'],
        'temperature_policy' => [
            'mode' => 'bounded',
            'min' => 0.8,
            'max' => 0.2,
        ],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors([
            'code',
            'capabilities.1',
            'temperature_policy.max',
        ]);
});
