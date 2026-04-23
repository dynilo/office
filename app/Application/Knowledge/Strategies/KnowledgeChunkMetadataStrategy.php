<?php

namespace App\Application\Knowledge\Strategies;

use App\Application\Knowledge\Data\DocumentChunkData;
use App\Infrastructure\Persistence\Eloquent\Models\Document;

final class KnowledgeChunkMetadataStrategy
{
    public function for(Document $document, DocumentChunkData $chunk): array
    {
        return [
            'chunk_index' => $chunk->index,
            'start_offset' => $chunk->startOffset,
            'end_offset' => $chunk->endOffset,
            'character_count' => strlen($chunk->content),
            'document_title' => $document->title,
            'document_checksum' => $document->checksum,
            'source' => 'document_extraction_v1',
        ];
    }
}
