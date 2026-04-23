<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Application\Memory\Contracts\KnowledgeSimilaritySearch;
use App\Application\Memory\Data\SimilarityMatchData;
use App\Application\Memory\Services\PgvectorCapabilitiesService;
use App\Infrastructure\Persistence\Eloquent\Models\KnowledgeItem;
use Illuminate\Support\Facades\DB;

final class PgvectorKnowledgeSimilaritySearch implements KnowledgeSimilaritySearch
{
    public function __construct(
        private readonly PgvectorCapabilitiesService $capabilities,
    ) {}

    public function search(array $embedding, int $limit = 5): array
    {
        if ($limit < 1 || ! $this->capabilities->supportsSimilaritySearch() || ! $this->capabilities->vectorDimensionsAreValid($embedding)) {
            return [];
        }

        $operator = match (config('memory.pgvector.distance', 'cosine')) {
            'l2' => '<->',
            'inner_product' => '<#>',
            default => '<=>',
        };

        $vector = '['.implode(',', array_map(
            static fn (float $value): string => sprintf('%.14F', $value),
            $embedding,
        )).']';

        $rows = DB::table('knowledge_items')
            ->select('id')
            ->selectRaw("embedding {$operator} ?::vector as distance", [$vector])
            ->whereNotNull('embedding')
            ->orderBy('distance')
            ->limit($limit)
            ->get();

        $items = KnowledgeItem::query()
            ->whereIn('id', $rows->pluck('id')->all())
            ->get()
            ->keyBy('id');

        return $rows->map(static fn (object $row): SimilarityMatchData => new SimilarityMatchData(
            knowledgeItem: $items[$row->id],
            distance: (float) $row->distance,
        ))->all();
    }
}
