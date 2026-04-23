<?php

namespace App\Application\Memory\Data;

final readonly class EmbeddingData
{
    /**
     * @param  array<int, float>  $vector
     */
    public function __construct(
        public array $vector,
        public string $model,
    ) {
    }

    public function dimensions(): int
    {
        return count($this->vector);
    }
}
