<?php

namespace App\Application\Tasks\Services;

use App\Application\Agents\Services\ResearchAnalystAgent;
use App\Application\Executions\Services\ExecutionLifecycleService;
use App\Application\Executions\Services\ExecutionRetryService;
use App\Application\Providers\Exceptions\LlmProviderException;
use App\Domain\Tasks\Contracts\TaskRepository;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use App\Support\Exceptions\InvalidStateException;

final class RunQueuedResearchTaskService
{
    public function __construct(
        private readonly TaskRepository $tasks,
        private readonly AssignTaskService $assignment,
        private readonly ExecutionLifecycleService $executions,
        private readonly ExecutionRetryService $retries,
        private readonly ResearchAnalystAgent $researchAgent,
        private readonly TaskLifecycleService $taskLifecycle,
    ) {
    }

    public function runOne(): ?Task
    {
        $task = $this->tasks->findFirstQueuedByRole('research');

        if ($task === null) {
            return null;
        }

        $decision = $this->assignment->assign($task->id);

        if (! $decision->wasAssigned()) {
            throw new InvalidStateException('Queued research task could not be assigned.');
        }

        $task = $this->tasks->findForAssignment($task->id);

        if ($task === null || $task->agent === null) {
            throw new InvalidStateException('Assigned research task could not be reloaded.');
        }

        $execution = $this->executions->createPendingForAssignedTask(
            taskId: $task->id,
            idempotencyKey: 'task-'.$task->id.'-attempt-1',
        );

        $task = $this->taskLifecycle->transition($task, TaskStatus::InProgress);

        dispatch_sync(new \App\Application\Executions\Jobs\StartExecutionJob($execution->id));

        try {
            $result = $this->researchAgent->run($task, $task->agent);

            $this->executions->markSucceeded(
                executionId: $execution->id,
                outputPayload: $result['structured_result'],
                providerResponse: $result['raw_response'] + [
                    'prompt_messages' => $result['prompt_messages'],
                ],
            );

            return $this->taskLifecycle->transition($task->fresh(['executions']), TaskStatus::Completed);
        } catch (LlmProviderException $exception) {
            $decision = $this->retries->handleFailure($execution->id, $exception->getMessage(), $exception, [
                'provider' => $exception->provider,
                'status_code' => $exception->statusCode,
                'error_code' => $exception->errorCode,
            ]);

            $this->taskLifecycle->transition($task->fresh(['executions']), TaskStatus::Failed);

            throw $exception;
        }
    }
}
