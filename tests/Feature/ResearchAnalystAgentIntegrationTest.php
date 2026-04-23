<?php

use App\Application\Providers\Contracts\LlmProvider;
use App\Application\Providers\Data\LlmRequestData;
use App\Application\Providers\Data\LlmResponseData;
use App\Application\Providers\Exceptions\LlmProviderException;
use App\Application\Memory\Contracts\EmbeddingGenerator;
use App\Application\Memory\Contracts\KnowledgeSimilaritySearch;
use App\Application\Memory\Data\EmbeddingData;
use App\Application\Memory\Data\SimilarityMatchData;
use App\Domain\Agents\Enums\AgentStatus;
use App\Domain\Executions\Enums\ExecutionStatus;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\AgentProfile;
use App\Infrastructure\Persistence\Eloquent\Models\Artifact;
use App\Infrastructure\Persistence\Eloquent\Models\Document;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use App\Infrastructure\Persistence\Eloquent\Models\KnowledgeItem;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('runs one queued research task end to end with retrieved context', function (): void {
    $agent = Agent::factory()->create([
        'role' => 'research',
        'status' => AgentStatus::Active,
        'capabilities' => ['analysis'],
    ]);
    AgentProfile::factory()->for($agent)->create([
        'model_preference' => 'gpt-5',
        'instructions' => ['Prefer concise evidence-backed summaries.'],
        'temperature_policy' => ['mode' => 'fixed', 'value' => 0.2],
    ]);
    $task = Task::factory()->create([
        'agent_id' => null,
        'status' => TaskStatus::Queued,
        'requested_agent_role' => 'research',
        'payload' => [
            'required_capabilities' => ['analysis'],
            'research_question' => 'Summarize the vendor landscape.',
        ],
        'context' => [
            'blocks' => ['Focus on enterprise vendors.'],
        ],
    ]);
    $document = Document::factory()->create([
        'title' => 'Vendor Landscape Notes',
    ]);
    $knowledgeItem = KnowledgeItem::factory()->for($document)->create([
        'title' => 'Vendor Landscape Chunk 1',
        'content' => 'Vendor A leads enterprise adoption while Vendor B is growing in regulated sectors.',
        'metadata' => [
            'document_title' => 'Vendor Landscape Notes',
            'chunk_index' => 0,
        ],
    ]);

    app()->bind(EmbeddingGenerator::class, fn () => new class implements EmbeddingGenerator
    {
        public function generate(string $input): EmbeddingData
        {
            return new EmbeddingData(
                vector: [0.11, 0.22, 0.33],
                model: 'fake-embedding-model',
            );
        }
    });
    app()->bind(KnowledgeSimilaritySearch::class, fn () => new class($knowledgeItem) implements KnowledgeSimilaritySearch
    {
        public function __construct(
            private readonly KnowledgeItem $knowledgeItem,
        ) {
        }

        public function search(array $embedding, int $limit = 5): array
        {
            return [
                new SimilarityMatchData($this->knowledgeItem, 0.08),
            ];
        }
    });

    $provider = new class implements LlmProvider
    {
        public ?LlmRequestData $lastRequest = null;

        public function generate(LlmRequestData $request): LlmResponseData
        {
            $this->lastRequest = $request;

            return new LlmResponseData(
                provider: 'fake',
                responseId: 'resp_research_1',
                model: 'gpt-5',
                content: json_encode([
                    'summary' => 'The market is led by a small set of enterprise-focused vendors.',
                    'findings' => ['Vendor A and Vendor B dominate enterprise usage.'],
                ], JSON_THROW_ON_ERROR),
                finishReason: 'stop',
                inputTokens: 120,
                outputTokens: 40,
                requestId: 'req_research_1',
            );
        }
    };
    app()->instance(LlmProvider::class, $provider);

    $this->artisan('research-agent:run-one')
        ->expectsOutput('Completed research task '.$task->id)
        ->assertExitCode(0);

    $task->refresh();
    $execution = Execution::query()->where('task_id', $task->id)->first();
    $artifacts = Artifact::query()
        ->where('task_id', $task->id)
        ->orderBy('name')
        ->get();

    expect($provider->lastRequest)->not->toBeNull()
        ->and(collect($provider->lastRequest?->messages)->pluck('content')->implode("\n\n"))->toContain('[Retrieved Context]')
        ->and(collect($provider->lastRequest?->messages)->pluck('content')->implode("\n\n"))->toContain('Title: Vendor Landscape Chunk 1')
        ->and(collect($provider->lastRequest?->messages)->pluck('content')->implode("\n\n"))->toContain('Vendor A leads enterprise adoption');

    expect($task->status)->toBe(TaskStatus::Completed)
        ->and($task->agent_id)->toBe($agent->id)
        ->and($execution)->not->toBeNull()
        ->and($execution?->status)->toBe(ExecutionStatus::Succeeded)
        ->and($execution?->output_payload['summary'] ?? null)->toBe('The market is led by a small set of enterprise-focused vendors.')
        ->and($execution?->output_payload['grounding']['retrieved_context_count'] ?? null)->toBe(1)
        ->and($execution?->output_payload['grounding']['knowledge_item_ids'][0] ?? null)->toBe($knowledgeItem->id)
        ->and($execution?->provider_response['provider'] ?? null)->toBe('fake')
        ->and($execution?->provider_response['response_id'] ?? null)->toBe('resp_research_1')
        ->and($execution?->provider_response['grounding_trace'][0]['knowledge_item_id'] ?? null)->toBe($knowledgeItem->id)
        ->and($execution?->logs)->toHaveCount(3)
        ->and($artifacts)->toHaveCount(2)
        ->and($artifacts[0]->name)->toBe('raw_response')
        ->and($artifacts[0]->kind)->toBe('text')
        ->and($artifacts[0]->execution_id)->toBe($execution?->id)
        ->and($artifacts[1]->name)->toBe('structured_result')
        ->and($artifacts[1]->kind)->toBe('json')
        ->and($artifacts[1]->content_json['summary'] ?? null)->toBe('The market is led by a small set of enterprise-focused vendors.');
});

it('handles the provider failure path and updates states correctly', function (): void {
    $agent = Agent::factory()->create([
        'role' => 'research',
        'status' => AgentStatus::Active,
        'capabilities' => ['analysis'],
    ]);
    AgentProfile::factory()->for($agent)->create([
        'model_preference' => 'gpt-5',
    ]);
    $task = Task::factory()->create([
        'agent_id' => null,
        'status' => TaskStatus::Queued,
        'requested_agent_role' => 'research',
        'payload' => [
            'required_capabilities' => ['analysis'],
            'research_question' => 'Summarize the vendor landscape.',
        ],
    ]);

    app()->bind(LlmProvider::class, fn () => new class implements LlmProvider
    {
        public function generate(LlmRequestData $request): LlmResponseData
        {
            throw LlmProviderException::response(
                provider: 'fake',
                message: 'Provider unavailable.',
                statusCode: 503,
                errorCode: 'unavailable',
                retriable: true,
            );
        }
    });

    $this->artisan('research-agent:run-one')
        ->expectsOutput('Research task failed: Provider unavailable.')
        ->assertExitCode(1);

    $task->refresh();
    $execution = Execution::query()->where('task_id', $task->id)->where('status', ExecutionStatus::Failed)->first();
    $retryExecution = Execution::query()->where('task_id', $task->id)->where('status', ExecutionStatus::Pending)->first();

    expect($task->status)->toBe(TaskStatus::Failed)
        ->and($task->agent_id)->toBe($agent->id)
        ->and($execution)->not->toBeNull()
        ->and($execution?->status)->toBe(ExecutionStatus::Failed)
        ->and($execution?->error_message)->toBe('Provider unavailable.')
        ->and($execution?->failure_classification)->toBe('transient_provider_failure')
        ->and($execution?->provider_response)->toBeNull()
        ->and($execution?->logs->pluck('message')->all())->toBe([
            'execution.pending_created',
            'execution.running',
            'execution.failed',
            'execution.retry_scheduled',
        ])
        ->and($retryExecution)->not->toBeNull()
        ->and($retryExecution?->attempt)->toBe(2)
        ->and($retryExecution?->retry_count)->toBe(1)
        ->and($retryExecution?->retry_of_execution_id)->toBe($execution?->id);
});
