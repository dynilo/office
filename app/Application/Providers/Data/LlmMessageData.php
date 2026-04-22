<?php

namespace App\Application\Providers\Data;

use InvalidArgumentException;

final readonly class LlmMessageData
{
    public function __construct(
        public string $role,
        public string $content,
    ) {
        if (trim($this->role) === '') {
            throw new InvalidArgumentException('LLM message role cannot be empty.');
        }

        if (trim($this->content) === '') {
            throw new InvalidArgumentException('LLM message content cannot be empty.');
        }
    }

    public function toArray(): array
    {
        return [
            'role' => $this->role,
            'content' => $this->content,
        ];
    }
}
