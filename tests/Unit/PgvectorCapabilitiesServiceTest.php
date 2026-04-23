<?php

use App\Application\Memory\Services\PgvectorCapabilitiesService;

it('reports pgvector as unavailable on non-postgresql connections', function (): void {
    $service = app(PgvectorCapabilitiesService::class);

    expect($service->supportsVectorStorage())->toBeFalse()
        ->and($service->supportsSimilaritySearch())->toBeFalse();
});
