<?php

use App\Domain\Tasks\Enums\TaskPriority;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('creates a task in draft state', function (): void {
    $response = $this->postJson('/api/tasks', [
        'title' => 'Review policy update',
        'summary' => 'Need a first-pass review',
        'description' => 'Review the attached policy update and note issues.',
        'payload' => [
            'document_id' => '01HXYZ',
            'channel' => 'api',
        ],
        'priority' => 'high',
        'source' => 'portal',
        'requested_agent_role' => 'compliance',
        'due_at' => '2026-05-01T12:00:00+00:00',
        'initial_state' => 'draft',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.title', 'Review policy update')
        ->assertJsonPath('data.priority', 'high')
        ->assertJsonPath('data.source', 'portal')
        ->assertJsonPath('data.requested_agent_role', 'compliance')
        ->assertJsonPath('data.state', 'draft')
        ->assertJsonPath('data.payload.document_id', '01HXYZ');

    $this->assertDatabaseHas('tasks', [
        'title' => 'Review policy update',
        'status' => TaskStatus::Draft->value,
        'priority' => TaskPriority::High->value,
        'source' => 'portal',
        'requested_agent_role' => 'compliance',
    ]);
});

it('creates a task in queued state', function (): void {
    $response = $this->postJson('/api/tasks', [
        'title' => 'Draft customer reply',
        'payload' => [
            'ticket_id' => 'T-100',
        ],
        'priority' => 'normal',
        'initial_state' => 'queued',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.title', 'Draft customer reply')
        ->assertJsonPath('data.state', 'queued')
        ->assertJsonPath('data.priority', 'normal');

    $this->assertDatabaseHas('tasks', [
        'title' => 'Draft customer reply',
        'status' => TaskStatus::Queued->value,
    ]);
});

it('lists tasks with stable json fields', function (): void {
    $older = Task::factory()->create([
        'title' => 'Older task',
        'status' => TaskStatus::Draft,
        'priority' => TaskPriority::Low,
        'created_at' => now()->subMinute(),
        'updated_at' => now()->subMinute(),
    ]);
    $newer = Task::factory()->create([
        'title' => 'Newer task',
        'status' => TaskStatus::Queued,
        'priority' => TaskPriority::Critical,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->getJson('/api/tasks');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $newer->id)
        ->assertJsonPath('data.0.title', 'Newer task')
        ->assertJsonPath('data.0.state', 'queued')
        ->assertJsonPath('data.0.priority', 'critical')
        ->assertJsonPath('data.1.id', $older->id)
        ->assertJsonPath('data.1.state', 'draft');
});

it('shows a single task with stable json fields', function (): void {
    $task = Task::factory()->create([
        'title' => 'Summarize contract',
        'summary' => 'Need a concise summary',
        'description' => 'Summarize the contract and flag risky clauses.',
        'status' => TaskStatus::Queued,
        'priority' => TaskPriority::High,
        'source' => 'email',
        'requested_agent_role' => 'legal',
        'payload' => ['document_id' => 'doc_1'],
    ]);

    $response = $this->getJson("/api/tasks/{$task->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $task->id)
        ->assertJsonPath('data.title', 'Summarize contract')
        ->assertJsonPath('data.summary', 'Need a concise summary')
        ->assertJsonPath('data.description', 'Summarize the contract and flag risky clauses.')
        ->assertJsonPath('data.source', 'email')
        ->assertJsonPath('data.requested_agent_role', 'legal')
        ->assertJsonPath('data.state', 'queued')
        ->assertJsonPath('data.payload.document_id', 'doc_1');
});

it('returns validation errors for invalid intake payloads', function (): void {
    $response = $this->postJson('/api/tasks', [
        'title' => '',
        'summary' => str_repeat('a', 501),
        'payload' => 'not-an-array',
        'priority' => 'urgent',
        'source' => ['bad'],
        'requested_agent_role' => ['bad'],
        'due_at' => 'not-a-date',
        'initial_state' => 'running',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors([
            'title',
            'summary',
            'payload',
            'priority',
            'source',
            'requested_agent_role',
            'due_at',
            'initial_state',
        ]);
});
