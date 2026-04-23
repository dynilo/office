<?php

use Illuminate\Support\Facades\File;

it('defines ci automation scripts in composer metadata', function (): void {
    $composer = json_decode(File::get(base_path('composer.json')), true, 512, JSON_THROW_ON_ERROR);

    expect(data_get($composer, 'scripts.lint'))->toBe(['./vendor/bin/pint --test'])
        ->and(data_get($composer, 'scripts.ci'))->toBe([
            '@composer validate --strict',
            '@composer lint',
            '@php artisan about --only=environment,cache,drivers',
            '@composer test',
        ]);
});

it('includes a github actions workflow for ci verification', function (): void {
    $workflowPath = base_path('.github/workflows/ci.yml');

    expect(File::exists($workflowPath))->toBeTrue();

    $workflow = File::get($workflowPath);

    expect($workflow)->toContain("php-version: '8.4'")
        ->toContain('composer validate --strict')
        ->toContain('./vendor/bin/pint --test')
        ->toContain('php artisan about --only=environment,cache,drivers')
        ->toContain('php artisan test');
});

it('documents the deployment baseline and runtime assumptions', function (): void {
    $documentPath = base_path('docs/DEPLOYMENT_BASELINE.md');

    expect(File::exists($documentPath))->toBeTrue();

    $document = File::get($documentPath);

    expect($document)->toContain('PostgreSQL')
        ->toContain('Redis')
        ->toContain('php artisan migrate --force')
        ->toContain('php artisan queue:restart')
        ->toContain('/api/health');
});
