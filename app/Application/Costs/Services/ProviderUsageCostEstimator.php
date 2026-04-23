<?php

namespace App\Application\Costs\Services;

use App\Application\Costs\Data\CostEstimateData;

final class ProviderUsageCostEstimator
{
    public function estimate(string $provider, ?string $model, ?int $inputTokens, ?int $outputTokens): CostEstimateData
    {
        $inputTokens = max(0, $inputTokens ?? 0);
        $outputTokens = max(0, $outputTokens ?? 0);
        $rates = $this->ratesFor($provider, $model);

        $cost = (($inputTokens / 1_000_000) * $rates['input'])
            + (($outputTokens / 1_000_000) * $rates['output']);

        return new CostEstimateData(
            inputTokens: $inputTokens,
            outputTokens: $outputTokens,
            totalTokens: $inputTokens + $outputTokens,
            estimatedCostMicros: (int) round($cost * 1_000_000),
            currency: (string) config('costs.currency', 'USD'),
            pricingSource: $rates['source'],
        );
    }

    /**
     * @return array{input: float, output: float, source: string}
     */
    private function ratesFor(string $provider, ?string $model): array
    {
        $providerRates = config("costs.provider_rates.{$provider}", []);

        if (! is_array($providerRates)) {
            $providerRates = [];
        }

        $modelRates = null;
        $source = 'unconfigured';

        if ($model !== null && isset($providerRates[$model]) && is_array($providerRates[$model])) {
            $modelRates = $providerRates[$model];
            $source = $provider.':'.$model;
        } elseif (isset($providerRates['*']) && is_array($providerRates['*'])) {
            $modelRates = $providerRates['*'];
            $source = $provider.':*';
        }

        return [
            'input' => (float) ($modelRates['input_per_million_tokens'] ?? 0),
            'output' => (float) ($modelRates['output_per_million_tokens'] ?? 0),
            'source' => $source,
        ];
    }
}
