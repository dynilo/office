<?php

namespace App\Application\Agents\Services\Specialists;

final class LegalComplianceReviewAgent extends AbstractSpecialistAgent
{
    public function role(): string
    {
        return 'legal_compliance';
    }

    public function outputType(): string
    {
        return 'compliance_review';
    }

    protected function fallbackOutput(string $content): array
    {
        return [
            'review_summary' => trim($content),
            'compliance_flags' => [],
            'required_follow_up' => [],
            'approval_recommendation' => 'needs_review',
        ];
    }
}
