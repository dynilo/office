<?php

namespace App\Application\Runtime\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

final readonly class ExecutionCreated implements ShouldBroadcastNow
{
    public function __construct(
        public string $executionId,
        public string $taskId,
        public string $agentId,
        public string $status,
        public int $attempt,
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
        return 'execution.created';
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
            'status' => $this->status,
            'attempt' => $this->attempt,
        ];
    }

    public function broadcastWhen(): bool
    {
        return config('broadcasting.default') !== 'null';
    }
}
