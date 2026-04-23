<?php

namespace App\Support\Observability;

use Carbon\CarbonImmutable;

final readonly class ObservabilitySpanData
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public string $name,
        public string $traceId,
        public string $spanId,
        public CarbonImmutable $startedAt,
        public array $context = [],
    ) {}
}
