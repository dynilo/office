<?php

use App\Domain\Agents\Enums\AgentStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\AgentProfile;
use App\Infrastructure\Persistence\Eloquent\Models\AuditEvent;
use App\Infrastructure\Persistence\Eloquent\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('captures authenticated user actors for agent registry mutations', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $createdId = $this->postJson('/api/agents', [
        'name' => 'Audit Agent',
        'code' => 'audit_agent',
        'role' => 'operations',
        'capabilities' => ['audit'],
        'model_preference' => 'gpt-5.4-mini',
        'temperature_policy' => [
            'mode' => 'fixed',
            'value' => 0.2,
        ],
        'active' => false,
    ])->assertCreated()->json('data.id');

    $this->patchJson("/api/agents/{$createdId}", [
        'name' => 'Updated Audit Agent',
        'code' => 'updated_audit_agent',
        'role' => 'operations',
        'capabilities' => ['audit', 'review'],
        'model_preference' => 'gpt-5.4',
        'temperature_policy' => [
            'mode' => 'fixed',
            'value' => 0.3,
        ],
        'active' => false,
    ])->assertOk();

    $this->patchJson("/api/agents/{$createdId}/activate")->assertOk();
    $this->patchJson("/api/agents/{$createdId}/deactivate")->assertOk();

    expect(AuditEvent::query()
        ->where('auditable_type', 'agent')
        ->where('auditable_id', $createdId)
        ->where('actor_type', 'user')
        ->where('actor_id', $user->id)
        ->pluck('event_name')
        ->all())->toEqualCanonicalizing([
            'agent.created',
            'agent.updated',
            'agent.activated',
            'agent.deactivated',
        ]);
});

it('captures authenticated user actors for task intake mutations', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $taskId = $this->postJson('/api/tasks', [
        'title' => 'Audit task intake',
        'payload' => [
            'source' => 'audit-test',
        ],
        'priority' => 'high',
        'source' => 'admin',
        'requested_agent_role' => 'research',
        'initial_state' => 'queued',
    ])->assertCreated()->json('data.id');

    $event = AuditEvent::query()
        ->where('event_name', 'task.created')
        ->where('auditable_type', 'task')
        ->where('auditable_id', $taskId)
        ->firstOrFail();

    expect($event->actor_type)->toBe('user')
        ->and($event->actor_id)->toBe($user->id)
        ->and($event->source)->toBe('api.task_intake')
        ->and($event->metadata['priority'] ?? null)->toBe('high')
        ->and($event->metadata['requested_agent_role'] ?? null)->toBe('research');
});

it('captures authenticated user actors for document ingestion and extraction mutations', function (): void {
    Storage::fake('local');

    $user = User::factory()->create();
    $this->actingAs($user);

    $documentId = $this->post('/api/documents/ingest', [
        'file' => UploadedFile::fake()->createWithContent(
            'audit-notes.txt',
            "Alpha finding.\n\nBeta finding."
        ),
        'title' => 'Audit Notes',
    ], [
        'Accept' => 'application/json',
    ])->assertCreated()->json('data.id');

    $this->postJson("/api/documents/{$documentId}/extract-knowledge")
        ->assertOk()
        ->assertJsonCount(1, 'data');

    expect(Document::query()->whereKey($documentId)->exists())->toBeTrue()
        ->and(AuditEvent::query()
            ->where('event_name', 'document.ingested')
            ->where('auditable_id', $documentId)
            ->where('actor_type', 'user')
            ->where('actor_id', $user->id)
            ->exists())->toBeTrue()
        ->and(AuditEvent::query()
            ->where('event_name', 'document.knowledge_extracted')
            ->where('auditable_id', $documentId)
            ->where('actor_type', 'user')
            ->where('actor_id', $user->id)
            ->where('source', 'api.knowledge_extraction')
            ->exists())->toBeTrue();
});

it('preserves existing agent audit metadata without authenticated actor leakage', function (): void {
    $user = User::factory()->create();
    $agent = Agent::factory()->create([
        'code' => 'existing_audit_agent',
        'key' => 'existing_audit_agent',
        'role' => 'support',
        'status' => AgentStatus::Inactive,
    ]);
    AgentProfile::factory()->for($agent)->create([
        'model_preference' => 'gpt-5.4-mini',
    ]);

    $this->actingAs($user)
        ->patchJson("/api/agents/{$agent->id}", [
            'name' => 'Existing Audit Agent',
            'code' => 'existing_audit_agent',
            'role' => 'support',
            'capabilities' => ['triage'],
            'model_preference' => 'gpt-5.4',
            'temperature_policy' => [
                'mode' => 'fixed',
                'value' => 0.1,
            ],
            'active' => true,
        ])
        ->assertOk();

    $event = AuditEvent::query()
        ->where('event_name', 'agent.updated')
        ->where('auditable_id', $agent->id)
        ->firstOrFail();

    expect($event->actor_type)->toBe('user')
        ->and($event->actor_id)->toBe($user->id)
        ->and($event->metadata['before']['model_preference'] ?? null)->toBe('gpt-5.4-mini')
        ->and($event->metadata['after']['model_preference'] ?? null)->toBe('gpt-5.4');
});
