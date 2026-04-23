<?php

namespace App\Application\Agents\Data;

final readonly class CoordinatorReportData
{
    /**
     * @param  array<string, mixed>  $coordinatorProfile
     * @param  array<string, mixed>  $taskIntent
     */
    public function __construct(
        public string $goal,
        public string $status,
        public string $summary,
        public array $coordinatorProfile,
        public array $taskIntent,
        public ?string $taskId,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'goal' => $this->goal,
            'status' => $this->status,
            'summary' => $this->summary,
            'coordinator_profile' => $this->coordinatorProfile,
            'task_intent' => $this->taskIntent,
            'task_id' => $this->taskId,
        ];
    }
}
