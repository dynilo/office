<?php

namespace App\Application\Executions\Guards;

use App\Domain\Executions\Enums\ExecutionStatus;
use App\Support\Exceptions\InvalidStateException;

final class ExecutionTransitionGuard
{
    public function assertCanTransition(ExecutionStatus $from, ExecutionStatus $to): void
    {
        $valid = match ($from) {
            ExecutionStatus::Pending => in_array($to, [ExecutionStatus::Running], true),
            ExecutionStatus::Running => in_array($to, [ExecutionStatus::Succeeded, ExecutionStatus::Failed], true),
            ExecutionStatus::Succeeded, ExecutionStatus::Failed, ExecutionStatus::Cancelled => false,
        };

        if (! $valid) {
            throw InvalidStateException::forTransition('Execution', $from->value, $to->value);
        }
    }
}
