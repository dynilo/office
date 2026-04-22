<?php

namespace App\Application\Prompts\Data;

use InvalidArgumentException;

final readonly class PromptSectionData
{
    public function __construct(
        public string $name,
        public string $content,
        public string $role,
        public int $priority = 0,
    ) {
        if (trim($this->name) === '') {
            throw new InvalidArgumentException('Prompt section name cannot be empty.');
        }

        if (trim($this->content) === '') {
            throw new InvalidArgumentException('Prompt section content cannot be empty.');
        }

        if (trim($this->role) === '') {
            throw new InvalidArgumentException('Prompt section role cannot be empty.');
        }
    }

    public function toBlock(): string
    {
        return strtoupper($this->name).":\n".$this->content;
    }
}
