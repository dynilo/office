<?php

use Illuminate\Support\Facades\Artisan;

it('exposes retrieval quality validation through a console command', function (): void {
    config()->set('context.retrieval.top_k', 3);
    config()->set('context.retrieval.max_distance', 0.8);
    config()->set('memory.pgvector.distance', 'cosine');

    $exitCode = Artisan::call('retrieval:validate-quality');
    $output = Artisan::output();

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('"ready": true')
        ->and($output)->toContain('"deterministic_ordering": true')
        ->and($output)->toContain('"threshold_filtering": true');
});

it('documents retrieval quality validation and safe fallback behavior', function (): void {
    $contents = file_get_contents(base_path('docs/RETRIEVAL_QUALITY_PRODUCTION.md'));

    expect($contents)->toContain('php artisan retrieval:validate-quality')
        ->and($contents)->toContain('CONTEXT_RETRIEVAL_MAX_DISTANCE')
        ->and($contents)->toContain('empty context block set');
});
