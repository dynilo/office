<?php

namespace App\Application\Integrations\Data;

use InvalidArgumentException;

final readonly class IntegrationRequestData
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public string $connector,
        public string $operation,
        public array $payload = [],
        public array $context = [],
    ) {
        if (trim($this->connector) === '') {
            throw new InvalidArgumentException('Integration connector cannot be empty.');
        }

        if (trim($this->operation) === '') {
            throw new InvalidArgumentException('Integration operation cannot be empty.');
        }
    }
}
