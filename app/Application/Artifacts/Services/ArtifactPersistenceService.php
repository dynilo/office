<?php

namespace App\Application\Artifacts\Services;

use App\Infrastructure\Persistence\Eloquent\Models\Artifact;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use App\Infrastructure\Persistence\Eloquent\Models\Task;

final class ArtifactPersistenceService
{
    public function store(
        Task $task,
        ?Execution $execution,
        string $kind,
        string $name,
        ?string $contentText = null,
        ?array $contentJson = null,
        ?array $fileMetadata = null,
        array $metadata = [],
    ): Artifact {
        return Artifact::query()->create([
            'task_id' => $task->id,
            'execution_id' => $execution?->id,
            'kind' => $kind,
            'name' => $name,
            'content_text' => $contentText,
            'content_json' => $contentJson,
            'file_metadata' => $fileMetadata,
            'metadata' => $metadata,
        ]);
    }
}
