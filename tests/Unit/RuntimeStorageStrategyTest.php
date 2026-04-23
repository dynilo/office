<?php

use App\Support\Storage\RuntimeStorageStrategy;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

it('builds explicit document storage paths with configured prefix and safe filenames', function (): void {
    Carbon::setTestNow('2026-04-23 12:00:00');
    config()->set('runtime_storage.documents.path_prefix', 'runtime-documents');

    $path = app(RuntimeStorageStrategy::class)->documentPathForUpload(
        UploadedFile::fake()->createWithContent('Research Notes.txt', 'Alpha')
    );

    expect($path)->toStartWith('runtime-documents/2026/04/23/')
        ->and($path)->toEndWith('_research-notes.txt');
});

it('normalizes artifact file metadata with explicit storage intent', function (): void {
    config()->set('runtime_storage.artifacts.disk', 'local');
    config()->set('runtime_storage.artifacts.allowed_disks', ['local']);

    $metadata = app(RuntimeStorageStrategy::class)->normalizeArtifactFileMetadata([
        'path' => '/artifacts//reports/final.txt',
        'mime_type' => 'text/plain',
    ]);

    expect($metadata['disk'])->toBe('local')
        ->and($metadata['path'])->toBe('artifacts/reports/final.txt')
        ->and($metadata['storage_intent'])->toBe('runtime_artifact')
        ->and($metadata['mime_type'])->toBe('text/plain');
});

it('rejects unsafe runtime storage traversal paths', function (): void {
    expect(fn () => app(RuntimeStorageStrategy::class)->normalizeRelativePath('documents/../secrets.txt'))
        ->toThrow(InvalidArgumentException::class);
});

it('reports public runtime storage as unsafe in production by default', function (): void {
    config()->set('app.env', 'production');
    config()->set('runtime_storage.documents.disk', 'public');
    config()->set('runtime_storage.artifacts.disk', 'local');
    config()->set('runtime_storage.documents.allowed_disks', ['public', 'local']);
    config()->set('runtime_storage.artifacts.allowed_disks', ['local']);
    config()->set('runtime_storage.production.disallow_public_runtime_storage', true);

    $report = app(RuntimeStorageStrategy::class)->report();

    expect($report['ready'])->toBeFalse()
        ->and($report['unavailable_reason'])->toBe('production_private_runtime_storage');
});
