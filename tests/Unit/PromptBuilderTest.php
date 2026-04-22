<?php

use App\Application\Prompts\Data\PromptBuildInputData;
use App\Application\Prompts\Services\PromptBuilder;
use App\Application\Prompts\Strategies\AgentRoleTemplateStrategy;

it('builds the same prompt output for the same input', function (): void {
    $builder = new PromptBuilder(new AgentRoleTemplateStrategy());

    $input = new PromptBuildInputData(
        agentName: 'Support Agent',
        agentRole: 'support',
        capabilities: ['triage', 'reply'],
        taskTitle: 'Draft a response',
        taskSummary: 'Customer needs a concise answer',
        taskDescription: 'Draft a safe and concise support response.',
        taskPayload: [
            'ticket_id' => 'T-100',
            'channel' => 'email',
        ],
        memoryBlocks: [
            'Customer prefers short responses.',
            'Customer prefers short responses.',
        ],
        contextBlocks: [
            'Current SLA: 4 hours.',
        ],
    );

    $first = $builder->build($input);
    $second = $builder->build($input);

    expect(array_map(fn ($section) => $section->toBlock(), $first->sections))
        ->toBe(array_map(fn ($section) => $section->toBlock(), $second->sections))
        ->and(array_map(fn ($message) => $message->toArray(), $first->messages))
        ->toBe(array_map(fn ($message) => $message->toArray(), $second->messages));
});

it('generates different prompt shapes for different agent roles', function (): void {
    $builder = new PromptBuilder(new AgentRoleTemplateStrategy());

    $support = $builder->build(new PromptBuildInputData(
        agentName: 'Support Agent',
        agentRole: 'support',
        capabilities: ['triage'],
        taskTitle: 'Answer customer',
        taskSummary: null,
        taskDescription: null,
        taskPayload: ['ticket_id' => 'T-101'],
    ));

    $research = $builder->build(new PromptBuildInputData(
        agentName: 'Research Agent',
        agentRole: 'research',
        capabilities: ['analysis'],
        taskTitle: 'Summarize market signals',
        taskSummary: null,
        taskDescription: null,
        taskPayload: ['report_id' => 'R-9'],
    ));

    expect($support->messages[0]->content)->not->toBe($research->messages[0]->content)
        ->and($support->messages[1]->content)->toContain('Agent role: support')
        ->and($research->messages[1]->content)->toContain('Agent role: research');
});

it('deduplicates optional memory and context blocks deterministically', function (): void {
    $builder = new PromptBuilder(new AgentRoleTemplateStrategy());

    $prompt = $builder->build(new PromptBuildInputData(
        agentName: 'Operations Agent',
        agentRole: 'operations',
        capabilities: ['routing'],
        taskTitle: 'Route intake',
        taskSummary: 'Route to the correct queue',
        taskDescription: 'Use standard intake handling.',
        taskPayload: ['queue' => 'ops'],
        memoryBlocks: ['Policy A', 'Policy A', 'Policy B'],
        contextBlocks: ['Context X', 'Context X'],
    ));

    $sectionNames = array_map(fn ($section) => $section->name, $prompt->sections);
    $developerMessage = collect($prompt->messages)->firstWhere('role', 'developer');
    $userMessage = collect($prompt->messages)->firstWhere('role', 'user');

    expect($sectionNames)->toBe([
        'system',
        'developer',
        'task context',
        'memory block 1',
        'memory block 2',
        'context block 1',
    ])
        ->and(substr_count($developerMessage->content, 'Policy A'))->toBe(1)
        ->and(substr_count($developerMessage->content, 'Policy B'))->toBe(1)
        ->and(substr_count($userMessage->content, 'Context X'))->toBe(1);
});
