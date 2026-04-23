<?php

namespace App\Application\Tasks\Actions;

use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Task;

final class CreateTaskAction
{
    public function execute(array $attributes): Task
    {
        $status = TaskStatus::from($attributes['initial_state']);

        return Task::query()->create([
            'parent_task_id' => $attributes['parent_task_id'] ?? null,
            'decomposition_index' => $attributes['decomposition_index'] ?? null,
            'title' => $attributes['title'],
            'summary' => $attributes['summary'] ?? null,
            'description' => $attributes['description'] ?? null,
            'payload' => $attributes['payload'],
            'priority' => $attributes['priority'],
            'source' => $attributes['source'] ?? null,
            'requested_agent_role' => $attributes['requested_agent_role'] ?? null,
            'due_at' => $attributes['due_at'] ?? null,
            'status' => $status,
            'submitted_at' => now(),
        ]);
    }
}
