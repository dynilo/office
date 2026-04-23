<?php

namespace App\Application\Executions\Services;

use App\Application\Executions\Data\ExecutionRetryDecisionData;
use App\Application\Providers\Exceptions\LlmProviderException;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use Carbon\CarbonImmutable;
use Throwable;

final class ExecutionRetryPolicyService
{
    public function __construct(
        private readonly ExecutionFailureClassifier $classifier,
    ) {
    }

    public function decide(Execution $execution, ?Throwable $throwable = null): ExecutionRetryDecisionData
    {
        $classification = $this->classifier->classify($throwable);
        $isRetriable = $throwable instanceof LlmProviderException
            ? $throwable->retriable
            : false;

        $canRetry = $isRetriable && $execution->retry_count < $execution->max_retries;
        $nextRetryCount = $execution->retry_count + 1;
        $nextAttempt = $execution->attempt + 1;

        return new ExecutionRetryDecisionData(
            classification: $classification,
            shouldRetry: $canRetry,
            nextAttempt: $nextAttempt,
            retryCount: $nextRetryCount,
            nextRetryAt: $canRetry ? $this->nextRetryAt($nextRetryCount) : null,
        );
    }

    private function nextRetryAt(int $retryCount): CarbonImmutable
    {
        $backoff = config('executions.retry.backoff_seconds', [60, 300, 900]);
        $seconds = $backoff[$retryCount - 1] ?? end($backoff) ?: 900;

        return CarbonImmutable::now()->addSeconds((int) $seconds);
    }
}
