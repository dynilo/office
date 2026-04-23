<?php

namespace App\Application\Agents\Services\Specialists;

use App\Application\Prompts\Data\PromptBuildInputData;
use App\Application\Prompts\Services\PromptBuilder;
use App\Application\Providers\Contracts\LlmProvider;
use App\Application\Providers\Data\LlmRequestData;
use App\Application\Providers\Data\LlmResponseData;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use App\Support\Exceptions\InvalidStateException;

abstract class AbstractSpecialistAgent
{
    public function __construct(
        private readonly PromptBuilder $promptBuilder,
        private readonly LlmProvider $provider,
    ) {}

    abstract public function role(): string;

    abstract public function outputType(): string;

    /**
     * @return array<string, mixed>
     */
    abstract protected function fallbackOutput(string $content): array;

    public function supports(Agent $agent): bool
    {
        return $agent->role === $this->role();
    }

    /**
     * @return array<string, mixed>
     */
    public function run(Task $task, Agent $agent): array
    {
        $this->assertSupportedAgent($agent);
        $agent->loadMissing('profile');

        $prompt = $this->promptBuilder->build(new PromptBuildInputData(
            agentName: $agent->name,
            agentRole: $this->role(),
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
            idempotencyKey: 'specialist-'.$this->role().'-task-'.$task->id.'-agent-'.$agent->id,
            metadata: [
                'task_id' => $task->id,
                'agent_id' => $agent->id,
                'agent_role' => $this->role(),
                'specialist_output_type' => $this->outputType(),
                'prompt' => $prompt->trace->toArray(),
            ],
        ));

        return [
            'structured_result' => $this->normalizeStructuredResult($response),
            'raw_response' => [
                'provider' => $response->provider,
                'response_id' => $response->responseId,
                'model' => $response->model,
                'content' => $response->content,
                'finish_reason' => $response->finishReason,
                'input_tokens' => $response->inputTokens,
                'output_tokens' => $response->outputTokens,
                'request_id' => $response->requestId,
                'prompt' => $prompt->trace->toArray(),
            ],
            'prompt_messages' => array_map(
                static fn ($message): array => $message->toArray(),
                $prompt->messages,
            ),
        ];
    }

    private function assertSupportedAgent(Agent $agent): void
    {
        if (! $this->supports($agent)) {
            throw new InvalidStateException(sprintf(
                'Specialist agent role must be [%s].',
                $this->role(),
            ));
        }
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

    /**
     * @return array<string, mixed>
     */
    private function normalizeStructuredResult(LlmResponseData $response): array
    {
        $decoded = json_decode($response->content, true);
        $result = is_array($decoded)
            ? $decoded
            : $this->fallbackOutput($response->content);

        return [
            'role' => $this->role(),
            'output_type' => $this->outputType(),
            ...$result,
        ];
    }
}
