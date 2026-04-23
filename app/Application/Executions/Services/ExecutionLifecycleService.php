<?php

namespace App\Application\Executions\Services;

use App\Application\Executions\Guards\ExecutionTransitionGuard;
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
    ) {
    }

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

        $execution = new Execution([
            'task_id' => $task->id,
            'agent_id' => $task->agent_id,
            'idempotency_key' => $idempotencyKey,
            'status' => ExecutionStatus::Pending,
            'attempt' => 1,
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

        return $execution->refresh()->load('logs');
    }

    public function markRunning(string $executionId): Execution
    {
        $execution = $this->getExecution($executionId);
        $this->guard->assertCanTransition($execution->status, ExecutionStatus::Running);

        $execution->status = ExecutionStatus::Running;
        $execution->started_at = $execution->started_at ?? now();
        $execution = $this->executions->save($execution);

        $this->logs->write($execution, 'info', 'execution.running', []);

        return $execution->refresh()->load('logs');
    }

    public function markSucceeded(
        string $executionId,
        array $outputPayload = [],
        ?array $providerResponse = null,
    ): Execution
    {
        $execution = $this->getExecution($executionId);
        $this->guard->assertCanTransition($execution->status, ExecutionStatus::Succeeded);

        $execution->status = ExecutionStatus::Succeeded;
        $execution->output_payload = $outputPayload;
        $execution->provider_response = $providerResponse;
        $execution->error_message = null;
        $execution->finished_at = now();
        $execution = $this->executions->save($execution);

        $this->logs->write($execution, 'info', 'execution.succeeded', [
            'output_keys' => array_keys($outputPayload),
        ]);

        return $execution->refresh()->load('logs');
    }

    public function markFailed(string $executionId, string $errorMessage, array $context = []): Execution
    {
        $execution = $this->getExecution($executionId);
        $this->guard->assertCanTransition($execution->status, ExecutionStatus::Failed);

        $execution->status = ExecutionStatus::Failed;
        $execution->error_message = $errorMessage;
        $execution->finished_at = now();
        $execution = $this->executions->save($execution);

        $this->logs->write($execution, 'error', 'execution.failed', [
            'error' => $errorMessage,
            ...$context,
        ]);

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
