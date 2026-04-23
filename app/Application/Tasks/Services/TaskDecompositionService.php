<?php

namespace App\Application\Tasks\Services;

use App\Application\Tasks\Actions\CreateTaskAction;
use App\Application\Tasks\Data\TaskDecompositionChildData;
use App\Application\Tasks\Data\TaskDecompositionResultData;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use App\Support\Exceptions\InvalidStateException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class TaskDecompositionService
{
    private const MAX_CHILD_TASKS = 10;

    public function __construct(
        private readonly CreateTaskAction $createTask,
    ) {
    }

    /**
     * @param  array<int, TaskDecompositionChildData|array<string, mixed>>  $children
     */
    public function decompose(Task $parent, array $children): TaskDecompositionResultData
    {
        $this->assertParentCanBeDecomposed($parent);

        $childIntents = $this->normalizeChildren($children);

        return DB::transaction(function () use ($parent, $childIntents): TaskDecompositionResultData {
            $lockedParent = Task::query()
                ->whereKey($parent->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertParentCanBeDecomposed($lockedParent);

            /** @var Collection<int, Task> $created */
            $created = collect();

            foreach ($childIntents as $index => $child) {
                $created->push($this->createTask->execute([
                    'parent_task_id' => $lockedParent->id,
                    'decomposition_index' => $index + 1,
                    'title' => $child->title,
                    'summary' => $child->summary,
                    'description' => $child->description,
                    'payload' => [
                        ...$child->payload,
                        'type' => 'decomposed_child_task',
                        'parent_task_id' => $lockedParent->id,
                        'decomposition_index' => $index + 1,
                    ],
                    'priority' => $child->priority,
                    'source' => 'coordinator_decomposition',
                    'requested_agent_role' => $child->requestedAgentRole,
                    'initial_state' => TaskStatus::Draft->value,
                ]));
            }

            return new TaskDecompositionResultData(
                parent: $lockedParent->fresh(['children']) ?? $lockedParent,
                children: $created->sortBy('decomposition_index')->values(),
            );
        });
    }

    /**
     * @param  array<int, TaskDecompositionChildData|array<string, mixed>>  $children
     * @return array<int, TaskDecompositionChildData>
     */
    private function normalizeChildren(array $children): array
    {
        if ($children === []) {
            throw new InvalidArgumentException('At least one child task is required for decomposition.');
        }

        if (count($children) > self::MAX_CHILD_TASKS) {
            throw new InvalidArgumentException('Task decomposition cannot create more than '.self::MAX_CHILD_TASKS.' child tasks.');
        }

        return array_values(array_map(
            static fn (TaskDecompositionChildData|array $child): TaskDecompositionChildData => $child instanceof TaskDecompositionChildData
                ? $child
                : TaskDecompositionChildData::fromArray($child),
            $children,
        ));
    }

    private function assertParentCanBeDecomposed(Task $parent): void
    {
        if ($parent->parent_task_id !== null) {
            throw new InvalidStateException('Only root coordinator tasks can be decomposed.');
        }

        if ($parent->source !== 'coordinator' || ($parent->payload['type'] ?? null) !== 'coordinator_goal_intent') {
            throw new InvalidStateException('Only coordinator-created parent tasks can be decomposed.');
        }

        if ($parent->status !== TaskStatus::Draft) {
            throw new InvalidStateException('Only draft parent tasks can be decomposed.');
        }

        if ($parent->children()->exists()) {
            throw new InvalidStateException('Parent task has already been decomposed.');
        }
    }
}
