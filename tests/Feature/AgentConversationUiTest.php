<?php

use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\AgentCommunicationLog;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders agent conversation history with task and agent context', function (): void {
    $coordinator = Agent::factory()->create([
        'name' => 'Coordinator',
        'role' => 'coordinator',
    ]);
    $research = Agent::factory()->create([
        'name' => 'Research Analyst',
        'role' => 'research',
    ]);
    $task = Task::factory()->create([
        'title' => 'Research competitor pricing',
    ]);

    AgentCommunicationLog::factory()->create([
        'sender_agent_id' => $coordinator->id,
        'recipient_agent_id' => $research->id,
        'task_id' => $task->id,
        'message_type' => 'handoff.request',
        'subject' => 'Competitor pricing handoff',
        'body' => 'Please investigate competitor pricing.',
        'metadata' => ['priority' => 'high'],
        'sent_at' => now()->subMinutes(2),
    ]);
    AgentCommunicationLog::factory()->create([
        'sender_agent_id' => $research->id,
        'recipient_agent_id' => $coordinator->id,
        'task_id' => $task->id,
        'message_type' => 'status.update',
        'subject' => 'Research underway',
        'body' => 'Initial pricing scan is underway.',
        'sent_at' => now()->subMinute(),
    ]);

    $user = User::factory()->create();
    $user->assignRole(Role::OBSERVER);

    $response = $this->actingAs($user)->get('/admin/conversations');

    $response->assertOk()
        ->assertSee('Conversation history active')
        ->assertSee('Trace agent-to-agent messages.')
        ->assertSee('Coordinator')
        ->assertSee('Research Analyst')
        ->assertSee('Competitor pricing handoff')
        ->assertSee('Research underway')
        ->assertSee('Research competitor pricing')
        ->assertSee('handoff.request')
        ->assertSee('status.update')
        ->assertSee('&quot;priority&quot;: &quot;high&quot;', false);
});

it('filters conversation history by task', function (): void {
    $coordinator = Agent::factory()->create(['name' => 'Coordinator']);
    $research = Agent::factory()->create(['name' => 'Research Analyst']);
    $targetTask = Task::factory()->create(['title' => 'Target task']);
    $otherTask = Task::factory()->create(['title' => 'Other task']);

    AgentCommunicationLog::factory()->create([
        'sender_agent_id' => $coordinator->id,
        'recipient_agent_id' => $research->id,
        'task_id' => $targetTask->id,
        'subject' => 'Target message',
        'body' => 'Visible message.',
    ]);
    AgentCommunicationLog::factory()->create([
        'sender_agent_id' => $research->id,
        'recipient_agent_id' => $coordinator->id,
        'task_id' => $otherTask->id,
        'subject' => 'Other message',
        'body' => 'Filtered message.',
    ]);

    $user = User::factory()->create();
    $user->assignRole(Role::OBSERVER);

    $this->actingAs($user)->get('/admin/conversations?task_id='.$targetTask->id)
        ->assertOk()
        ->assertSee('Target message')
        ->assertSee('Target task')
        ->assertDontSee('Other message');
});

it('filters conversation history by an agent pair', function (): void {
    $coordinator = Agent::factory()->create(['name' => 'Coordinator']);
    $research = Agent::factory()->create(['name' => 'Research Analyst']);
    $finance = Agent::factory()->create(['name' => 'Finance Analyst']);

    AgentCommunicationLog::factory()->create([
        'sender_agent_id' => $coordinator->id,
        'recipient_agent_id' => $research->id,
        'subject' => 'Pair message one',
        'body' => 'Visible pair message.',
        'sent_at' => now()->subMinutes(2),
    ]);
    AgentCommunicationLog::factory()->create([
        'sender_agent_id' => $research->id,
        'recipient_agent_id' => $coordinator->id,
        'subject' => 'Pair message two',
        'body' => 'Visible reverse pair message.',
        'sent_at' => now()->subMinute(),
    ]);
    AgentCommunicationLog::factory()->create([
        'sender_agent_id' => $finance->id,
        'recipient_agent_id' => $coordinator->id,
        'subject' => 'Finance message',
        'body' => 'Filtered pair message.',
        'sent_at' => now(),
    ]);

    $user = User::factory()->create();
    $user->assignRole(Role::OBSERVER);

    $this->actingAs($user)->get('/admin/conversations?first_agent_id='.$coordinator->id.'&second_agent_id='.$research->id)
        ->assertOk()
        ->assertSee('Pair message one')
        ->assertSee('Pair message two')
        ->assertDontSee('Finance message');
});

it('exposes conversation bootstrap state', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::OBSERVER);

    $this->actingAs($user)->get('/admin/conversations')
        ->assertOk()
        ->assertSee('window.OfficeAdmin', false)
        ->assertSee('window.OfficeConversations', false)
        ->assertSee('initialMessages', false)
        ->assertSee('conversationFilters', false)
        ->assertSee('conversationOptions', false);
});
