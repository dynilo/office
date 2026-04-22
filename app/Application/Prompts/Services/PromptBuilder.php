<?php

namespace App\Application\Prompts\Services;

use App\Application\Prompts\Data\BuiltPromptData;
use App\Application\Prompts\Data\PromptBuildInputData;
use App\Application\Prompts\Data\PromptSectionData;
use App\Application\Prompts\Strategies\AgentRoleTemplateStrategy;
use App\Application\Providers\Data\LlmMessageData;

final class PromptBuilder
{
    public function __construct(
        private readonly AgentRoleTemplateStrategy $strategy,
    ) {
    }

    public function build(PromptBuildInputData $input): BuiltPromptData
    {
        $sections = [
            $this->strategy->systemSection($input),
            $this->strategy->developerSection($input),
            $this->taskContextSection($input),
            ...$this->memorySections($input),
            ...$this->contextSections($input),
        ];

        usort($sections, static function (PromptSectionData $left, PromptSectionData $right): int {
            return [$left->priority, $left->name] <=> [$right->priority, $right->name];
        });

        $messages = [];
        $grouped = [];

        foreach ($sections as $section) {
            $grouped[$section->role][] = $section->toBlock();
        }

        foreach (['system', 'developer', 'user'] as $role) {
            if (! isset($grouped[$role])) {
                continue;
            }

            $messages[] = new LlmMessageData(
                role: $role,
                content: implode("\n\n", $grouped[$role]),
            );
        }

        return new BuiltPromptData(
            sections: $sections,
            messages: $messages,
        );
    }

    private function taskContextSection(PromptBuildInputData $input): PromptSectionData
    {
        $payload = json_encode($input->taskPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return new PromptSectionData(
            name: 'task context',
            role: 'user',
            priority: 30,
            content: implode("\n", array_filter([
                'Task title: '.$input->taskTitle,
                $input->taskSummary !== null ? 'Task summary: '.$input->taskSummary : null,
                $input->taskDescription !== null ? 'Task description: '.$input->taskDescription : null,
                'Task payload:',
                $payload ?: '{}',
            ])),
        );
    }

    /**
     * @return array<int, PromptSectionData>
     */
    private function memorySections(PromptBuildInputData $input): array
    {
        $uniqueBlocks = array_values(array_unique(array_filter(
            array_map('trim', $input->memoryBlocks),
            static fn (string $block): bool => $block !== '',
        )));

        return array_map(
            static fn (string $block, int $index): PromptSectionData => new PromptSectionData(
                name: 'memory block '.($index + 1),
                role: 'developer',
                priority: 40 + $index,
                content: $block,
            ),
            $uniqueBlocks,
            array_keys($uniqueBlocks),
        );
    }

    /**
     * @return array<int, PromptSectionData>
     */
    private function contextSections(PromptBuildInputData $input): array
    {
        $uniqueBlocks = array_values(array_unique(array_filter(
            array_map('trim', $input->contextBlocks),
            static fn (string $block): bool => $block !== '',
        )));

        return array_map(
            static fn (string $block, int $index): PromptSectionData => new PromptSectionData(
                name: 'context block '.($index + 1),
                role: 'user',
                priority: 60 + $index,
                content: $block,
            ),
            $uniqueBlocks,
            array_keys($uniqueBlocks),
        );
    }
}
