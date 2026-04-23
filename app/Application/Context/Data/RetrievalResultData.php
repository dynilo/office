<?php

namespace App\Application\Context\Data;

final readonly class RetrievalResultData
{
    /**
     * @param  array<int, RetrievedContextBlockData>  $blocks
     * @param  array<string, mixed>  $diagnostics
     */
    public function __construct(
        public array $blocks,
        public array $diagnostics,
    ) {}
}
