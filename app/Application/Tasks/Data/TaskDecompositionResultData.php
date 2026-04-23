<?php

namespace App\Application\Tasks\Data;

use App\Infrastructure\Persistence\Eloquent\Models\Task;
use Illuminate\Support\Collection;

final readonly class TaskDecompositionResultData
{
    /**
     * @param  Collection<int, Task>  $children
     */
    public function __construct(
        public Task $parent,
        public Collection $children,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'parent_task_id' => $this->parent->id,
            'child_count' => $this->children->count(),
            'children' => $this->children
                ->map(fn (Task $task): array => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'state' => $task->status?->value,
                    'priority' => $task->priority?->value,
                    'requested_agent_role' => $task->requested_agent_role,
                    'decomposition_index' => $task->decomposition_index,
                ])
                ->values()
                ->all(),
        ];
    }
}
