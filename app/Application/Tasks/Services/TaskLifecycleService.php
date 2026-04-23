<?php

namespace App\Application\Tasks\Services;

use App\Application\Tasks\Guards\TaskTransitionGuard;
use App\Domain\Tasks\Contracts\TaskRepository;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use App\Support\Exceptions\EntityNotFoundException;

final class TaskLifecycleService
{
    public function __construct(
        private readonly TaskRepository $tasks,
        private readonly TaskTransitionGuard $guard,
    ) {
    }

    public function transition(Task $task, TaskStatus $target): Task
    {
        $task->loadMissing('executions');
        $this->guard->assertCanTransition($task, $target);

        $task->status = $target;

        return $this->tasks->save($task);
    }

    public function transitionById(string $taskId, TaskStatus $target): Task
    {
        $task = $this->tasks->findForAssignment($taskId);

        if ($task === null) {
            throw EntityNotFoundException::for('Task', $taskId);
        }

        return $this->transition($task, $target);
    }
}
