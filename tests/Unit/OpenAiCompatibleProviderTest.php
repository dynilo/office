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

it('sends normalized requests through a single provider interface', function (): void {
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

    $provider = app(LlmProvider::class);
    $response = $provider->generate(new LlmRequestData(
        messages: [
            new LlmMessageData('developer', 'Be concise.'),
            new LlmMessageData('user', 'Say hello.'),
        ],
        temperature: 0.2,
        idempotencyKey: 'client-request-1',
        metadata: ['trace_id' => 'trace-1'],
    ));

    expect($response->provider)->toBe('openai_compatible')
        ->and($response->responseId)->toBe('chatcmpl_123')
        ->and($response->content)->toBe('Normalized output')
        ->and($response->requestId)->toBe('req_123')
        ->and($response->inputTokens)->toBe(12)
        ->and($response->outputTokens)->toBe(8);

    Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
        return $request->url() === 'https://api.openai.com/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer secret-test-key')
            && $request->hasHeader('X-Client-Request-Id', 'client-request-1')
            && $request['model'] === 'gpt-5'
            && count($request['messages']) === 2;
    });
});

it('retries retriable provider failures and normalizes the eventual response', function (): void {
    Http::fakeSequence()
        ->push([
            'error' => [
                'message' => 'Rate limit hit.',
                'type' => 'rate_limit_error',
                'code' => 'rate_limit_exceeded',
            ],
        ], 429)
        ->push([
            'id' => 'chatcmpl_retry',
            'model' => 'gpt-5',
            'choices' => [[
                'finish_reason' => 'stop',
                'message' => [
                    'content' => 'Recovered response',
                ],
            ]],
            'usage' => [
                'prompt_tokens' => 10,
                'completion_tokens' => 5,
            ],
        ], 200, [
            'x-request-id' => 'req_retry',
        ]);

    $response = app(LlmProvider::class)->generate(new LlmRequestData(
        messages: [new LlmMessageData('user', 'Retry me.')],
    ));

    expect($response->content)->toBe('Recovered response');
    Http::assertSentCount(2);
});

it('normalizes provider failures', function (): void {
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
    )))->toThrow(function (LlmProviderException $exception): bool {
        expect($exception->provider)->toBe('openai_compatible')
            ->and($exception->statusCode)->toBe(400)
            ->and($exception->errorCode)->toBe('invalid_payload')
            ->and($exception->retriable)->toBeFalse();

        return $exception->getMessage() === 'Bad request.';
    });
});

it('never logs secrets', function (): void {
    Log::spy();
    Http::fake([
        'https://api.openai.com/v1/chat/completions' => Http::response([
            'id' => 'chatcmpl_log',
            'model' => 'gpt-5',
            'choices' => [[
                'finish_reason' => 'stop',
                'message' => [
                    'content' => 'Safe log response',
                ],
            ]],
            'usage' => [
                'prompt_tokens' => 3,
                'completion_tokens' => 4,
            ],
        ], 200, [
            'x-request-id' => 'req_log',
        ]),
    ]);

    app(LlmProvider::class)->generate(new LlmRequestData(
        messages: [new LlmMessageData('user', 'Hello')],
        idempotencyKey: 'client-request-log',
    ));

    Log::shouldHaveReceived('info')
        ->with(
            'llm.provider.request',
            \Mockery::on(static function (array $context): bool {
                return ($context['headers']['Authorization'] ?? null) === '[REDACTED]'
            && ($context['headers']['OpenAI-Organization'] ?? null) === '[REDACTED]'
            && ($context['headers']['OpenAI-Project'] ?? null) === '[REDACTED]'
            && ! str_contains(json_encode($context, JSON_THROW_ON_ERROR), 'secret-test-key');
            }),
        )
        ->once();
});
