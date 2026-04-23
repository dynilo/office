<?php

use App\Application\Providers\Contracts\LlmProvider;
use App\Application\Providers\Data\LlmMessageData;
use App\Application\Providers\Data\LlmRequestData;
use App\Application\Providers\Data\LlmResponseData;
use App\Application\Providers\Exceptions\LlmProviderException;
use App\Application\Providers\Services\ProviderFailoverService;
use Illuminate\Support\Facades\Http;

it('falls back from primary to secondary on retriable provider failure deterministically', function (): void {
    $primary = new class implements LlmProvider
    {
        public int $calls = 0;

        public function generate(LlmRequestData $request): LlmResponseData
        {
            $this->calls++;

            throw LlmProviderException::response(
                provider: 'primary',
                message: 'Primary overloaded.',
                statusCode: 503,
                errorCode: 'overloaded',
                retriable: true,
            );
        }
    };
    $secondary = new class implements LlmProvider
    {
        public int $calls = 0;

        public function generate(LlmRequestData $request): LlmResponseData
        {
            $this->calls++;

            return new LlmResponseData(
                provider: 'secondary',
                responseId: 'resp_secondary',
                model: 'gpt-secondary',
                content: 'Recovered by secondary.',
                finishReason: 'stop',
                inputTokens: 10,
                outputTokens: 5,
                requestId: 'req_secondary',
            );
        }
    };

    $response = new ProviderFailoverService(
        providers: [
            'primary' => $primary,
            'secondary' => $secondary,
        ],
        order: ['primary', 'secondary'],
    )->generate(llmRequest());

    expect($response->provider)->toBe('secondary')
        ->and($response->content)->toBe('Recovered by secondary.')
        ->and($primary->calls)->toBe(1)
        ->and($secondary->calls)->toBe(1);
});

it('does not fall back on non-retriable provider failure by default', function (): void {
    $primary = new class implements LlmProvider
    {
        public function generate(LlmRequestData $request): LlmResponseData
        {
            throw LlmProviderException::response(
                provider: 'primary',
                message: 'Invalid payload.',
                statusCode: 400,
                errorCode: 'invalid_payload',
                retriable: false,
            );
        }
    };
    $secondary = new class implements LlmProvider
    {
        public int $calls = 0;

        public function generate(LlmRequestData $request): LlmResponseData
        {
            $this->calls++;

            return new LlmResponseData('secondary', 'resp', 'model', 'unused', 'stop', null, null, null);
        }
    };

    expect(fn () => new ProviderFailoverService(
        providers: [
            'primary' => $primary,
            'secondary' => $secondary,
        ],
        order: ['primary', 'secondary'],
    )->generate(llmRequest()))
        ->toThrow(function (LlmProviderException $exception) use ($secondary): bool {
            expect($exception->provider)->toBe('primary')
                ->and($exception->errorCode)->toBe('invalid_payload')
                ->and($secondary->calls)->toBe(0);

            return true;
        });
});

it('can be configured to fall back on all normalized provider failures', function (): void {
    $primary = new class implements LlmProvider
    {
        public function generate(LlmRequestData $request): LlmResponseData
        {
            throw LlmProviderException::response(
                provider: 'primary',
                message: 'Invalid payload.',
                statusCode: 400,
                errorCode: 'invalid_payload',
                retriable: false,
            );
        }
    };
    $secondary = new class implements LlmProvider
    {
        public function generate(LlmRequestData $request): LlmResponseData
        {
            return new LlmResponseData('secondary', 'resp', 'model', 'fallback', 'stop', null, null, null);
        }
    };

    $response = new ProviderFailoverService(
        providers: [
            'primary' => $primary,
            'secondary' => $secondary,
        ],
        order: ['primary', 'secondary'],
        fallbackOnRetriableOnly: false,
    )->generate(llmRequest());

    expect($response->content)->toBe('fallback');
});

it('binds the configured failover provider through the application container', function (): void {
    config()->set('providers.default', 'failover');
    config()->set('providers.failover.order', ['openai_compatible', 'openai_compatible_secondary']);
    config()->set('providers.failover.fallback_on_retriable_only', true);
    config()->set('providers.openai_compatible', [
        'base_url' => 'https://primary.example/v1',
        'api_key' => 'primary-key',
        'organization' => null,
        'project' => null,
        'model' => 'gpt-primary',
        'timeout' => 30,
        'retry_times' => 0,
        'retry_sleep_ms' => 1,
        'store' => false,
    ]);
    config()->set('providers.openai_compatible_secondary', [
        'base_url' => 'https://secondary.example/v1',
        'api_key' => 'secondary-key',
        'organization' => null,
        'project' => null,
        'model' => 'gpt-secondary',
        'timeout' => 30,
        'retry_times' => 0,
        'retry_sleep_ms' => 1,
        'store' => false,
    ]);

    Http::fake([
        'https://primary.example/v1/chat/completions' => Http::response([
            'error' => [
                'message' => 'Primary unavailable.',
                'type' => 'server_error',
                'code' => 'unavailable',
            ],
        ], 503),
        'https://secondary.example/v1/chat/completions' => Http::response([
            'id' => 'chatcmpl_secondary',
            'model' => 'gpt-secondary',
            'choices' => [[
                'finish_reason' => 'stop',
                'message' => [
                    'content' => 'Secondary response.',
                ],
            ]],
        ]),
    ]);

    $response = app(LlmProvider::class)->generate(llmRequest());

    expect($response->content)->toBe('Secondary response.');
    Http::assertSentCount(2);
});

function llmRequest(): LlmRequestData
{
    return new LlmRequestData([
        new LlmMessageData('user', 'Produce a response.'),
    ]);
}
