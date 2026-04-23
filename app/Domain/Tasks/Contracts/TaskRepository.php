<?php

namespace App\Domain\Tasks\Contracts;

use App\Domain\Tasks\Data\TaskPayloadSummaryData;
use App\Infrastructure\Persistence\Eloquent\Models\Task;

interface TaskRepository
{
    public function findPayloadSummary(string $taskId): ?TaskPayloadSummaryData;

    public function exists(string $taskId): bool;

    public function findForAssignment(string $taskId): ?Task;

    public function findFirstQueuedByRole(string $role): ?Task;

    public function save(Task $task): Task;
}
