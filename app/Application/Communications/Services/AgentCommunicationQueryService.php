<?php

namespace App\Application\Communications\Services;

use App\Infrastructure\Persistence\Eloquent\Models\AgentCommunicationLog;
use Illuminate\Database\Eloquent\Collection;

final class AgentCommunicationQueryService
{
    /**
     * @return Collection<int, AgentCommunicationLog>
     */
    public function betweenAgents(string $firstAgentId, string $secondAgentId): Collection
    {
        return AgentCommunicationLog::query()
            ->with(['sender', 'recipient', 'task'])
            ->where(function ($query) use ($firstAgentId, $secondAgentId): void {
                $query
                    ->where('sender_agent_id', $firstAgentId)
                    ->where('recipient_agent_id', $secondAgentId);
            })
            ->orWhere(function ($query) use ($firstAgentId, $secondAgentId): void {
                $query
                    ->where('sender_agent_id', $secondAgentId)
                    ->where('recipient_agent_id', $firstAgentId);
            })
            ->orderBy('sent_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, AgentCommunicationLog>
     */
    public function forTask(string $taskId): Collection
    {
        return AgentCommunicationLog::query()
            ->with(['sender', 'recipient', 'task'])
            ->where('task_id', $taskId)
            ->orderBy('sent_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, AgentCommunicationLog>
     */
    public function forAgent(string $agentId): Collection
    {
        return AgentCommunicationLog::query()
            ->with(['sender', 'recipient', 'task'])
            ->where('sender_agent_id', $agentId)
            ->orWhere('recipient_agent_id', $agentId)
            ->orderByDesc('sent_at')
            ->orderByDesc('id')
            ->get();
    }
}
