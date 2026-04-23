<?php

namespace App\Support\Usage;

use App\Domain\Executions\Enums\ExecutionStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use App\Infrastructure\Persistence\Eloquent\Models\ProviderUsageRecord;
use App\Infrastructure\Persistence\Eloquent\Models\UsageAccountingRecord;
use Illuminate\Support\Facades\Schema;

final readonly class UsageAccountingHardeningValidation
{
    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        $trackingEnabled = (bool) config('costs.tracking_enabled', true);
        $runtime = [
            'cost_tracking_enabled' => $trackingEnabled,
            'usage_record_count' => UsageAccountingRecord::query()->count(),
            'provider_usage_record_count' => ProviderUsageRecord::query()->count(),
            'duplicate_usage_dedupe_keys' => $this->duplicateUsageDedupeKeys(),
            'invalid_usage_quantities' => $this->invalidUsageQuantities(),
            'missing_provider_usage_execution_ids' => $this->missingProviderUsageExecutionIds($trackingEnabled),
            'invalid_provider_token_records' => $this->invalidProviderTokenRecords(),
        ];

        $checks = [
            'usage_accounting_table_present' => Schema::hasTable('usage_accounting_records'),
            'usage_accounting_dedupe_key_present' => Schema::hasColumn('usage_accounting_records', 'dedupe_key'),
            'provider_usage_table_present' => Schema::hasTable('provider_usage_records'),
            'usage_quantities_positive' => $runtime['invalid_usage_quantities'] === [],
            'usage_dedupe_keys_unique' => $runtime['duplicate_usage_dedupe_keys'] === [],
            'provider_usage_complete_for_succeeded_executions' => $runtime['missing_provider_usage_execution_ids'] === [],
            'provider_token_math_valid' => $runtime['invalid_provider_token_records'] === [],
        ];

        return [
            'environment' => (string) config('app.env'),
            'runtime' => $runtime,
            'checks' => $checks,
            'fallback' => [
                'safe' => true,
                'validation_is_read_only' => true,
                'cost_tracking_disabled_skips_provider_completeness_requirement' => ! $trackingEnabled,
            ],
            'ready' => ! in_array(false, $checks, true),
            'unavailable_reason' => $this->firstFailedCheck($checks),
        ];
    }

    /**
     * @return array<int, array{dedupe_key: string, count: int}>
     */
    private function duplicateUsageDedupeKeys(): array
    {
        return UsageAccountingRecord::query()
            ->selectRaw('dedupe_key, COUNT(*) as duplicate_count')
            ->whereNotNull('dedupe_key')
            ->groupBy('dedupe_key')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('dedupe_key')
            ->get()
            ->map(static fn (UsageAccountingRecord $record): array => [
                'dedupe_key' => (string) $record->getAttribute('dedupe_key'),
                'count' => (int) $record->getAttribute('duplicate_count'),
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: string, metric_key: string, quantity: int}>
     */
    private function invalidUsageQuantities(): array
    {
        return UsageAccountingRecord::query()
            ->where('quantity', '<=', 0)
            ->orderBy('id')
            ->get()
            ->map(static fn (UsageAccountingRecord $record): array => [
                'id' => $record->id,
                'metric_key' => $record->metric_key,
                'quantity' => $record->quantity,
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function missingProviderUsageExecutionIds(bool $trackingEnabled): array
    {
        if (! $trackingEnabled) {
            return [];
        }

        return Execution::query()
            ->where('status', ExecutionStatus::Succeeded->value)
            ->whereNotNull('provider_response')
            ->whereDoesntHave('providerUsageRecords')
            ->orderBy('id')
            ->pluck('id')
            ->all();
    }

    /**
     * @return array<int, array{id: string, execution_id: string, total_tokens: int, expected_total_tokens: int}>
     */
    private function invalidProviderTokenRecords(): array
    {
        return ProviderUsageRecord::query()
            ->whereRaw('total_tokens <> input_tokens + output_tokens')
            ->orderBy('id')
            ->get()
            ->map(static fn (ProviderUsageRecord $record): array => [
                'id' => $record->id,
                'execution_id' => $record->execution_id,
                'total_tokens' => $record->total_tokens,
                'expected_total_tokens' => $record->input_tokens + $record->output_tokens,
            ])
            ->all();
    }

    /**
     * @param  array<string, bool>  $checks
     */
    private function firstFailedCheck(array $checks): ?string
    {
        foreach ($checks as $check => $passed) {
            if (! $passed) {
                return $check;
            }
        }

        return null;
    }
}
