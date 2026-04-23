<?php

namespace App\Application\Memory\Contracts;

use App\Application\Memory\Data\SimilarityMatchData;

interface KnowledgeSimilaritySearch
{
    /**
     * @return array<int, SimilarityMatchData>
     */
    public function search(array $embedding, int $limit = 5): array;
}
