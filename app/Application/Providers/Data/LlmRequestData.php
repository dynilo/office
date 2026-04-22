<?php

namespace App\Application\Providers\Data;

use InvalidArgumentException;

final readonly class LlmRequestData
{
    /**
     * @param array<int, LlmMessageData> $messages
     */
    public function __construct(
        public array $messages,
        public ?string $model = null,
        public ?float $temperature = null,
        public ?string $idempotencyKey = null,
        public array $metadata = [],
    ) {
        if ($this->messages === []) {
            throw new InvalidArgumentException('LLM request must contain at least one message.');
        }

        foreach ($this->messages as $message) {
            if (! $message instanceof LlmMessageData) {
                throw new InvalidArgumentException('LLM request messages must be message DTOs.');
            }
        }

        if ($this->temperature !== null && ($this->temperature < 0 || $this->temperature > 2)) {
            throw new InvalidArgumentException('LLM request temperature must be between 0 and 2.');
        }
    }

    public function toArray(string $fallbackModel): array
    {
        return [
            'model' => $this->model ?? $fallbackModel,
            'messages' => array_map(
                static fn (LlmMessageData $message): array => $message->toArray(),
                $this->messages,
            ),
            'temperature' => $this->temperature,
            'metadata' => $this->metadata === [] ? null : $this->metadata,
        ];
    }
}
