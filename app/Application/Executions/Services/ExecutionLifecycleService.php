<?php

namespace App\Application\Executions\Services;

use App\Application\Approvals\Services\HumanApprovalGateService;
use App\Application\Audit\Data\AuditActorData;
use App\Application\Audit\Data\AuditEventData;
use App\Application\Audit\Data\AuditSubjectData;
use App\Application\Audit\Services\AuditEventWriter;
use App\Application\Costs\Services\ProviderUsageCostTracker;
use App\Application\Executions\Guards\ExecutionTransitionGuard;
use App\Application\Policies\Services\PolicyEngineService;
use App\Application\Runtime\Events\ExecutionCreated;
use App\Application\Runtime\Events\ExecutionStatusChanged;
use App\Domain\Executions\Contracts\ExecutionRepository;
use App\Domain\Executions\Enums\ExecutionStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use App\Support\Exceptions\EntityNotFoundException;
use App\Support\Exceptions\InvalidStateException;

final class ExecutionLifecycleService
{
    public function __construct(
        private readonly ExecutionRepository $executions,
        private readonly ExecutionTransitionGuard $guard,
        private readonly ExecutionLogWriter $logs,
        private readonly AuditEventWriter $audit,
        private readonly ProviderUsageCostTracker $costs,
        private readonly PolicyEngineService $policies,
        private readonly HumanApprovalGateService $approvals,
    ) {}

    public function createPendingForAssignedTask(string $taskId, string $idempotencyKey): Execution
    {
        $existing = $this->executions->findByIdempotencyKey($idempotencyKey);

        if ($existing !== null) {
            return $existing->load('logs');
        }

        /** @var Task|null $task */
        $task = Task::query()->find($taskId);

        if ($task === null) {
            throw EntityNotFoundException::for('Task', $taskId);
        }

        if ($task->agent_id === null) {
            throw new InvalidStateException('Task must be assigned before execution can be created.');
        }

        $agent = $task->agent()->first();

        if ($agent === null) {
            throw new InvalidStateException('Assigned task agent could not be loaded for execution.');
        }

        $policyDecision = $this->policies->authorizeExecution($task, $agent);

        if (! $policyDecision->allowed) {
            throw new InvalidStateException($policyDecision->message());
        }

        $this->approvals->assertExecutionCanStart($task, $agent);

        $execution = new Execution([
            'task_id' => $task->id,
            'agent_id' => $task->agent_id,
            'idempotency_key' => $idempotencyKey,
            'status' => ExecutionStatus::Pending,
            'attempt' => 1,
            'retry_count' => 0,
            'max_retries' => (int) config('executions.retry.max_retries', 2),
            'input_snapshot' => [
                'task' => [
                    'title' => $task->title,
                    'summary' => $task->summary,
                    'description' => $task->description,
                    'payload' => $task->payload,
                    'priority' => $task->priority?->value,
                ],
            ],
        ]);

        $execution = $this->executions->save($execution);
        $this->logs->write($execution, 'info', 'execution.pending_created', [
            'idempotency_key' => $idempotencyKey,
        ]);
        $this->audit->write(new AuditEventData(
            eventName: 'execution.created',
            subject: new AuditSubjectData('execution', $execution->id),
            actor: new AuditActorData('agent', $execution->agent_id),
            source: 'execution_lifecycle',
            metadata: [
                'task_id' => $execution->task_id,
                'status' => $execution->status->value,
                'idempotency_key' => $idempotencyKey,
            ],
        ));

        event(new ExecutionCreated(
            executionId: $execution->id,
            taskId: $execution->task_id,
            agentId: $execution->agent_id,
            status: $execution->status->value,
            attempt: $execution->attempt,
        ));

        return $execution->refresh()->load('logs');
    }

    public function markRunning(string $executionId): Execution
    {
        $execution = $this->getExecution($executionId);
        $from = $execution->status;
        $this->guard->assertCanTransition($from, ExecutionStatus::Running);

        $execution->status = ExecutionStatus::Running;
        $execution->started_at = $execution->started_at ?? now();
        $execution = $this->executions->save($execution);

        $this->logs->write($execution, 'info', 'execution.running', []);
        $this->audit->write(new AuditEventData(
            eventName: 'execution.status_changed',
            subject: new AuditSubjectData('execution', $execution->id),
            actor: new AuditActorData('agent', $execution->agent_id),
            source: 'execution_lifecycle',
            metadata: [
                'from' => ExecutionStatus::Pending->value,
                'to' => ExecutionStatus::Running->value,
            ],
        ));

        event(new ExecutionStatusChanged(
            executionId: $execution->id,
            taskId: $execution->task_id,
            agentId: $execution->agent_id,
            from: $from->value,
            to: ExecutionStatus::Running->value,
            attempt: $execution->attempt,
        ));

        return $execution->refresh()->load('logs');
    }

    public function markSucceeded(
        string $executionId,
        array $outputPayload = [],
        ?array $providerResponse = null,
    ): Execution {
        $execution = $this->getExecution($executionId);
        $from = $execution->status;
        $this->guard->assertCanTransition($from, ExecutionStatus::Succeeded);

        $execution->status = ExecutionStatus::Succeeded;
        $execution->output_payload = $outputPayload;
        $execution->provider_response = $providerResponse;
        $execution->error_message = null;
        $execution->finished_at = now();
        $execution = $this->executions->save($execution);
        $usage = $providerResponse === null
            ? null
            : $this->costs->recordForExecution($execution, $providerResponse);

        $this->logs->write($execution, 'info', 'execution.succeeded', [
            'output_keys' => array_keys($outputPayload),
            'provider_usage_record_id' => $usage?->id,
        ]);
        $this->audit->write(new AuditEventData(
            eventName: 'execution.status_changed',
            subject: new AuditSubjectData('execution', $execution->id),
            actor: new AuditActorData('agent', $execution->agent_id),
            source: 'execution_lifecycle',
            metadata: [
                'from' => ExecutionStatus::Running->value,
                'to' => ExecutionStatus::Succeeded->value,
                'output_keys' => array_keys($outputPayload),
                'provider_usage_record_id' => $usage?->id,
            ],
        ));

        event(new ExecutionStatusChanged(
            executionId: $execution->id,
            taskId: $execution->task_id,
            agentId: $execution->agent_id,
            from: $from->value,
            to: ExecutionStatus::Succeeded->value,
            attempt: $execution->attempt,
        ));

        return $execution->refresh()->load('logs');
    }

    public function markFailed(
        string $executionId,
        string $errorMessage,
        array $context = [],
        ?string $failureClassification = null,
        mixed $nextRetryAt = null,
    ): Execution {
        $execution = $this->getExecution($executionId);
        $from = $execution->status;
        $this->guard->assertCanTransition($from, ExecutionStatus::Failed);

        $execution->status = ExecutionStatus::Failed;
        $execution->error_message = $errorMessage;
        $execution->failure_classification = $failureClassification;
        $execution->finished_at = now();
        $execution->next_retry_at = $nextRetryAt;
        $execution = $this->executions->save($execution);

        $this->logs->write($execution, 'error', 'execution.failed', [
            'error' => $errorMessage,
            ...$context,
        ]);
        $this->audit->write(new AuditEventData(
            eventName: 'execution.status_changed',
            subject: new AuditSubjectData('execution', $execution->id),
            actor: new AuditActorData('agent', $execution->agent_id),
            source: 'execution_lifecycle',
            metadata: [
                'from' => $from->value,
                'to' => ExecutionStatus::Failed->value,
                'error' => $errorMessage,
                'failure_classification' => $failureClassification,
            ],
        ));

        event(new ExecutionStatusChanged(
            executionId: $execution->id,
            taskId: $execution->task_id,
            agentId: $execution->agent_id,
            from: $from->value,
            to: ExecutionStatus::Failed->value,
            attempt: $execution->attempt,
            failureClassification: $failureClassification,
        ));

        return $execution->refresh()->load('logs');
    }

    private function getExecution(string $executionId): Execution
    {
        $execution = $this->executions->find($executionId);

        if ($execution === null) {
            throw EntityNotFoundException::for('Execution', $executionId);
        }

        return $execution;
    }
}
