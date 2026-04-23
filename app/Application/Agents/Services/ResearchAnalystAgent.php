<?php

namespace App\Application\Agents\Services;

use App\Application\Prompts\Data\PromptBuildInputData;
use App\Application\Prompts\Services\PromptBuilder;
use App\Application\Providers\Contracts\LlmProvider;
use App\Application\Providers\Data\LlmRequestData;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\Task;

final class ResearchAnalystAgent
{
    public function __construct(
        private readonly PromptBuilder $promptBuilder,
        private readonly LlmProvider $provider,
    ) {
    }

    public function run(Task $task, Agent $agent): array
    {
        $prompt = $this->promptBuilder->build(new PromptBuildInputData(
            agentName: $agent->name,
            agentRole: $agent->role ?? 'research',
            capabilities: $agent->capabilities ?? [],
            taskTitle: $task->title,
            taskSummary: $task->summary,
            taskDescription: $task->description,
            taskPayload: $task->payload ?? [],
            memoryBlocks: $this->memoryBlocks($agent),
            contextBlocks: $this->contextBlocks($task),
        ));

        $response = $this->provider->generate(new LlmRequestData(
            messages: $prompt->messages,
            model: $agent->profile?->model_preference,
            temperature: $this->resolveTemperature($agent),
            idempotencyKey: 'task-'.$task->id.'-agent-'.$agent->id,
            metadata: [
                'task_id' => $task->id,
                'agent_id' => $agent->id,
                'agent_role' => $agent->role,
            ],
        ));

        return [
            'structured_result' => $this->normalizeStructuredResult($response->content),
            'raw_response' => [
                'provider' => $response->provider,
                'response_id' => $response->responseId,
                'model' => $response->model,
                'content' => $response->content,
                'finish_reason' => $response->finishReason,
                'input_tokens' => $response->inputTokens,
                'output_tokens' => $response->outputTokens,
                'request_id' => $response->requestId,
            ],
            'prompt_messages' => array_map(
                static fn ($message): array => $message->toArray(),
                $prompt->messages,
            ),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function memoryBlocks(Agent $agent): array
    {
        return collect($agent->profile?->instructions ?? [])
            ->filter(fn (mixed $instruction): bool => is_string($instruction) && trim($instruction) !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function contextBlocks(Task $task): array
    {
        return collect($task->context['blocks'] ?? [])
            ->filter(fn (mixed $block): bool => is_string($block) && trim($block) !== '')
            ->values()
            ->all();
    }

    private function resolveTemperature(Agent $agent): ?float
    {
        $policy = $agent->profile?->temperature_policy;

        if (! is_array($policy)) {
            return null;
        }

        $value = $policy['value'] ?? null;

        return is_numeric($value) ? (float) $value : null;
    }

    private function normalizeStructuredResult(string $content): array
    {
        $decoded = json_decode($content, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        return [
            'summary' => trim($content),
            'findings' => [],
        ];
    }
}
