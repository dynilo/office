<?php

namespace App\Infrastructure\Documents\Parsers;

use App\Application\Documents\Contracts\DocumentParser;
use App\Application\Documents\Data\ParsedDocumentData;
use Illuminate\Support\Facades\Storage;

final class PlainTextDocumentParser implements DocumentParser
{
    /**
     * @var array<int, string>
     */
    private const SUPPORTED_MIME_TYPES = [
        'text/plain',
        'text/markdown',
        'text/csv',
        'application/json',
    ];

    public function supports(string $mimeType): bool
    {
        return in_array($mimeType, self::SUPPORTED_MIME_TYPES, true);
    }

    public function parse(string $disk, string $path, string $mimeType): ParsedDocumentData
    {
        $rawText = Storage::disk($disk)->get($path);

        return new ParsedDocumentData(
            rawText: trim($rawText),
            metadata: [
                'parser' => 'plain_text',
                'mime_type' => $mimeType,
                'character_count' => mb_strlen($rawText),
                'line_count' => preg_match_all("/\r\n|\n|\r/", $rawText) + 1,
            ],
        );
    }
}
