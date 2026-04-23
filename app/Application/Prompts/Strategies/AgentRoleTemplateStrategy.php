<?php

namespace App\Application\Prompts\Strategies;

use App\Application\Prompts\Data\PromptBuildInputData;
use App\Application\Prompts\Data\PromptSectionData;

final class AgentRoleTemplateStrategy
{
    public function systemSection(PromptBuildInputData $input): PromptSectionData
    {
        return match ($input->agentRole) {
            'support' => new PromptSectionData(
                name: 'system',
                role: 'system',
                priority: 10,
                content: 'You are a support operations agent. Optimize for clarity, correctness, and concise customer-safe outputs.',
            ),
            'research' => new PromptSectionData(
                name: 'system',
                role: 'system',
                priority: 10,
                content: 'You are a research analysis agent. Optimize for synthesis, evidence tracking, and explicit uncertainty handling.',
            ),
            'operations' => new PromptSectionData(
                name: 'system',
                role: 'system',
                priority: 10,
                content: 'You are an operations coordination agent. Optimize for deterministic execution, process fidelity, and actionable outputs.',
            ),
            'strategy' => new PromptSectionData(
                name: 'system',
                role: 'system',
                priority: 10,
                content: 'You are a strategy analyst agent. Optimize for market framing, option analysis, risk tradeoffs, and executive-ready recommendations.',
            ),
            'finance' => new PromptSectionData(
                name: 'system',
                role: 'system',
                priority: 10,
                content: 'You are a finance analyst agent. Optimize for assumptions, unit economics, financial risks, and decision-useful metrics.',
            ),
            'legal_compliance' => new PromptSectionData(
                name: 'system',
                role: 'system',
                priority: 10,
                content: 'You are a legal and compliance review agent. Optimize for policy issues, risk flags, required follow-up, and clear non-legal-advice framing.',
            ),
            'product_architect' => new PromptSectionData(
                name: 'system',
                role: 'system',
                priority: 10,
                content: 'You are a product architect agent. Optimize for system boundaries, components, tradeoffs, sequencing, and implementation clarity.',
            ),
            default => new PromptSectionData(
                name: 'system',
                role: 'system',
                priority: 10,
                content: 'You are a specialized AI office agent. Optimize for accurate, structured task completion.',
            ),
        };
    }

    public function developerSection(PromptBuildInputData $input): PromptSectionData
    {
        $capabilities = $input->capabilities === []
            ? 'none declared'
            : implode(', ', array_values($input->capabilities));

        return new PromptSectionData(
            name: 'developer',
            role: 'developer',
            priority: 20,
            content: implode("\n", [
                'Agent name: '.$input->agentName,
                'Agent role: '.$input->agentRole,
                'Declared capabilities: '.$capabilities,
                'Follow the role guidance strictly and stay within the provided task context.',
            ]),
        );
    }
}
