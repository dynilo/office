<?php

namespace App\Application\Agents\Services\Specialists;

final class FinanceAnalystAgent extends AbstractSpecialistAgent
{
    public function role(): string
    {
        return 'finance';
    }

    public function outputType(): string
    {
        return 'finance_analysis';
    }

    protected function fallbackOutput(string $content): array
    {
        return [
            'financial_summary' => trim($content),
            'assumptions' => [],
            'metrics' => [],
            'financial_risks' => [],
        ];
    }
}
