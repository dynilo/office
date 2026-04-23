<?php

namespace App\Application\Agents\Services\Specialists;

final class StrategyAnalystAgent extends AbstractSpecialistAgent
{
    public function role(): string
    {
        return 'strategy';
    }

    public function outputType(): string
    {
        return 'strategy_brief';
    }

    protected function fallbackOutput(string $content): array
    {
        return [
            'strategic_summary' => trim($content),
            'opportunities' => [],
            'risks' => [],
            'recommended_moves' => [],
        ];
    }
}
