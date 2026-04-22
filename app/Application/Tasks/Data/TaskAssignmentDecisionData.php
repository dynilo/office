<?php

namespace App\Application\Tasks\Data;

final readonly class TaskAssignmentDecisionData
{
    /**
     * @param array<int, string> $consideredAgentIds
     */
    public function __construct(
        public string $taskId,
        public ?string $agentId,
        public string $outcome,
        public ?string $reasonCode,
        public ?string $matchedBy,
        public array $consideredAgentIds = [],
    ) {
    }

    /**
     * @param array<int, string> $consideredAgentIds
     */
    public static function assigned(
        string $taskId,
        string $agentId,
        string $matchedBy,
        array $consideredAgentIds,
    ): self {
        return new self(
            taskId: $taskId,
            agentId: $agentId,
            outcome: 'assigned',
            reasonCode: null,
            matchedBy: $matchedBy,
            consideredAgentIds: $consideredAgentIds,
        );
    }

    /**
     * @param array<int, string> $consideredAgentIds
     */
    public static function unassigned(
        string $taskId,
        string $reasonCode,
        array $consideredAgentIds = [],
    ): self {
        return new self(
            taskId: $taskId,
            agentId: null,
            outcome: 'unassigned',
            reasonCode: $reasonCode,
            matchedBy: null,
            consideredAgentIds: $consideredAgentIds,
        );
    }

    public function wasAssigned(): bool
    {
        return $this->outcome === 'assigned' && $this->agentId !== null;
    }

    public function toPersistenceContext(): array
    {
        return [
            'considered_agent_ids' => $this->consideredAgentIds,
        ];
    }
}
