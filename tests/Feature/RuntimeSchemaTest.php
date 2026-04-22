<?php

use App\Domain\Agents\Enums\AgentStatus;
use App\Domain\Executions\Enums\ExecutionStatus;
use App\Domain\Tasks\Enums\TaskPriority;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\AgentProfile;
use App\Infrastructure\Persistence\Eloquent\Models\Document;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use App\Infrastructure\Persistence\Eloquent\Models\ExecutionLog;
use App\Infrastructure\Persistence\Eloquent\Models\KnowledgeItem;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use App\Infrastructure\Persistence\Eloquent\Models\TaskDependency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('loads the runtime schema through migrations', function (): void {
    expect(Schema::hasTable('agents'))->toBeTrue()
        ->and(Schema::hasTable('knowledge_items'))->toBeTrue();
});

it('creates the expected runtime tables and columns', function (): void {
    expect(Schema::hasColumns('agents', [
        'id',
        'key',
        'name',
        'version',
        'status',
        'capabilities',
        'metadata',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('tasks', [
            'id',
            'agent_id',
            'status',
            'priority',
            'payload',
            'context',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('executions', [
            'id',
            'task_id',
            'agent_id',
            'status',
            'attempt',
            'input_snapshot',
            'output_payload',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('documents', [
            'id',
            'storage_disk',
            'storage_path',
            'checksum',
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
    $execution = Execution::factory()->for($agent)->for($task)->create([
        'status' => ExecutionStatus::Running,
    ]);
    $log = ExecutionLog::factory()->for($execution)->create();
    $document = Document::factory()->create();
    $knowledgeItem = KnowledgeItem::factory()->for($document)->create();

    expect($agent->id)->toHaveLength(26)
        ->and($profile->agent_id)->toBe($agent->id)
        ->and($task->status)->toBe(TaskStatus::Queued)
        ->and($task->priority)->toBe(TaskPriority::High)
        ->and($dependency->task_id)->toBe($task->id)
        ->and($execution->status)->toBe(ExecutionStatus::Running)
        ->and($log->execution_id)->toBe($execution->id)
        ->and($knowledgeItem->document_id)->toBe($document->id);
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
    $execution = Execution::factory()->for($agent)->for($task)->create();
    ExecutionLog::factory()->count(2)->for($execution)->sequence(
        ['sequence' => 1],
        ['sequence' => 2],
    )->create();
    $document = Document::factory()->create();
    KnowledgeItem::factory()->count(2)->for($document)->create();

    expect($agent->profile)->not->toBeNull()
        ->and($agent->profile->is($profile))->toBeTrue()
        ->and($agent->tasks)->toHaveCount(1)
        ->and($task->dependencies)->toHaveCount(1)
        ->and($task->dependencies->first()?->is($dependencyTask))->toBeTrue()
        ->and($task->executions)->toHaveCount(1)
        ->and($execution->logs)->toHaveCount(2)
        ->and($document->knowledgeItems)->toHaveCount(2);
});
