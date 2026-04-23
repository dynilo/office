<?php

namespace App\Application\Agents\Services;

use App\Application\Agents\Data\CoordinatorReportData;
use App\Application\Agents\Data\CoordinatorTaskIntentData;
use App\Application\Tasks\Actions\CreateTaskAction;
use App\Domain\Agents\Enums\AgentStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Support\Exceptions\InvalidStateException;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class CoordinatorAgent
{
    public const ROLE = 'coordinator';

    public function __construct(
        private readonly CreateTaskAction $createTask,
    ) {
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function coordinate(Agent $coordinator, string $goal, array $context = []): CoordinatorReportData
    {
        $goal = trim($goal);

        if ($goal === '') {
            throw new InvalidArgumentException('Coordinator goal cannot be empty.');
        }

        $this->assertCanCoordinate($coordinator);

        $intent = $this->prepareTaskIntent($coordinator, $goal, $context);
        $task = $this->createTask->execute($intent->toTaskAttributes());

        return new CoordinatorReportData(
            goal: $goal,
            status: 'intent_created',
            summary: 'Coordinator accepted the goal and created a draft internal task intent.',
            coordinatorProfile: $this->profileFor($coordinator),
            taskIntent: [
                ...$intent->toTaskAttributes(),
                'task_id' => $task->id,
                'state' => $task->status?->value,
            ],
            taskId: $task->id,
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function prepareTaskIntent(Agent $coordinator, string $goal, array $context = []): CoordinatorTaskIntentData
    {
        $goal = trim($goal);

        if ($goal === '') {
            throw new InvalidArgumentException('Coordinator goal cannot be empty.');
        }

        $this->assertCanCoordinate($coordinator);

        $title = Str::limit($goal, 120, '');

        return new CoordinatorTaskIntentData(
            title: $title,
            summary: 'Coordinator intake for a high-level user goal.',
            description: $goal,
            payload: [
                'type' => 'coordinator_goal_intent',
                'goal' => $goal,
                'context' => $context,
                'coordinator' => [
                    'agent_id' => $coordinator->id,
                    'code' => $coordinator->code,
                    'role' => $coordinator->role,
                ],
                'planning_scope' => [
                    'decomposition_enabled' => false,
                    'multi_agent_messaging_enabled' => false,
                ],
            ],
        );
    }

    public function supports(Agent $agent): bool
    {
        return $agent->role === self::ROLE;
    }

    /**
     * @return array<string, mixed>
     */
    public function profileFor(Agent $agent): array
    {
        $agent->loadMissing('profile');

        return [
            'agent_id' => $agent->id,
            'name' => $agent->name,
            'code' => $agent->code,
            'role' => $agent->role,
            'capabilities' => $agent->capabilities ?? [],
            'model_preference' => $agent->profile?->model_preference,
            'temperature_policy' => $agent->profile?->temperature_policy,
            'instructions' => $agent->profile?->instructions ?? [],
        ];
    }

    private function assertCanCoordinate(Agent $coordinator): void
    {
        if (! $this->supports($coordinator)) {
            throw new InvalidStateException('Coordinator agent role must be [coordinator].');
        }

        if ($coordinator->status !== AgentStatus::Active) {
            throw new InvalidStateException('Coordinator agent must be active.');
        }
    }
}
