<?php

namespace App\Application\Context\Data;

use App\Infrastructure\Persistence\Eloquent\Models\KnowledgeItem;

final readonly class RetrievedContextBlockData
{
    public function __construct(
        public KnowledgeItem $knowledgeItem,
        public float $distance,
        public string $formattedBlock,
    ) {
    }
}
