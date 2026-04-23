<?php

use App\Application\Memory\Contracts\EmbeddingGenerator;
use App\Application\Memory\Exceptions\EmbeddingProviderException;
use App\Infrastructure\Memory\NullEmbeddingGenerator;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function (): void {
    config()->set('providers.embeddings.default', 'openai_compatible');
    config()->set('providers.embeddings.openai_compatible', [
        'base_url' => 'https://api.openai.com/v1',
        'api_key' => 'secret-embedding-key',
        'organization' => 'org_embedding',
        'project' => 'proj_embedding',
        'model' => 'text-embedding-3-small',
        'timeout' => 30,
        'retry_times' => 2,
        'retry_sleep_ms' => 1,
    ]);
});

it('generates normalized embeddings through the embedding contract', function (): void {
    Http::fake([
        'https://api.openai.com/v1/embeddings' => Http::response([
            'object' => 'list',
            'model' => 'text-embedding-3-small',
            'data' => [[
                'object' => 'embedding',
                'index' => 0,
                'embedding' => [0.1, 0, -0.25],
            ]],
            'usage' => [
                'prompt_tokens' => 4,
                'total_tokens' => 4,
            ],
        ], 200, [
            'x-request-id' => 'req_embed_123',
        ]),
    ]);

    $embedding = app(EmbeddingGenerator::class)->generate('Operational memory context');

    expect($embedding->model)->toBe('text-embedding-3-small')
        ->and($embedding->vector)->toBe([0.1, 0.0, -0.25])
        ->and($embedding->dimensions())->toBe(3);

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://api.openai.com/v1/embeddings'
            && $request->hasHeader('Authorization', 'Bearer secret-embedding-key')
            && $request->hasHeader('OpenAI-Organization', 'org_embedding')
            && $request->hasHeader('OpenAI-Project', 'proj_embedding')
            && $request['model'] === 'text-embedding-3-small'
            && $request['input'] === 'Operational memory context'
            && $request['encoding_format'] === 'float';
    });
});

it('retries retriable embedding failures and normalizes the eventual vector', function (): void {
    Http::fakeSequence()
        ->push([
            'error' => [
                'message' => 'Rate limited.',
                'type' => 'rate_limit_error',
                'code' => 'rate_limit_exceeded',
            ],
        ], 429)
        ->push([
            'model' => 'text-embedding-3-small',
            'data' => [[
                'embedding' => [1, 2, 3],
            ]],
        ], 200, [
            'x-request-id' => 'req_embed_retry',
        ]);

    $embedding = app(EmbeddingGenerator::class)->generate('Retry embedding');

    expect($embedding->vector)->toBe([1.0, 2.0, 3.0]);
    Http::assertSentCount(2);
});

it('normalizes embedding provider failures', function (): void {
    Http::fake([
        'https://api.openai.com/v1/embeddings' => Http::response([
            'error' => [
                'message' => 'Invalid embedding request.',
                'type' => 'invalid_request_error',
                'code' => 'invalid_embedding_input',
            ],
        ], 400, [
            'x-request-id' => 'req_embed_bad',
        ]),
    ]);

    expect(fn () => app(EmbeddingGenerator::class)->generate('Bad embedding input'))
        ->toThrow(function (EmbeddingProviderException $exception): bool {
            expect($exception->provider)->toBe('openai_compatible')
                ->and($exception->statusCode)->toBe(400)
                ->and($exception->errorCode)->toBe('invalid_embedding_input')
                ->and($exception->retriable)->toBeFalse()
                ->and($exception->context['request_id'] ?? null)->toBe('req_embed_bad');

            return $exception->getMessage() === 'Invalid embedding request.';
        });
});

it('rejects invalid embedding response shapes', function (): void {
    Http::fake([
        'https://api.openai.com/v1/embeddings' => Http::response([
            'model' => 'text-embedding-3-small',
            'data' => [[
                'embedding' => ['not-a-float'],
            ]],
        ], 200, [
            'x-request-id' => 'req_embed_invalid',
        ]),
    ]);

    expect(fn () => app(EmbeddingGenerator::class)->generate('Invalid response shape'))
        ->toThrow(function (EmbeddingProviderException $exception): bool {
            expect($exception->errorCode)->toBe('invalid_embedding_response')
                ->and($exception->retriable)->toBeFalse()
                ->and($exception->context['request_id'] ?? null)->toBe('req_embed_invalid');

            return true;
        });
});

it('logs embedding requests without secrets or raw input', function (): void {
    Log::spy();
    Http::fake([
        'https://api.openai.com/v1/embeddings' => Http::response([
            'model' => 'text-embedding-3-small',
            'data' => [[
                'embedding' => [0.5, 0.25],
            ]],
            'usage' => [
                'prompt_tokens' => 3,
            ],
        ], 200),
    ]);

    app(EmbeddingGenerator::class)->generate('Secret customer context');

    Log::shouldHaveReceived('info')
        ->with(
            'embedding.provider.request',
            Mockery::on(static function (array $context): bool {
                $encoded = json_encode($context, JSON_THROW_ON_ERROR);

                return ($context['headers']['Authorization'] ?? null) === '[REDACTED]'
                    && ($context['headers']['OpenAI-Organization'] ?? null) === '[REDACTED]'
                    && ($context['headers']['OpenAI-Project'] ?? null) === '[REDACTED]'
                    && isset($context['payload']['input_sha256'])
                    && ! str_contains($encoded, 'secret-embedding-key')
                    && ! str_contains($encoded, 'Secret customer context');
            }),
        )
        ->once();
});

it('redacts secrets from embedding provider failure logs', function (): void {
    Log::spy();
    Http::fake(function (): void {
        throw new RuntimeException('Transport failed with secret-embedding-key in diagnostic output.');
    });

    expect(fn () => app(EmbeddingGenerator::class)->generate('Trigger embedding failure'))
        ->toThrow(EmbeddingProviderException::class);

    Log::shouldHaveReceived('warning')
        ->with(
            'embedding.provider.failure',
            Mockery::on(static function (array $context): bool {
                $encoded = json_encode($context, JSON_THROW_ON_ERROR);

                return ! str_contains($encoded, 'secret-embedding-key')
                    && str_contains($encoded, '[REDACTED]');
            }),
        )
        ->once();
});

it('can still bind the explicit null embedding fallback when configured', function (): void {
    config()->set('providers.embeddings.default', 'null');

    expect(app(EmbeddingGenerator::class))->toBeInstanceOf(NullEmbeddingGenerator::class);
});
