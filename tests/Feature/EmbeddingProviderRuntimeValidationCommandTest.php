<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

it('exposes embedding provider runtime validation through a console command', function (): void {
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

    Http::fake([
        'https://api.openai.com/v1/embeddings' => Http::response([
            'object' => 'list',
            'model' => 'text-embedding-3-small',
            'data' => [[
                'embedding' => [0.1, 0.2, 0.3],
            ]],
        ], 200),
    ]);

    $exitCode = Artisan::call('embedding-provider:validate-runtime');
    $output = Artisan::output();

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('"provider": "openai_compatible"')
        ->and($output)->toContain('"ready": true');
});

it('documents embedding provider runtime validation and fallback behavior', function (): void {
    $contents = file_get_contents(base_path('docs/EMBEDDING_PROVIDER_PRODUCTION.md'));

    expect($contents)->toContain('php artisan embedding-provider:validate-runtime')
        ->and($contents)->toContain('EMBEDDING_PROVIDER=null')
        ->and($contents)->toContain('MEMORY_EMBEDDING_DIMENSIONS');
});
