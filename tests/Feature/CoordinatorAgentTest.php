<?php

use App\Application\Agents\Services\CoordinatorAgent;
use App\Domain\Agents\Enums\AgentStatus;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\AgentProfile;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use App\Support\Exceptions\InvalidStateException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('accepts a high-level goal and creates a draft internal task intent', function (): void {
    $coordinator = Agent::factory()->create([
        'name' => 'Office Coordinator',
        'code' => 'office_coordinator',
        'role' => CoordinatorAgent::ROLE,
        'status' => AgentStatus::Active,
        'capabilities' => ['coordination', 'intake', 'reporting'],
    ]);
    AgentProfile::factory()->for($coordinator)->create([
        'model_preference' => 'gpt-5.4',
        'instructions' => [
            'Clarify user goals before routing work.',
        ],
    ]);

    $report = app(CoordinatorAgent::class)->coordinate(
        coordinator: $coordinator,
        goal: 'Launch a weekly market intelligence briefing for enterprise AI competitors.',
        context: [
            'requester' => 'ops',
            'cadence' => 'weekly',
        ],
    );

    $task = Task::query()->sole();

    expect($report->status)->toBe('intent_created')
        ->and($report->taskId)->toBe($task->id)
        ->and($report->summary)->toContain('created a draft internal task intent')
        ->and($report->coordinatorProfile['role'])->toBe(CoordinatorAgent::ROLE)
        ->and($report->coordinatorProfile['model_preference'])->toBe('gpt-5.4')
        ->and($report->taskIntent['state'])->toBe(TaskStatus::Draft->value)
        ->and($report->toArray()['task_id'])->toBe($task->id);

    expect($task->status)->toBe(TaskStatus::Draft)
        ->and($task->source)->toBe('coordinator')
        ->and($task->requested_agent_role)->toBeNull()
        ->and($task->payload['type'])->toBe('coordinator_goal_intent')
        ->and($task->payload['goal'])->toBe('Launch a weekly market intelligence briefing for enterprise AI competitors.')
        ->and($task->payload['context']['cadence'])->toBe('weekly')
        ->and($task->payload['coordinator']['agent_id'])->toBe($coordinator->id)
        ->and($task->payload['planning_scope']['decomposition_enabled'])->toBeFalse()
        ->and($task->payload['planning_scope']['multi_agent_messaging_enabled'])->toBeFalse();
});

it('can prepare task intent without persisting it', function (): void {
    $coordinator = Agent::factory()->create([
        'role' => CoordinatorAgent::ROLE,
        'status' => AgentStatus::Active,
    ]);

    $intent = app(CoordinatorAgent::class)->prepareTaskIntent(
        coordinator: $coordinator,
        goal: 'Plan a customer onboarding operating rhythm.',
    );

    expect(Task::query()->count())->toBe(0)
        ->and($intent->initialState)->toBe('draft')
        ->and($intent->source)->toBe('coordinator')
        ->and($intent->payload['goal'])->toBe('Plan a customer onboarding operating rhythm.')
        ->and($intent->toTaskAttributes()['initial_state'])->toBe('draft');
});

it('requires an active coordinator role agent', function (): void {
    $researchAgent = Agent::factory()->create([
        'role' => 'research',
        'status' => AgentStatus::Active,
    ]);
    $inactiveCoordinator = Agent::factory()->create([
        'role' => CoordinatorAgent::ROLE,
        'status' => AgentStatus::Inactive,
    ]);

    expect(fn () => app(CoordinatorAgent::class)->coordinate($researchAgent, 'Coordinate this goal.'))
        ->toThrow(InvalidStateException::class, 'Coordinator agent role must be [coordinator].');

    expect(fn () => app(CoordinatorAgent::class)->coordinate($inactiveCoordinator, 'Coordinate this goal.'))
        ->toThrow(InvalidStateException::class, 'Coordinator agent must be active.');
});
