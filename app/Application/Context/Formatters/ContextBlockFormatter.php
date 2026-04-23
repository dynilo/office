<?php

namespace App\Application\Context\Formatters;

use App\Application\Memory\Data\SimilarityMatchData;

final class ContextBlockFormatter
{
    public function format(SimilarityMatchData $match): string
    {
        $item = $match->knowledgeItem;
        $metadata = $item->metadata ?? [];

        return implode("\n", array_filter([
            '[Retrieved Context]',
            'Title: '.$item->title,
            isset($metadata['document_title']) ? 'Document: '.$metadata['document_title'] : null,
            isset($metadata['chunk_index']) ? 'Chunk: '.((int) $metadata['chunk_index'] + 1) : null,
            'Distance: '.number_format($match->distance, 6, '.', ''),
            'Content:',
            $item->content,
        ]));
    }
}
