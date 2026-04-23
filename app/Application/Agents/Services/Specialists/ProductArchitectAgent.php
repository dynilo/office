<?php

namespace App\Application\Agents\Services\Specialists;

final class ProductArchitectAgent extends AbstractSpecialistAgent
{
    public function role(): string
    {
        return 'product_architect';
    }

    public function outputType(): string
    {
        return 'product_architecture_plan';
    }

    protected function fallbackOutput(string $content): array
    {
        return [
            'architecture_summary' => trim($content),
            'components' => [],
            'tradeoffs' => [],
            'implementation_sequence' => [],
        ];
    }
}
