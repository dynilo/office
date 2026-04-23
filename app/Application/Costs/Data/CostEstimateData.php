<?php

namespace App\Application\Costs\Data;

final readonly class CostEstimateData
{
    public function __construct(
        public int $inputTokens,
        public int $outputTokens,
        public int $totalTokens,
        public int $estimatedCostMicros,
        public string $currency,
        public string $pricingSource,
    ) {}
}
