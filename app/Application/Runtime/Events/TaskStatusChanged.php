<?php

namespace App\Application\Runtime\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

final readonly class TaskStatusChanged implements ShouldBroadcastNow
{
    public function __construct(
        public string $taskId,
        public ?string $agentId,
        public string $from,
        public string $to,
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
        return 'task.status.changed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'type' => $this->broadcastAs(),
            'task_id' => $this->taskId,
            'agent_id' => $this->agentId,
            'from' => $this->from,
            'to' => $this->to,
        ];
    }

    public function broadcastWhen(): bool
    {
        return config('broadcasting.default') !== 'null';
    }
}
