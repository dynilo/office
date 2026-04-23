<?php

namespace App\Application\Tasks\Services;

use App\Application\Audit\Data\AuditActorData;
use App\Application\Audit\Data\AuditEventData;
use App\Application\Audit\Data\AuditSubjectData;
use App\Application\Audit\Services\AuditEventWriter;
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
        private readonly AuditEventWriter $audit,
    ) {
    }

    public function transition(Task $task, TaskStatus $target): Task
    {
        $task->loadMissing('executions');
        $this->guard->assertCanTransition($task, $target);

        $from = $task->status;
        $task->status = $target;

        $saved = $this->tasks->save($task);

        $this->audit->write(new AuditEventData(
            eventName: 'task.status_changed',
            subject: new AuditSubjectData('task', $saved->id),
            actor: $saved->agent_id !== null ? new AuditActorData('agent', $saved->agent_id) : null,
            source: 'task_lifecycle',
            metadata: [
                'from' => $from->value,
                'to' => $target->value,
            ],
        ));

        return $saved;
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
