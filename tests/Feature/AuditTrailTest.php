<?php

use App\Application\Audit\Data\AuditActorData;
use App\Application\Audit\Data\AuditEventData;
use App\Application\Audit\Data\AuditSubjectData;
use App\Application\Audit\Services\AuditEventQueryService;
use App\Application\Audit\Services\AuditEventWriter;
use App\Application\Artifacts\Services\ArtifactPersistenceService;
use App\Application\Executions\Services\ExecutionLifecycleService;
use App\Application\Tasks\Services\AssignTaskService;
use App\Application\Tasks\Services\TaskLifecycleService;
use App\Domain\Agents\Enums\AgentStatus;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('persists audit events with actor and source metadata and supports lookup', function (): void {
    $written = app(AuditEventWriter::class)->write(new AuditEventData(
        eventName: 'document.ingested',
        subject: new AuditSubjectData('document', '01ARZ3NDEKTSV4RRFFQ69G5FAV'),
        actor: new AuditActorData('system', 'ingestion-pipeline'),
        source: 'document_ingestion',
        metadata: [
            'mime_type' => 'text/plain',
            'size_bytes' => 128,
        ],
    ));

    $subjectEvents = app(AuditEventQueryService::class)->forSubject('document', '01ARZ3NDEKTSV4RRFFQ69G5FAV');
    $namedEvents = app(AuditEventQueryService::class)->forEventName('document.ingested');

    expect($written->event_name)->toBe('document.ingested')
        ->and($written->actor_type)->toBe('system')
        ->and($written->actor_id)->toBe('ingestion-pipeline')
        ->and($written->source)->toBe('document_ingestion')
        ->and($written->metadata['mime_type'] ?? null)->toBe('text/plain')
        ->and($subjectEvents)->toHaveCount(1)
        ->and($namedEvents)->toHaveCount(1)
        ->and($subjectEvents->first()?->id)->toBe($written->id);
});

it('emits audit records for assignment, lifecycle transitions, execution creation, and artifact storage', function (): void {
    $agent = Agent::factory()->create([
        'status' => AgentStatus::Active,
        'role' => 'research',
        'capabilities' => ['analysis'],
    ]);
    $task = Task::factory()->create([
        'agent_id' => null,
        'status' => TaskStatus::Queued,
        'requested_agent_role' => 'research',
        'payload' => [
            'required_capabilities' => ['analysis'],
        ],
    ]);

    $assignment = app(AssignTaskService::class)->assign($task->id);
    expect($assignment->wasAssigned())->toBeTrue();

    $task = $task->fresh();
    $inProgress = app(TaskLifecycleService::class)->transition($task, TaskStatus::InProgress);
    $execution = app(ExecutionLifecycleService::class)->createPendingForAssignedTask(
        taskId: $inProgress->id,
        idempotencyKey: 'audit-task-'.$inProgress->id,
    );
    app(ArtifactPersistenceService::class)->store(
        task: $inProgress,
        execution: $execution,
        kind: 'json',
        name: 'audit_payload',
        contentJson: ['ok' => true],
        metadata: ['source' => 'test'],
    );

    $taskEvents = app(AuditEventQueryService::class)->forSubject('task', $task->id);
    $executionEvents = app(AuditEventQueryService::class)->forSubject('execution', $execution->id);
    $artifactEvents = app(AuditEventQueryService::class)->forEventName('artifact.stored');

    expect($taskEvents->pluck('event_name')->all())->toContain('task.assignment_recorded')
        ->and($taskEvents->pluck('event_name')->all())->toContain('task.status_changed')
        ->and($taskEvents->firstWhere('event_name', 'task.assignment_recorded')?->actor_id)->toBe($agent->id)
        ->and($taskEvents->firstWhere('event_name', 'task.assignment_recorded')?->source)->toBe('assignment_service')
        ->and($executionEvents->pluck('event_name')->all())->toContain('execution.created')
        ->and($executionEvents->firstWhere('event_name', 'execution.created')?->actor_id)->toBe($agent->id)
        ->and($artifactEvents)->toHaveCount(1)
        ->and($artifactEvents->first()?->source)->toBe('artifact_persistence')
        ->and($artifactEvents->first()?->metadata['task_id'] ?? null)->toBe($task->id);
});
