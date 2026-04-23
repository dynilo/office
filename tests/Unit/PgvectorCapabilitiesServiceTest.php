<?php

use App\Application\Memory\Services\PgvectorCapabilitiesService;

it('reports pgvector as unavailable on non-postgresql connections', function (): void {
    $service = app(PgvectorCapabilitiesService::class);
    $report = $service->readinessReport();

    expect($service->supportsVectorStorage())->toBeFalse()
        ->and($service->supportsSimilaritySearch())->toBeFalse()
        ->and($report['driver'])->toBe('sqlite')
        ->and($report['ready_for_storage'])->toBeFalse()
        ->and($report['ready_for_search'])->toBeFalse()
        ->and($report['unavailable_reason'])->toBe('connection_is_pgsql');
});

it('reports pgvector readiness configuration without requiring pgvector to be installed', function (): void {
    config()->set('memory.pgvector.dimensions', 3);
    config()->set('memory.pgvector.distance', 'cosine');
    config()->set('memory.pgvector.index.enabled', true);
    config()->set('memory.pgvector.index.method', 'hnsw');
    config()->set('memory.pgvector.index.name', 'knowledge_items_embedding_hnsw_idx');

    $report = app(PgvectorCapabilitiesService::class)->readinessReport();

    expect($report['dimensions'])->toBe(3)
        ->and($report['distance'])->toBe('cosine')
        ->and($report['index']['enabled'])->toBeTrue()
        ->and($report['index']['method'])->toBe('hnsw')
        ->and($report['index']['name'])->toBe('knowledge_items_embedding_hnsw_idx');
});

it('validates embedding dimensions against configured pgvector dimensions', function (): void {
    config()->set('memory.pgvector.dimensions', 3);

    $service = app(PgvectorCapabilitiesService::class);

    expect($service->vectorDimensionsAreValid([0.1, 0.2, 0.3]))->toBeTrue()
        ->and($service->vectorDimensionsAreValid([0.1, 0.2]))->toBeFalse()
        ->and($service->vectorDimensionsAreValid([]))->toBeFalse();
});

it('marks unsupported pgvector distance configuration as not search ready', function (): void {
    config()->set('memory.pgvector.distance', 'unsupported-distance');

    $report = app(PgvectorCapabilitiesService::class)->readinessReport();

    expect($report['checks']['distance_supported'])->toBeFalse()
        ->and($report['ready_for_search'])->toBeFalse();
});
