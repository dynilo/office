<?php

namespace App\Application\Tasks\Guards;

use App\Domain\Executions\Enums\ExecutionStatus;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use App\Support\Exceptions\InvalidStateException;

final class TaskTransitionGuard
{
    public function assertCanTransition(Task $task, TaskStatus $target): void
    {
        if (! $task->status->canTransitionTo($target)) {
            throw InvalidStateException::forTransition('Task', $task->status->value, $target->value);
        }

        match ($target) {
            TaskStatus::Queued => $this->assertQueueable($task),
            TaskStatus::InProgress => $this->assertStartable($task),
            TaskStatus::Completed => $this->assertCompletable($task),
            TaskStatus::Failed => $this->assertFailible($task),
            TaskStatus::Cancelled => $this->assertCancellable($task),
            TaskStatus::Draft, TaskStatus::Pending => null,
        };
    }

    private function assertQueueable(Task $task): void
    {
        if (trim($task->title) === '') {
            throw new InvalidStateException('Task must have a title before it can be queued.');
        }

        if (! is_array($task->payload) || $task->payload === []) {
            throw new InvalidStateException('Task must have payload before it can be queued.');
        }
    }

    private function assertStartable(Task $task): void
    {
        if ($task->agent_id === null) {
            throw new InvalidStateException('Task must be assigned before it can move to in_progress.');
        }
    }

    private function assertCompletable(Task $task): void
    {
        if ($task->agent_id === null) {
            throw new InvalidStateException('Task must be assigned before it can be completed.');
        }

        $latestExecution = $task->executions()->latest('created_at')->latest('id')->first();

        if ($latestExecution === null || $latestExecution->status !== ExecutionStatus::Succeeded) {
            throw new InvalidStateException('Task requires a succeeded execution before completion.');
        }
    }

    private function assertFailible(Task $task): void
    {
        if ($task->agent_id === null) {
            throw new InvalidStateException('Task must be assigned before it can fail.');
        }

        $latestFailedExecution = $task->executions()
            ->where('status', ExecutionStatus::Failed)
            ->latest('created_at')
            ->latest('id')
            ->first();

        if ($latestFailedExecution === null) {
            throw new InvalidStateException('Task requires a failed execution before it can fail.');
        }
    }

    private function assertCancellable(Task $task): void
    {
        if ($task->status->isTerminal()) {
            throw InvalidStateException::forTransition('Task', $task->status->value, TaskStatus::Cancelled->value);
        }
    }
}
