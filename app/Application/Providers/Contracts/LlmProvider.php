<?php

namespace App\Application\Providers\Contracts;

use App\Application\Providers\Data\LlmRequestData;
use App\Application\Providers\Data\LlmResponseData;

interface LlmProvider
{
    public function generate(LlmRequestData $request): LlmResponseData;
}
