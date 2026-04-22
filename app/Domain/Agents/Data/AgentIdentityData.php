<?php

namespace App\Domain\Agents\Data;

use App\Domain\Agents\Enums\AgentStatus;
use InvalidArgumentException;

final readonly class AgentIdentityData
{
    public function __construct(
        public string $agentId,
        public string $name,
        public string $version,
        public AgentStatus $status,
    ) {
        if (trim($this->agentId) === '') {
            throw new InvalidArgumentException('Agent ID cannot be empty.');
        }

        if (trim($this->name) === '') {
            throw new InvalidArgumentException('Agent name cannot be empty.');
        }

        if (trim($this->version) === '') {
            throw new InvalidArgumentException('Agent version cannot be empty.');
        }
    }

    public static function fromArray(array $attributes): self
    {
        return new self(
            agentId: (string) ($attributes['agent_id'] ?? ''),
            name: (string) ($attributes['name'] ?? ''),
            version: (string) ($attributes['version'] ?? ''),
            status: AgentStatus::from($attributes['status']),
        );
    }

    public function toArray(): array
    {
        return [
            'agent_id' => $this->agentId,
            'name' => $this->name,
            'version' => $this->version,
            'status' => $this->status->value,
        ];
    }
}
