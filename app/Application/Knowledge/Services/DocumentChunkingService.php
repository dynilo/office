<?php

namespace App\Application\Knowledge\Services;

use App\Application\Knowledge\Data\DocumentChunkData;

final class DocumentChunkingService
{
    /**
     * @return array<int, DocumentChunkData>
     */
    public function chunk(string $text, ?int $maxCharacters = null): array
    {
        $normalized = trim(preg_replace("/\r\n|\r/", "\n", $text) ?? $text);

        if ($normalized === '') {
            return [];
        }

        $limit = $maxCharacters ?? (int) config('knowledge.chunking.max_characters', 500);
        $paragraphs = preg_split("/\n{2,}/", $normalized) ?: [];

        $chunks = [];
        $buffer = '';
        $bufferStart = 0;
        $searchOffset = 0;

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);

            if ($paragraph === '') {
                continue;
            }

            $paragraphStart = strpos($normalized, $paragraph, $searchOffset);
            $paragraphStart = $paragraphStart === false ? $searchOffset : $paragraphStart;
            $searchOffset = $paragraphStart + strlen($paragraph);

            if ($buffer === '') {
                $buffer = $paragraph;
                $bufferStart = $paragraphStart;

                continue;
            }

            $candidate = $buffer."\n\n".$paragraph;

            if (strlen($candidate) <= $limit) {
                $buffer = $candidate;

                continue;
            }

            $chunks[] = new DocumentChunkData(
                index: count($chunks),
                content: $buffer,
                startOffset: $bufferStart,
                endOffset: $bufferStart + strlen($buffer) - 1,
            );

            $buffer = $paragraph;
            $bufferStart = $paragraphStart;
        }

        if ($buffer !== '') {
            $chunks[] = new DocumentChunkData(
                index: count($chunks),
                content: $buffer,
                startOffset: $bufferStart,
                endOffset: $bufferStart + strlen($buffer) - 1,
            );
        }

        return $chunks;
    }
}
