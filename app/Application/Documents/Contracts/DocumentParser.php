<?php

namespace App\Application\Documents\Contracts;

use App\Application\Documents\Data\ParsedDocumentData;

interface DocumentParser
{
    public function supports(string $mimeType): bool;

    public function parse(string $disk, string $path, string $mimeType): ParsedDocumentData;
}
