<?php

use App\Application\Providers\Contracts\LlmProvider;
use App\Application\Providers\Data\LlmRequestData;
use App\Application\Providers\Data\LlmResponseData;
use App\Application\Providers\Exceptions\LlmProviderException;
use App\Domain\Agents\Enums\AgentStatus;
use App\Domain\Executions\Enums\ExecutionStatus;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Agent;
use App\Infrastructure\Persistence\Eloquent\Models\AgentProfile;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;
use App\Infrastructure\Persistence\Eloquent\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('runs one queued research task end to end', function (): void {
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

    app()->bind(LlmProvider::class, fn () => new class implements LlmProvider
    {
        public function generate(LlmRequestData $request): LlmResponseData
        {
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
    });

    $this->artisan('research-agent:run-one')
        ->expectsOutput('Completed research task '.$task->id)
        ->assertExitCode(0);

    $task->refresh();
    $execution = Execution::query()->where('task_id', $task->id)->first();

    expect($task->status)->toBe(TaskStatus::Completed)
        ->and($task->agent_id)->toBe($agent->id)
        ->and($execution)->not->toBeNull()
        ->and($execution?->status)->toBe(ExecutionStatus::Succeeded)
        ->and($execution?->output_payload['summary'] ?? null)->toBe('The market is led by a small set of enterprise-focused vendors.')
        ->and($execution?->provider_response['provider'] ?? null)->toBe('fake')
        ->and($execution?->provider_response['response_id'] ?? null)->toBe('resp_research_1')
        ->and($execution?->logs)->toHaveCount(3);
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
    $execution = Execution::query()->where('task_id', $task->id)->first();

    expect($task->status)->toBe(TaskStatus::Failed)
        ->and($task->agent_id)->toBe($agent->id)
        ->and($execution)->not->toBeNull()
        ->and($execution?->status)->toBe(ExecutionStatus::Failed)
        ->and($execution?->error_message)->toBe('Provider unavailable.')
        ->and($execution?->provider_response)->toBeNull()
        ->and($execution?->logs->last()?->message)->toBe('execution.failed')
        ->and($execution?->logs->last()?->context['error_code'] ?? null)->toBe('unavailable');
});
