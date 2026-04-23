<?php

namespace App\Application\Integrations\Data;

final readonly class IntegrationResponseData
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public string $connector,
        public string $operation,
        public bool $success,
        public array $data = [],
        public array $meta = [],
    ) {}
}
