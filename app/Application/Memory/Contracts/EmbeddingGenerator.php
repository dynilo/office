<?php

namespace App\Application\Memory\Contracts;

use App\Application\Memory\Data\EmbeddingData;

interface EmbeddingGenerator
{
    public function generate(string $input): EmbeddingData;
}
