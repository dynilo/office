<?php

namespace App\Application\Documents\Data;

final readonly class ParsedDocumentData
{
    public function __construct(
        public string $rawText,
        public array $metadata = [],
    ) {
    }
}
