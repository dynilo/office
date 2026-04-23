<?php

namespace App\Application\Integrations\Data;

use InvalidArgumentException;

final readonly class IntegrationDescriptorData
{
    /**
     * @param  array<int, string>  $capabilities
     */
    public function __construct(
        public string $name,
        public string $label,
        public array $capabilities,
        public bool $enabled = true,
    ) {
        if (trim($this->name) === '') {
            throw new InvalidArgumentException('Integration descriptor name cannot be empty.');
        }

        if (trim($this->label) === '') {
            throw new InvalidArgumentException('Integration descriptor label cannot be empty.');
        }

        foreach ($this->capabilities as $capability) {
            if (! is_string($capability) || trim($capability) === '') {
                throw new InvalidArgumentException('Integration capabilities must be non-empty strings.');
            }
        }
    }
}
