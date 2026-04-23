<?php

namespace App\Application\Memory\Data;

use App\Infrastructure\Persistence\Eloquent\Models\KnowledgeItem;

final readonly class SimilarityMatchData
{
    public function __construct(
        public KnowledgeItem $knowledgeItem,
        public float $distance,
    ) {
    }
}
