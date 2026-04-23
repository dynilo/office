<?php

namespace App\Application\Runtime\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

final readonly class ExecutionStatusChanged implements ShouldBroadcastNow
{
    public function __construct(
        public string $executionId,
        public string $taskId,
        public string $agentId,
        public string $from,
        public string $to,
        public int $attempt,
        public ?string $failureClassification = null,
    ) {
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel('runtime')];
    }

    public function broadcastAs(): string
    {
        return 'execution.status.changed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'type' => $this->broadcastAs(),
            'execution_id' => $this->executionId,
            'task_id' => $this->taskId,
            'agent_id' => $this->agentId,
            'from' => $this->from,
            'to' => $this->to,
            'attempt' => $this->attempt,
            'failure_classification' => $this->failureClassification,
        ];
    }

    public function broadcastWhen(): bool
    {
        return config('broadcasting.default') !== 'null';
    }
}
