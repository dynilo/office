<?php

namespace App\Application\Communications\Data;

use DateTimeInterface;
use InvalidArgumentException;

final readonly class AgentMessageData
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $senderAgentId,
        public string $recipientAgentId,
        public string $messageType,
        public string $body,
        public ?string $subject = null,
        public ?string $taskId = null,
        public array $metadata = [],
        public ?DateTimeInterface $sentAt = null,
    ) {
        if (trim($this->senderAgentId) === '') {
            throw new InvalidArgumentException('Agent message sender cannot be empty.');
        }

        if (trim($this->recipientAgentId) === '') {
            throw new InvalidArgumentException('Agent message recipient cannot be empty.');
        }

        if ($this->senderAgentId === $this->recipientAgentId) {
            throw new InvalidArgumentException('Agent message sender and recipient must be different agents.');
        }

        if (trim($this->messageType) === '') {
            throw new InvalidArgumentException('Agent message type cannot be empty.');
        }

        if (trim($this->body) === '') {
            throw new InvalidArgumentException('Agent message body cannot be empty.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'sender_agent_id' => $this->senderAgentId,
            'recipient_agent_id' => $this->recipientAgentId,
            'task_id' => $this->taskId,
            'message_type' => $this->messageType,
            'subject' => $this->subject,
            'body' => $this->body,
            'metadata' => $this->metadata,
            'sent_at' => $this->sentAt,
        ];
    }
}
