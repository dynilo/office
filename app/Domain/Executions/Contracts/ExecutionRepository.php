<?php

namespace App\Domain\Executions\Contracts;

use App\Domain\Executions\Data\ExecutionResultSummaryData;

interface ExecutionRepository
{
    public function findResultSummary(string $executionId): ?ExecutionResultSummaryData;

    public function exists(string $executionId): bool;
}
