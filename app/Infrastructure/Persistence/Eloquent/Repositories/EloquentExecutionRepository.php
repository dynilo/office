<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Executions\Contracts\ExecutionRepository;
use App\Domain\Executions\Data\ExecutionResultSummaryData;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;

final class EloquentExecutionRepository implements ExecutionRepository
{
    public function findResultSummary(string $executionId): ?ExecutionResultSummaryData
    {
        $execution = Execution::query()->find($executionId);

        if ($execution === null) {
            return null;
        }

        return ExecutionResultSummaryData::fromArray([
            'execution_id' => $execution->id,
            'status' => $execution->status->value,
            'output_reference' => $execution->output_payload['reference'] ?? null,
            'error_message' => $execution->error_message,
        ]);
    }

    public function exists(string $executionId): bool
    {
        return Execution::query()->whereKey($executionId)->exists();
    }

    public function find(string $executionId): ?Execution
    {
        return Execution::query()->find($executionId);
    }

    public function findByIdempotencyKey(string $idempotencyKey): ?Execution
    {
        return Execution::query()
            ->where('idempotency_key', $idempotencyKey)
            ->first();
    }

    public function save(Execution $execution): Execution
    {
        $execution->save();

        return $execution->refresh();
    }
}
