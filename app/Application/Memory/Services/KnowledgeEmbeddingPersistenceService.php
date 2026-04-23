<?php

namespace App\Application\Memory\Services;

use App\Application\Memory\Data\EmbeddingData;
use App\Infrastructure\Persistence\Eloquent\Models\KnowledgeItem;
use Illuminate\Support\Facades\DB;

final class KnowledgeEmbeddingPersistenceService
{
    public function __construct(
        private readonly PgvectorCapabilitiesService $capabilities,
    ) {
    }

    public function persist(KnowledgeItem $item, EmbeddingData $embedding): KnowledgeItem
    {
        $item->forceFill([
            'embedding_model' => $embedding->model,
            'embedding_dimensions' => $embedding->dimensions(),
            'embedding_generated_at' => now(),
        ])->save();

        $item->metadata = [
            ...($item->metadata ?? []),
            'memory' => [
                'embedding_model' => $embedding->model,
                'embedding_dimensions' => $embedding->dimensions(),
                'vector_storage' => $this->capabilities->supportsVectorStorage() ? 'pgvector' : 'unavailable',
            ],
        ];
        $item->save();

        if (! $this->capabilities->supportsVectorStorage()) {
            return $item->fresh();
        }

        $vector = '['.implode(',', array_map(
            static fn (float $value): string => sprintf('%.14F', $value),
            $embedding->vector,
        )).']';

        DB::table('knowledge_items')
            ->where('id', $item->id)
            ->update([
                'embedding' => DB::raw("'".$vector."'::vector"),
            ]);

        return $item->fresh();
    }
}
