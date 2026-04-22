<?php

namespace App\Application\Providers\Data;

final readonly class LlmResponseData
{
    public function __construct(
        public string $provider,
        public string $responseId,
        public string $model,
        public string $content,
        public ?string $finishReason,
        public ?int $inputTokens,
        public ?int $outputTokens,
        public ?string $requestId,
    ) {
    }
}
