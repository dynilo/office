<?php

namespace App\Domain\Tasks\Contracts;

use App\Domain\Tasks\Data\TaskPayloadSummaryData;

interface TaskRepository
{
    public function findPayloadSummary(string $taskId): ?TaskPayloadSummaryData;

    public function exists(string $taskId): bool;
}
