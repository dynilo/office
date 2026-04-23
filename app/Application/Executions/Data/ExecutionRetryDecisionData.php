<?php

namespace App\Application\Executions\Data;

use Carbon\CarbonImmutable;

final readonly class ExecutionRetryDecisionData
{
    public function __construct(
        public string $classification,
        public bool $shouldRetry,
        public int $nextAttempt,
        public int $retryCount,
        public ?CarbonImmutable $nextRetryAt,
    ) {
    }
}
