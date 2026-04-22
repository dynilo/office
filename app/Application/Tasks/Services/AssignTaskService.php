<?php

namespace App\Application\Tasks\Services;

use App\Application\Tasks\Data\TaskAssignmentDecisionData;
use App\Domain\Agents\Contracts\AgentRepository;
use App\Domain\Tasks\Contracts\TaskRepository;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\TaskAssignmentDecision;
use App\Support\Exceptions\EntityNotFoundException;

final class AssignTaskService
{
    public function __construct(
        private readonly TaskRepository $tasks,
        private readonly AgentRepository $agents,
    ) {
    }

    public function assign(string $taskId): TaskAssignmentDecisionData
    {
        $task = $this->tasks->findForAssignment($taskId);

        if ($task === null) {
            throw EntityNotFoundException::for('Task', $taskId);
        }

        if ($task->status !== TaskStatus::Queued) {
            return $this->persist(TaskAssignmentDecisionData::unassigned(
                taskId: $task->id,
                reasonCode: 'task_not_queued',
            ));
        }

        $activeAgents = $this->agents->findActiveForAssignment();

        if ($activeAgents->isEmpty()) {
            return $this->persist(TaskAssignmentDecisionData::unassigned(
                taskId: $task->id,
                reasonCode: 'no_active_agent',
            ));
        }

        $consideredAgentIds = $activeAgents->pluck('id')->all();
        $roleCandidates = $activeAgents;

        if ($task->requested_agent_role !== null) {
            $roleCandidates = $this->agents->findActiveForAssignment($task->requested_agent_role);

            if ($roleCandidates->isEmpty()) {
                return $this->persist(TaskAssignmentDecisionData::unassigned(
                    taskId: $task->id,
                    reasonCode: 'role_mismatch',
                    consideredAgentIds: $consideredAgentIds,
                ));
            }

            $consideredAgentIds = $roleCandidates->pluck('id')->all();
        }

        $requiredCapabilities = collect($task->payload['required_capabilities'] ?? [])
            ->filter(fn (mixed $capability): bool => is_string($capability) && $capability !== '')
            ->values();

        $capabilityCandidates = $roleCandidates;

        if ($requiredCapabilities->isNotEmpty()) {
            $capabilityCandidates = $roleCandidates
                ->filter(function ($agent) use ($requiredCapabilities): bool {
                    $agentCapabilities = collect($agent->capabilities ?? []);

                    return $requiredCapabilities->every(
                        fn (string $capability): bool => $agentCapabilities->contains($capability),
                    );
                })
                ->values();

            if ($capabilityCandidates->isEmpty()) {
                return $this->persist(TaskAssignmentDecisionData::unassigned(
                    taskId: $task->id,
                    reasonCode: 'capability_mismatch',
                    consideredAgentIds: $consideredAgentIds,
                ));
            }
        }

        /** @var \App\Infrastructure\Persistence\Eloquent\Models\Agent $assignedAgent */
        $assignedAgent = $capabilityCandidates->first();

        $task->agent()->associate($assignedAgent);
        $this->tasks->save($task);

        $matchedBy = $task->requested_agent_role !== null && $requiredCapabilities->isNotEmpty()
            ? 'role_then_capability'
            : ($task->requested_agent_role !== null ? 'role' : ($requiredCapabilities->isNotEmpty() ? 'capability' : 'first_active'));

        return $this->persist(TaskAssignmentDecisionData::assigned(
            taskId: $task->id,
            agentId: $assignedAgent->id,
            matchedBy: $matchedBy,
            consideredAgentIds: $capabilityCandidates->pluck('id')->all(),
        ));
    }

    private function persist(TaskAssignmentDecisionData $decision): TaskAssignmentDecisionData
    {
        TaskAssignmentDecision::query()->create([
            'task_id' => $decision->taskId,
            'agent_id' => $decision->agentId,
            'outcome' => $decision->outcome,
            'reason_code' => $decision->reasonCode,
            'matched_by' => $decision->matchedBy,
            'context' => $decision->toPersistenceContext(),
        ]);

        return $decision;
    }
}
