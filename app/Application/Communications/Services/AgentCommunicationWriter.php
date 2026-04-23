<?php

namespace App\Application\Communications\Services;

use App\Application\Communications\Data\AgentMessageData;
use App\Infrastructure\Persistence\Eloquent\Models\AgentCommunicationLog;

final class AgentCommunicationWriter
{
    public function write(AgentMessageData $message): AgentCommunicationLog
    {
        return AgentCommunicationLog::query()->create([
            'sender_agent_id' => $message->senderAgentId,
            'recipient_agent_id' => $message->recipientAgentId,
            'task_id' => $message->taskId,
            'message_type' => $message->messageType,
            'subject' => $message->subject,
            'body' => $message->body,
            'metadata' => $message->metadata,
            'sent_at' => $message->sentAt ?? now(),
        ]);
    }
}
