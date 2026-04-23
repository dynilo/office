<?php

namespace App\Application\Prompts\Data;

use App\Application\Providers\Data\LlmMessageData;

final readonly class BuiltPromptData
{
    /**
     * @param  array<int, PromptSectionData>  $sections
     * @param  array<int, LlmMessageData>  $messages
     */
    public function __construct(
        public array $sections,
        public array $messages,
        public PromptTraceData $trace,
    ) {}
}
