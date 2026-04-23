<?php

namespace App\Application\Executions\Services;

use App\Application\Executions\Data\ExecutionRetryDecisionData;
use App\Application\Runtime\Events\ExecutionCreated;
use App\Domain\Executions\Contracts\ExecutionRepository;
use App\Domain\Executions\Enums\ExecutionStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use App\Support\Exceptions\EntityNotFoundException;
use Throwable;

final class ExecutionRetryService
{
    public function __construct(
        private readonly ExecutionRepository $executions,
        private readonly ExecutionLifecycleService $lifecycle,
        private readonly ExecutionLogWriter $logs,
        private readonly ExecutionRetryPolicyService $policy,
        private readonly DeadLetterService $deadLetters,
    ) {}

    public function handleFailure(string $executionId, string $errorMessage, ?Throwable $throwable = null, array $context = []): ExecutionRetryDecisionData
    {
        $execution = $this->executions->find($executionId);

        if ($execution === null) {
            throw EntityNotFoundException::for('Execution', $executionId);
        }

        $decision = $this->policy->decide($execution, $throwable);

        $failed = $this->lifecycle->markFailed(
            executionId: $execution->id,
            errorMessage: $errorMessage,
            context: [
                ...$context,
                'failure_classification' => $decision->classification,
                'retry_scheduled' => $decision->shouldRetry,
            ],
            failureClassification: $decision->classification,
            nextRetryAt: $decision->nextRetryAt,
        );

        if (! $decision->shouldRetry) {
            $this->deadLetters->captureForExecution(
                execution: $failed,
                reasonCode: $decision->classification,
                errorMessage: $errorMessage,
                payload: [
                    ...$context,
                    'retry_count' => $decision->retryCount,
                    'next_attempt' => $decision->nextAttempt,
                    'retry_exhausted' => $failed->retry_count >= $failed->max_retries,
                ],
            );

            $this->logs->write($failed, 'warning', 'execution.dead_lettered', [
                'reason_code' => $decision->classification,
            ]);

            return $decision;
        }

        $retryExecution = new Execution([
            'organization_id' => $failed->organization_id,
            'task_id' => $failed->task_id,
            'agent_id' => $failed->agent_id,
            'retry_of_execution_id' => $failed->id,
            'idempotency_key' => 'task-'.$failed->task_id.'-attempt-'.$decision->nextAttempt,
            'status' => ExecutionStatus::Pending,
            'attempt' => $decision->nextAttempt,
            'retry_count' => $decision->retryCount,
            'max_retries' => $failed->max_retries,
            'next_retry_at' => $decision->nextRetryAt,
            'input_snapshot' => $failed->input_snapshot,
        ]);

        $retryExecution = $this->executions->save($retryExecution);

        $this->logs->write($failed, 'warning', 'execution.retry_scheduled', [
            'next_attempt' => $decision->nextAttempt,
            'retry_count' => $decision->retryCount,
            'next_retry_at' => $decision->nextRetryAt?->toIso8601String(),
            'failure_classification' => $decision->classification,
        ]);

        $this->logs->write($retryExecution, 'info', 'execution.pending_created', [
            'retry_of_execution_id' => $failed->id,
            'next_retry_at' => $decision->nextRetryAt?->toIso8601String(),
        ]);

        event(new ExecutionCreated(
            executionId: $retryExecution->id,
            taskId: $retryExecution->task_id,
            agentId: $retryExecution->agent_id,
            status: $retryExecution->status->value,
            attempt: $retryExecution->attempt,
        ));

        return $decision;
    }
}
