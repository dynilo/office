<?php

namespace App\Application\Knowledge\Services;

use App\Application\Knowledge\Strategies\KnowledgeChunkMetadataStrategy;
use App\Infrastructure\Persistence\Eloquent\Models\Document;
use App\Infrastructure\Persistence\Eloquent\Models\KnowledgeItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class ExtractDocumentKnowledgeItemsService
{
    public function __construct(
        private readonly DocumentChunkingService $chunking,
        private readonly KnowledgeChunkMetadataStrategy $metadata,
    ) {
    }

    /**
     * @return Collection<int, KnowledgeItem>
     */
    public function extract(Document $document): Collection
    {
        $chunks = $this->chunking->chunk($document->raw_text ?? '');

        return DB::transaction(function () use ($document, $chunks): Collection {
            $document->knowledgeItems()->delete();

            $items = [];

            foreach ($chunks as $chunk) {
                $items[] = KnowledgeItem::query()->create([
                    'document_id' => $document->id,
                    'title' => $document->title.' - Chunk '.($chunk->index + 1),
                    'content' => $chunk->content,
                    'content_hash' => hash('sha256', $chunk->content),
                    'metadata' => $this->metadata->for($document, $chunk),
                    'indexed_at' => now(),
                ]);
            }

            return new Collection($items);
        });
    }
}
