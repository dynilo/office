<?php

use App\Support\Memory\EmbeddingProviderRuntimeValidation;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config()->set('providers.embeddings.default', 'openai_compatible');
    config()->set('providers.embeddings.openai_compatible', [
        'base_url' => 'https://api.openai.com/v1',
        'api_key' => 'secret-embedding-key',
        'organization' => 'org_embedding',
        'project' => 'proj_embedding',
        'model' => 'text-embedding-3-small',
        'timeout' => 30,
        'retry_times' => 1,
        'retry_sleep_ms' => 1,
    ]);
    config()->set('memory.pgvector.dimensions', 3);
});

it('reports successful real embedding provider validation when the probe returns normalized output', function (): void {
    Http::fake([
        'https://api.openai.com/v1/embeddings' => Http::response([
            'object' => 'list',
            'model' => 'text-embedding-3-small',
            'data' => [[
                'embedding' => [0.1, 0.2, 0.3],
            ]],
        ], 200),
    ]);

    $report = app(EmbeddingProviderRuntimeValidation::class)->report();

    expect($report['ready'])->toBeTrue()
        ->and($report['provider'])->toBe('openai_compatible')
        ->and($report['runtime']['normalized_model'])->toBe('text-embedding-3-small')
        ->and($report['runtime']['normalized_dimensions'])->toBe(3)
        ->and($report['checks']['probe_completed'])->toBeTrue()
        ->and($report['checks']['dimensions_match_memory_config'])->toBeTrue();
});

it('reports safe fallback behavior when the null embedding provider is selected', function (): void {
    config()->set('providers.embeddings.default', 'null');

    $report = app(EmbeddingProviderRuntimeValidation::class)->report();

    expect($report['ready'])->toBeFalse()
        ->and($report['provider'])->toBe('null')
        ->and($report['fallback']['safe'])->toBeTrue()
        ->and($report['fallback']['null_provider_available'])->toBeTrue()
        ->and($report['unavailable_reason'])->toBe('real_provider_selected');
});

it('fails safely with a redacted probe error when the real provider is unavailable', function (): void {
    Http::fake(function (): void {
        throw new RuntimeException('Transport failed with secret-embedding-key in diagnostic output.');
    });

    $report = app(EmbeddingProviderRuntimeValidation::class)->report();

    expect($report['ready'])->toBeFalse()
        ->and($report['runtime']['probe_error']['message'])->not->toContain('secret-embedding-key')
        ->and($report['runtime']['probe_error']['message'])->toBe('Unexpected embedding provider failure.')
        ->and($report['fallback']['safe'])->toBeTrue()
        ->and($report['checks']['probe_completed'])->toBeFalse();
});
