<?php

namespace App\Domain\Executions\Contracts;

use App\Domain\Executions\Data\ExecutionResultSummaryData;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;

interface ExecutionRepository
{
    public function findResultSummary(string $executionId): ?ExecutionResultSummaryData;

    public function exists(string $executionId): bool;

    public function find(string $executionId): ?Execution;

    public function findByIdempotencyKey(string $idempotencyKey): ?Execution;

    public function save(Execution $execution): Execution;
}
