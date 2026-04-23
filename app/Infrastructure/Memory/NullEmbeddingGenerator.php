<?php

namespace App\Infrastructure\Memory;

use App\Application\Memory\Contracts\EmbeddingGenerator;
use App\Application\Memory\Data\EmbeddingData;

final class NullEmbeddingGenerator implements EmbeddingGenerator
{
    public function generate(string $input): EmbeddingData
    {
        return new EmbeddingData(
            vector: [],
            model: 'null-embedding-generator',
        );
    }
}
