<?php

use App\Application\Agents\Services\CoordinatorAgent;
use App\Application\Tasks\Data\TaskDecompositionChildData;
use App\Application\Tasks\Services\TaskDecompositionService;
use App\Domain\Agents\Enums\AgentStatus;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use App\Support\Exceptions\InvalidStateException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('decomposes a coordinator parent task into ordered child tasks', function (): void {
    $coordinator = Agent::factory()->create([
        'role' => CoordinatorAgent::ROLE,
        'status' => AgentStatus::Active,
    ]);
    $parent = app(CoordinatorAgent::class)
        ->coordinate($coordinator, 'Prepare a launch readiness operating plan.')
        ->taskId;

    $parentTask = Task::query()->findOrFail($parent);

    $result = app(TaskDecompositionService::class)->decompose($parentTask, [
        [
            'title' => 'Research launch risks',
            'summary' => 'Collect launch readiness risks.',
            'description' => 'Identify the top market, support, and operational launch risks.',
            'priority' => 'high',
            'requested_agent_role' => 'research',
            'payload' => [
                'focus' => 'risk discovery',
            ],
        ],
        new TaskDecompositionChildData(
            title: 'Draft launch checklist',
            summary: 'Create a concise launch checklist.',
            description: 'Convert readiness findings into an operational checklist.',
            payload: [
                'focus' => 'checklist',
            ],
            requestedAgentRole: 'operations',
        ),
    ]);

    $children = $parentTask->fresh('children')->children;

    expect($result->parent->id)->toBe($parentTask->id)
        ->and($result->children)->toHaveCount(2)
        ->and($result->toArray()['child_count'])->toBe(2)
        ->and($children)->toHaveCount(2)
        ->and($children->pluck('title')->all())->toBe([
            'Research launch risks',
            'Draft launch checklist',
        ])
        ->and($children->pluck('decomposition_index')->all())->toBe([1, 2]);

    expect($children[0]->parent_task_id)->toBe($parentTask->id)
        ->and($children[0]->status)->toBe(TaskStatus::Draft)
        ->and($children[0]->source)->toBe('coordinator_decomposition')
        ->and($children[0]->priority->value)->toBe('high')
        ->and($children[0]->requested_agent_role)->toBe('research')
        ->and($children[0]->payload['type'])->toBe('decomposed_child_task')
        ->and($children[0]->payload['parent_task_id'])->toBe($parentTask->id)
        ->and($children[0]->payload['decomposition_index'])->toBe(1)
        ->and($children[0]->payload['focus'])->toBe('risk discovery');
});

it('blocks decomposition for non coordinator tasks and repeat decomposition', function (): void {
    $regularTask = Task::factory()->create([
        'source' => 'api',
        'status' => TaskStatus::Draft,
        'payload' => [
            'type' => 'external_task',
        ],
    ]);

    expect(fn () => app(TaskDecompositionService::class)->decompose($regularTask, [
        [
            'title' => 'Invalid child',
            'summary' => 'Invalid',
            'description' => 'Invalid child task.',
        ],
    ]))->toThrow(InvalidStateException::class, 'Only coordinator-created parent tasks can be decomposed.');

    $coordinator = Agent::factory()->create([
        'role' => CoordinatorAgent::ROLE,
        'status' => AgentStatus::Active,
    ]);
    $parentId = app(CoordinatorAgent::class)->coordinate($coordinator, 'Coordinate a board memo.')->taskId;
    $parent = Task::query()->findOrFail($parentId);

    app(TaskDecompositionService::class)->decompose($parent, [
        [
            'title' => 'Collect source notes',
            'summary' => 'Collect memo source notes.',
            'description' => 'Gather source notes for the board memo.',
        ],
    ]);

    expect(fn () => app(TaskDecompositionService::class)->decompose($parent->fresh(), [
        [
            'title' => 'Duplicate child',
            'summary' => 'Duplicate',
            'description' => 'Duplicate child task.',
        ],
    ]))->toThrow(InvalidStateException::class, 'Parent task has already been decomposed.');
});

it('enforces deterministic decomposition constraints', function (): void {
    $coordinator = Agent::factory()->create([
        'role' => CoordinatorAgent::ROLE,
        'status' => AgentStatus::Active,
    ]);
    $parentId = app(CoordinatorAgent::class)->coordinate($coordinator, 'Coordinate security readiness.')->taskId;
    $parent = Task::query()->findOrFail($parentId);

    expect(fn () => app(TaskDecompositionService::class)->decompose($parent, []))
        ->toThrow(InvalidArgumentException::class, 'At least one child task is required for decomposition.');

    expect(fn () => app(TaskDecompositionService::class)->decompose($parent, array_fill(0, 11, [
        'title' => 'Too many children',
        'summary' => 'Too many',
        'description' => 'This should exceed the allowed child task count.',
    ])))->toThrow(InvalidArgumentException::class, 'Task decomposition cannot create more than 10 child tasks.');

    $queuedParent = Task::factory()->create([
        'source' => 'coordinator',
        'status' => TaskStatus::Queued,
        'payload' => [
            'type' => 'coordinator_goal_intent',
        ],
    ]);

    expect(fn () => app(TaskDecompositionService::class)->decompose($queuedParent, [
        [
            'title' => 'Late child',
            'summary' => 'Late',
            'description' => 'This child should not be created from a queued parent.',
        ],
    ]))->toThrow(InvalidStateException::class, 'Only draft parent tasks can be decomposed.');
});
