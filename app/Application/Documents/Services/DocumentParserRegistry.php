<?php

namespace App\Application\Documents\Services;

use App\Application\Documents\Contracts\DocumentParser;
use App\Application\Documents\Data\ParsedDocumentData;
use InvalidArgumentException;

final class DocumentParserRegistry
{
    /**
     * @param  iterable<int, DocumentParser>  $parsers
     */
    public function __construct(
        private readonly iterable $parsers,
    ) {
    }

    public function parse(string $disk, string $path, string $mimeType): ParsedDocumentData
    {
        foreach ($this->parsers as $parser) {
            if ($parser->supports($mimeType)) {
                return $parser->parse($disk, $path, $mimeType);
            }
        }

        throw new InvalidArgumentException(sprintf('No document parser is registered for mime type [%s].', $mimeType));
    }
}
