<?php

namespace App\Application\Agents\Data;

use InvalidArgumentException;

final readonly class CoordinatorTaskIntentData
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $title,
        public string $summary,
        public string $description,
        public array $payload,
        public string $priority = 'normal',
        public string $source = 'coordinator',
        public ?string $requestedAgentRole = null,
        public string $initialState = 'draft',
    ) {
        if (trim($this->title) === '') {
            throw new InvalidArgumentException('Coordinator task intent title cannot be empty.');
        }

        if (trim($this->description) === '') {
            throw new InvalidArgumentException('Coordinator task intent description cannot be empty.');
        }

        if (! in_array($this->priority, ['low', 'normal', 'high', 'critical'], true)) {
            throw new InvalidArgumentException('Coordinator task intent priority is invalid.');
        }

        if (! in_array($this->initialState, ['draft', 'queued'], true)) {
            throw new InvalidArgumentException('Coordinator task intent initial state is invalid.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toTaskAttributes(): array
    {
        return [
            'title' => $this->title,
            'summary' => $this->summary,
            'description' => $this->description,
            'payload' => $this->payload,
            'priority' => $this->priority,
            'source' => $this->source,
            'requested_agent_role' => $this->requestedAgentRole,
            'initial_state' => $this->initialState,
        ];
    }
}
