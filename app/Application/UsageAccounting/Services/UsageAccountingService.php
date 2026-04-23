<?php

namespace App\Application\UsageAccounting\Services;

use App\Infrastructure\Persistence\Eloquent\Models\UsageAccountingRecord;
use Illuminate\Database\Eloquent\Builder;

final class UsageAccountingService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        string $metricKey,
        int $quantity = 1,
        ?string $organizationId = null,
        ?string $userId = null,
        ?string $agentId = null,
        ?string $taskId = null,
        ?string $executionId = null,
        array $metadata = [],
        ?string $dedupeKey = null,
    ): UsageAccountingRecord {
        if ($dedupeKey !== null) {
            return UsageAccountingRecord::query()->updateOrCreate(
                ['dedupe_key' => $dedupeKey],
                [
                    'organization_id' => $organizationId,
                    'user_id' => $userId,
                    'agent_id' => $agentId,
                    'task_id' => $taskId,
                    'execution_id' => $executionId,
                    'metric_key' => $metricKey,
                    'quantity' => $quantity,
                    'metadata' => $metadata,
                    'recorded_at' => now(),
                ],
            );
        }

        return UsageAccountingRecord::query()->create([
            'organization_id' => $organizationId,
            'user_id' => $userId,
            'agent_id' => $agentId,
            'task_id' => $taskId,
            'execution_id' => $executionId,
            'metric_key' => $metricKey,
            'dedupe_key' => null,
            'quantity' => $quantity,
            'metadata' => $metadata,
            'recorded_at' => now(),
        ]);
    }

    public function total(
        string $metricKey,
        ?string $organizationId = null,
        ?string $userId = null,
        ?string $agentId = null,
    ): int {
        return (int) $this->filteredQuery(
            metricKey: $metricKey,
            organizationId: $organizationId,
            userId: $userId,
            agentId: $agentId,
        )->sum('quantity');
    }

    /**
     * @return array<string, int>
     */
    public function totalsByMetric(
        ?string $organizationId = null,
        ?string $userId = null,
        ?string $agentId = null,
    ): array {
        return $this->filteredQuery(
            organizationId: $organizationId,
            userId: $userId,
            agentId: $agentId,
        )
            ->selectRaw('metric_key, SUM(quantity) as total_quantity')
            ->groupBy('metric_key')
            ->pluck('total_quantity', 'metric_key')
            ->map(static fn (mixed $total): int => (int) $total)
            ->all();
    }

    private function filteredQuery(
        ?string $metricKey = null,
        ?string $organizationId = null,
        ?string $userId = null,
        ?string $agentId = null,
    ): Builder {
        $query = UsageAccountingRecord::query();

        if ($metricKey !== null) {
            $query->where('metric_key', $metricKey);
        }

        if ($organizationId !== null) {
            $query->where('organization_id', $organizationId);
        }

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        if ($agentId !== null) {
            $query->where('agent_id', $agentId);
        }

        return $query;
    }
}
