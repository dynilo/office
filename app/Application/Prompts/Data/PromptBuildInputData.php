<?php

namespace App\Application\Prompts\Data;

use InvalidArgumentException;

final readonly class PromptBuildInputData
{
    /**
     * @param array<int, string> $capabilities
     * @param array<int, string> $memoryBlocks
     * @param array<int, string> $contextBlocks
     */
    public function __construct(
        public string $agentName,
        public string $agentRole,
        public array $capabilities,
        public string $taskTitle,
        public ?string $taskSummary,
        public ?string $taskDescription,
        public array $taskPayload,
        public array $memoryBlocks = [],
        public array $contextBlocks = [],
    ) {
        if (trim($this->agentName) === '') {
            throw new InvalidArgumentException('Prompt input agent name cannot be empty.');
        }

        if (trim($this->agentRole) === '') {
            throw new InvalidArgumentException('Prompt input agent role cannot be empty.');
        }

        if (trim($this->taskTitle) === '') {
            throw new InvalidArgumentException('Prompt input task title cannot be empty.');
        }
    }
}
