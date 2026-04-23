<?php

use App\Support\Context\RetrievalQualityValidation;

beforeEach(function (): void {
    config()->set('context.retrieval.top_k', 3);
    config()->set('context.retrieval.max_distance', 0.8);
    config()->set('memory.pgvector.distance', 'cosine');
});

it('reports retrieval quality as ready when thresholds and deterministic filtering are valid', function (): void {
    $report = app(RetrievalQualityValidation::class)->report();

    expect($report['ready'])->toBeTrue()
        ->and($report['checks']['top_k_positive'])->toBeTrue()
        ->and($report['checks']['max_distance_explicit'])->toBeTrue()
        ->and($report['checks']['deterministic_ordering'])->toBeTrue()
        ->and($report['checks']['duplicate_rejection'])->toBeTrue()
        ->and($report['checks']['threshold_filtering'])->toBeTrue()
        ->and($report['checks']['empty_results_safe'])->toBeTrue()
        ->and($report['runtime']['deterministic_selected_ids'])->toBe([
            '01CRZ3NDEKTSV4RRFFQ69G5FAA',
            '01CRZ3NDEKTSV4RRFFQ69G5FAB',
        ]);
});

it('fails safely when retrieval threshold is not explicit', function (): void {
    config()->set('context.retrieval.max_distance', null);

    $report = app(RetrievalQualityValidation::class)->report();

    expect($report['ready'])->toBeFalse()
        ->and($report['fallback']['safe'])->toBeTrue()
        ->and($report['unavailable_reason'])->toBe('max_distance_explicit');
});

it('fails when the configured threshold is outside the expected metric range', function (): void {
    config()->set('memory.pgvector.distance', 'cosine');
    config()->set('context.retrieval.max_distance', 3.5);

    $report = app(RetrievalQualityValidation::class)->report();

    expect($report['ready'])->toBeFalse()
        ->and($report['checks']['max_distance_within_metric_range'])->toBeFalse()
        ->and($report['unavailable_reason'])->toBe('max_distance_within_metric_range');
});
