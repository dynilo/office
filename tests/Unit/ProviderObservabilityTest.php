<?php

use App\Application\Providers\Contracts\LlmProvider;
use App\Application\Providers\Data\LlmMessageData;
use App\Application\Providers\Data\LlmRequestData;
use App\Application\Providers\Exceptions\LlmProviderException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function (): void {
    config()->set('providers.openai_compatible', [
        'base_url' => 'https://api.openai.com/v1',
        'api_key' => 'secret-test-key',
        'organization' => 'org_test',
        'project' => 'proj_test',
        'model' => 'gpt-5',
        'timeout' => 30,
        'retry_times' => 2,
        'retry_sleep_ms' => 1,
        'store' => false,
    ]);
});

it('emits provider trace and metric hooks on success', function (): void {
    Log::spy();
    Http::fake([
        'https://api.openai.com/v1/chat/completions' => Http::response([
            'id' => 'chatcmpl_123',
            'model' => 'gpt-5',
            'choices' => [[
                'finish_reason' => 'stop',
                'message' => [
                    'content' => 'Normalized output',
                ],
            ]],
            'usage' => [
                'prompt_tokens' => 12,
                'completion_tokens' => 8,
            ],
        ], 200, [
            'x-request-id' => 'req_123',
        ]),
    ]);

    app(LlmProvider::class)->generate(new LlmRequestData(
        messages: [new LlmMessageData('user', 'Say hello.')],
        metadata: ['trace_id' => 'trace-provider-1'],
    ));

    Log::shouldHaveReceived('info')
        ->with(
            'observability.trace.start',
            Mockery::on(static function (array $context): bool {
                return $context['name'] === 'llm.provider.generate'
                    && $context['trace_id'] === 'trace-provider-1'
                    && $context['context']['provider'] === 'openai_compatible';
            }),
        )
        ->once();

    Log::shouldHaveReceived('info')
        ->with(
            'observability.metric',
            Mockery::on(static function (array $context): bool {
                return $context['name'] === 'llm.provider.requests_total'
                    && $context['dimensions']['provider'] === 'openai_compatible'
                    && $context['dimensions']['outcome'] === 'success';
            }),
        )
        ->once();

    Log::shouldHaveReceived('info')
        ->with(
            'observability.trace.finish',
            Mockery::on(static function (array $context): bool {
                return $context['name'] === 'llm.provider.generate'
                    && $context['trace_id'] === 'trace-provider-1'
                    && $context['context']['request_id'] === 'req_123';
            }),
        )
        ->once();
});

it('emits provider failure trace hooks on normalized failures', function (): void {
    Log::spy();
    Http::fake([
        'https://api.openai.com/v1/chat/completions' => Http::response([
            'error' => [
                'message' => 'Bad request.',
                'type' => 'invalid_request_error',
                'code' => 'invalid_payload',
            ],
        ], 400, [
            'x-request-id' => 'req_bad',
        ]),
    ]);

    expect(fn () => app(LlmProvider::class)->generate(new LlmRequestData(
        messages: [new LlmMessageData('user', 'Bad request')],
        metadata: ['trace_id' => 'trace-provider-fail'],
    )))->toThrow(LlmProviderException::class);

    Log::shouldHaveReceived('warning')
        ->with(
            'observability.trace.fail',
            Mockery::on(static function (array $context): bool {
                return $context['name'] === 'llm.provider.generate'
                    && $context['trace_id'] === 'trace-provider-fail'
                    && $context['context']['error_code'] === 'invalid_payload'
                    && $context['context']['status_code'] === 400;
            }),
        )
        ->once();
});
