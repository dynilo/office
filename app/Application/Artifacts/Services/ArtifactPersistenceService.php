<?php

namespace App\Application\Artifacts\Services;

use App\Application\Audit\Data\AuditActorData;
use App\Application\Audit\Data\AuditEventData;
use App\Application\Audit\Data\AuditSubjectData;
use App\Application\Audit\Services\AuditEventWriter;
use App\Infrastructure\Persistence\Eloquent\Models\Artifact;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use App\Support\Storage\RuntimeStorageStrategy;

final class ArtifactPersistenceService
{
    public function __construct(
        private readonly AuditEventWriter $audit,
        private readonly RuntimeStorageStrategy $storage,
    ) {}

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
        $fileMetadata = $this->storage->normalizeArtifactFileMetadata($fileMetadata);

        $artifact = Artifact::query()->create([
            'task_id' => $task->id,
            'execution_id' => $execution?->id,
            'kind' => $kind,
            'name' => $name,
            'content_text' => $contentText,
            'content_json' => $contentJson,
            'file_metadata' => $fileMetadata,
            'metadata' => $metadata,
        ]);

        $this->audit->write(new AuditEventData(
            eventName: 'artifact.stored',
            subject: new AuditSubjectData('artifact', $artifact->id),
            actor: $execution?->agent_id !== null ? new AuditActorData('agent', $execution->agent_id) : null,
            source: 'artifact_persistence',
            metadata: [
                'task_id' => $task->id,
                'execution_id' => $execution?->id,
                'kind' => $kind,
                'name' => $name,
                'storage_disk' => $fileMetadata['disk'] ?? null,
                'storage_path' => $fileMetadata['path'] ?? null,
            ],
        ));

        return $artifact;
    }
}
