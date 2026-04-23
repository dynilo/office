<?php

namespace App\Application\Prompts\Data;

final readonly class PromptTraceData
{
    public function __construct(
        public string $version,
        public string $templateStrategy,
        public string $schemaVersion,
        public string $fingerprint,
    ) {}

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'template_strategy' => $this->templateStrategy,
            'schema_version' => $this->schemaVersion,
            'fingerprint' => $this->fingerprint,
        ];
    }
}
