<?php

namespace App\Application\Costs\Services;

use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use App\Infrastructure\Persistence\Eloquent\Models\ProviderUsageRecord;

final class ProviderUsageCostTracker
{
    public function __construct(
        private readonly ProviderUsageCostEstimator $estimator,
    ) {}

    /**
     * @param  array<string, mixed>  $providerResponse
     */
    public function recordForExecution(Execution $execution, array $providerResponse): ?ProviderUsageRecord
    {
        if (! (bool) config('costs.tracking_enabled', true)) {
            return null;
        }

        $provider = $providerResponse['provider'] ?? null;

        if (! is_string($provider) || trim($provider) === '') {
            return null;
        }

        $model = $providerResponse['model'] ?? null;
        $model = is_string($model) && trim($model) !== '' ? $model : null;

        $estimate = $this->estimator->estimate(
            provider: $provider,
            model: $model,
            inputTokens: $this->integerOrNull($providerResponse['input_tokens'] ?? null),
            outputTokens: $this->integerOrNull($providerResponse['output_tokens'] ?? null),
        );

        return ProviderUsageRecord::query()->updateOrCreate(
            ['execution_id' => $execution->id],
            [
                'task_id' => $execution->task_id,
                'agent_id' => $execution->agent_id,
                'provider' => $provider,
                'model' => $model,
                'response_id' => $this->stringOrNull($providerResponse['response_id'] ?? null),
                'input_tokens' => $estimate->inputTokens,
                'output_tokens' => $estimate->outputTokens,
                'total_tokens' => $estimate->totalTokens,
                'estimated_cost_micros' => $estimate->estimatedCostMicros,
                'currency' => $estimate->currency,
                'pricing_source' => $estimate->pricingSource,
                'metadata' => [
                    'request_id' => $this->stringOrNull($providerResponse['request_id'] ?? null),
                    'finish_reason' => $this->stringOrNull($providerResponse['finish_reason'] ?? null),
                ],
            ],
        );
    }

    private function integerOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? $value : null;
    }
}
