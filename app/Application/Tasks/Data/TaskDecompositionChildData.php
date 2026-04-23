<?php

namespace App\Application\Tasks\Data;

use InvalidArgumentException;

final readonly class TaskDecompositionChildData
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $title,
        public string $summary,
        public string $description,
        public array $payload = [],
        public string $priority = 'normal',
        public ?string $requestedAgentRole = null,
    ) {
        if (trim($this->title) === '') {
            throw new InvalidArgumentException('Decomposition child title cannot be empty.');
        }

        if (trim($this->description) === '') {
            throw new InvalidArgumentException('Decomposition child description cannot be empty.');
        }

        if (! in_array($this->priority, ['low', 'normal', 'high', 'critical'], true)) {
            throw new InvalidArgumentException('Decomposition child priority is invalid.');
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        return new self(
            title: (string) ($attributes['title'] ?? ''),
            summary: (string) ($attributes['summary'] ?? ''),
            description: (string) ($attributes['description'] ?? ''),
            payload: is_array($attributes['payload'] ?? null) ? $attributes['payload'] : [],
            priority: (string) ($attributes['priority'] ?? 'normal'),
            requestedAgentRole: isset($attributes['requested_agent_role'])
                ? (string) $attributes['requested_agent_role']
                : null,
        );
    }
}
