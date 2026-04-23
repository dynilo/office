<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Tasks\Contracts\TaskRepository;
use App\Domain\Tasks\Data\TaskPayloadSummaryData;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Task;

final class EloquentTaskRepository implements TaskRepository
{
    public function findPayloadSummary(string $taskId): ?TaskPayloadSummaryData
    {
        $task = Task::query()->find($taskId);

        if ($task === null) {
            return null;
        }

        return TaskPayloadSummaryData::fromArray([
            'task_id' => $task->id,
            'title' => $task->title,
            'status' => $task->status->value,
            'priority' => $task->priority->value,
            'input_count' => count($task->payload ?? []),
        ]);
    }

    public function exists(string $taskId): bool
    {
        return Task::query()->whereKey($taskId)->exists();
    }

    public function findForAssignment(string $taskId): ?Task
    {
        return Task::query()->with('agent.profile')->find($taskId);
    }

    public function findFirstQueuedByRole(string $role): ?Task
    {
        return Task::query()
            ->where('status', TaskStatus::Queued->value)
            ->where('requested_agent_role', $role)
            ->orderBy('created_at')
            ->orderBy('id')
            ->first();
    }

    public function save(Task $task): Task
    {
        $task->save();

        return $task->refresh();
    }
}
