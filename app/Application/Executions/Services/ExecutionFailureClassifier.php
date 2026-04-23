<?php

namespace App\Application\Executions\Services;

use App\Application\Providers\Exceptions\LlmProviderException;
use Throwable;

final class ExecutionFailureClassifier
{
    public function classify(?Throwable $throwable): string
    {
        if ($throwable instanceof LlmProviderException) {
            if ($throwable->errorCode === 'transport_error') {
                return 'transport_failure';
            }

            if ($throwable->retriable) {
                return 'transient_provider_failure';
            }

            return 'provider_validation_failure';
        }

        return 'unknown_failure';
    }
}
