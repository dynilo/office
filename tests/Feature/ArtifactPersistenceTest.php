<?php

use App\Application\Artifacts\Services\ArtifactPersistenceService;
use App\Infrastructure\Persistence\Eloquent\Models\Artifact;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('persists structured artifacts linked to tasks and executions', function (): void {
    $task = Task::factory()->create();
    $execution = Execution::factory()->for($task)->create();

    $artifact = app(ArtifactPersistenceService::class)->store(
        task: $task,
        execution: $execution,
        kind: 'json',
        name: 'structured_result',
        contentJson: [
            'summary' => 'A structured result.',
            'findings' => ['one', 'two'],
        ],
        metadata: [
            'source' => 'test',
            'schema_version' => 1,
        ],
    );

    expect($artifact->task_id)->toBe($task->id)
        ->and($artifact->execution_id)->toBe($execution->id)
        ->and($artifact->kind)->toBe('json')
        ->and($artifact->content_json['summary'] ?? null)->toBe('A structured result.')
        ->and($task->fresh()->artifacts)->toHaveCount(1)
        ->and($execution->fresh()->artifacts)->toHaveCount(1);
});

it('stores file metadata artifacts without file ingestion', function (): void {
    $task = Task::factory()->create();
    config()->set('runtime_storage.artifacts.allowed_disks', ['local', 'private']);

    $artifact = app(ArtifactPersistenceService::class)->store(
        task: $task,
        execution: null,
        kind: 'file',
        name: 'report_attachment',
        fileMetadata: [
            'disk' => 'private',
            'path' => 'artifacts/report.txt',
            'original_name' => 'report.txt',
            'mime_type' => 'text/plain',
            'size_bytes' => 512,
            'checksum' => 'abc123',
        ],
        metadata: [
            'source' => 'manual',
        ],
    );

    expect($artifact->execution_id)->toBeNull()
        ->and($artifact->kind)->toBe('file')
        ->and($artifact->file_metadata['path'] ?? null)->toBe('artifacts/report.txt')
        ->and($artifact->file_metadata['storage_intent'] ?? null)->toBe('runtime_artifact')
        ->and($artifact->file_metadata['mime_type'] ?? null)->toBe('text/plain')
        ->and(Artifact::query()->where('name', 'report_attachment')->exists())->toBeTrue();
});
