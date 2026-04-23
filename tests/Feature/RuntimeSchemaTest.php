<?php

use App\Domain\Agents\Enums\AgentStatus;
use App\Domain\Executions\Enums\ExecutionStatus;
use App\Domain\Tasks\Enums\TaskPriority;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\AgentProfile;
use App\Infrastructure\Persistence\Eloquent\Models\AuditEvent;
use App\Infrastructure\Persistence\Eloquent\Models\Artifact;
use App\Infrastructure\Persistence\Eloquent\Models\Document;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use App\Infrastructure\Persistence\Eloquent\Models\ExecutionLog;
use App\Infrastructure\Persistence\Eloquent\Models\KnowledgeItem;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use App\Infrastructure\Persistence\Eloquent\Models\TaskAssignmentDecision;
use App\Infrastructure\Persistence\Eloquent\Models\TaskDependency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('loads the runtime schema through migrations', function (): void {
    expect(Schema::hasTable('agents'))->toBeTrue()
        ->and(Schema::hasTable('knowledge_items'))->toBeTrue()
        ->and(Schema::hasTable('task_assignment_decisions'))->toBeTrue()
        ->and(Schema::hasTable('artifacts'))->toBeTrue()
        ->and(Schema::hasTable('audit_events'))->toBeTrue();
});

it('creates the expected runtime tables and columns', function (): void {
    expect(Schema::hasColumns('agents', [
        'id',
        'code',
        'key',
        'name',
        'role',
        'version',
        'status',
        'capabilities',
        'metadata',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('tasks', [
            'id',
            'agent_id',
            'summary',
            'description',
            'status',
            'priority',
            'source',
            'requested_agent_role',
            'payload',
            'context',
            'due_at',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('executions', [
            'id',
            'task_id',
            'agent_id',
            'idempotency_key',
            'retry_of_execution_id',
            'status',
            'attempt',
            'retry_count',
            'max_retries',
            'input_snapshot',
            'output_payload',
            'provider_response',
            'failure_classification',
            'next_retry_at',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('audit_events', [
            'id',
            'event_name',
            'auditable_type',
            'auditable_id',
            'actor_type',
            'actor_id',
            'source',
            'metadata',
            'occurred_at',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('knowledge_items', [
            'id',
            'document_id',
            'content_hash',
            'embedding_model',
            'embedding_dimensions',
            'embedding_generated_at',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('artifacts', [
            'id',
            'task_id',
            'execution_id',
            'kind',
            'name',
            'content_text',
            'content_json',
            'file_metadata',
            'metadata',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('task_assignment_decisions', [
            'task_id',
            'agent_id',
            'outcome',
            'reason_code',
            'matched_by',
            'context',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('agent_profiles', [
            'agent_id',
            'model_preference',
            'temperature_policy',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('documents', [
            'id',
            'storage_disk',
            'storage_path',
            'checksum',
            'raw_text',
            'text_extracted_at',
        ]))->toBeTrue();
});

it('factories produce valid persisted records', function (): void {
    $agent = Agent::factory()->create(['status' => AgentStatus::Active]);
    $profile = AgentProfile::factory()->for($agent)->create();
    $task = Task::factory()->for($agent)->create([
        'status' => TaskStatus::Queued,
        'priority' => TaskPriority::High,
    ]);
    $dependency = TaskDependency::factory()->create([
        'task_id' => $task->id,
        'depends_on_task_id' => Task::factory()->create()->id,
    ]);
    $assignmentDecision = TaskAssignmentDecision::query()->create([
        'task_id' => $task->id,
        'agent_id' => $agent->id,
        'outcome' => 'assigned',
        'reason_code' => null,
        'matched_by' => 'role',
        'context' => ['considered_agent_ids' => [$agent->id]],
    ]);
    $execution = Execution::factory()->for($agent)->for($task)->create([
        'status' => ExecutionStatus::Running,
    ]);
    $log = ExecutionLog::factory()->for($execution)->create();
    $artifact = Artifact::factory()->for($task)->for($execution)->create();
    $auditEvent = AuditEvent::query()->create([
        'event_name' => 'test.event',
        'auditable_type' => 'task',
        'auditable_id' => $task->id,
        'actor_type' => 'agent',
        'actor_id' => $agent->id,
        'source' => 'runtime_schema_test',
        'metadata' => ['ok' => true],
        'occurred_at' => now(),
    ]);
    $document = Document::factory()->create();
    $knowledgeItem = KnowledgeItem::factory()->for($document)->create();

    expect($agent->id)->toHaveLength(26)
        ->and($agent->code)->not->toBeNull()
        ->and($profile->agent_id)->toBe($agent->id)
        ->and($profile->model_preference)->not->toBeNull()
        ->and($task->status)->toBe(TaskStatus::Queued)
        ->and($task->priority)->toBe(TaskPriority::High)
        ->and($task->summary)->not->toBeNull()
        ->and($task->requested_agent_role)->not->toBeNull()
        ->and($dependency->task_id)->toBe($task->id)
        ->and($assignmentDecision->task_id)->toBe($task->id)
        ->and($execution->status)->toBe(ExecutionStatus::Running)
        ->and($execution->idempotency_key)->not->toBeNull()
        ->and($execution->retry_count)->not->toBeNull()
        ->and($execution->provider_response)->not->toBeNull()
        ->and($log->execution_id)->toBe($execution->id)
        ->and($artifact->execution_id)->toBe($execution->id)
        ->and($artifact->task_id)->toBe($task->id)
        ->and($auditEvent->auditable_id)->toBe($task->id)
        ->and($document->raw_text)->not->toBeNull()
        ->and($knowledgeItem->document_id)->toBe($document->id)
        ->and($knowledgeItem->embedding_model)->toBeNull();
});

it('exposes coherent model relations', function (): void {
    $agent = Agent::factory()->create();
    $profile = AgentProfile::factory()->for($agent)->create();
    $dependencyTask = Task::factory()->create();
    $task = Task::factory()->for($agent)->create();
    TaskDependency::factory()->create([
        'task_id' => $task->id,
        'depends_on_task_id' => $dependencyTask->id,
    ]);
    TaskAssignmentDecision::query()->create([
        'task_id' => $task->id,
        'agent_id' => $agent->id,
        'outcome' => 'assigned',
        'reason_code' => null,
        'matched_by' => 'role',
        'context' => ['considered_agent_ids' => [$agent->id]],
    ]);
    $execution = Execution::factory()->for($agent)->for($task)->create();
    ExecutionLog::factory()->count(2)->for($execution)->sequence(
        ['sequence' => 1],
        ['sequence' => 2],
    )->create();
    Artifact::factory()->count(2)->for($task)->for($execution)->create();
    $document = Document::factory()->create();
    KnowledgeItem::factory()->count(2)->for($document)->create();

    expect($agent->profile)->not->toBeNull()
        ->and($agent->profile->is($profile))->toBeTrue()
        ->and($agent->tasks)->toHaveCount(1)
        ->and($agent->assignmentDecisions)->toHaveCount(1)
        ->and($task->dependencies)->toHaveCount(1)
        ->and($task->dependencies->first()?->is($dependencyTask))->toBeTrue()
        ->and($task->assignmentDecisions)->toHaveCount(1)
        ->and($task->executions)->toHaveCount(1)
        ->and($task->artifacts)->toHaveCount(2)
        ->and($execution->logs)->toHaveCount(2)
        ->and($execution->artifacts)->toHaveCount(2)
        ->and($document->knowledgeItems)->toHaveCount(2)
        ->and($document->knowledgeItems->first()?->metadata)->toBeArray();
});
