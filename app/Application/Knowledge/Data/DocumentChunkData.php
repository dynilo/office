<?php

namespace App\Application\Knowledge\Data;

final readonly class DocumentChunkData
{
    public function __construct(
        public int $index,
        public string $content,
        public int $startOffset,
        public int $endOffset,
    ) {
    }
}
