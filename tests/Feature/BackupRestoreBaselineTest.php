<?php

use App\Support\Backup\BackupBaselineService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

it('builds a backup plan from runtime storage and infrastructure configuration', function (): void {
    config()->set('database.default', 'pgsql');
    config()->set('queue.default', 'redis');
    config()->set('cache.default', 'redis');
    config()->set('backup.database.dump_command', 'pg_dump custom');
    config()->set('backup.redis.snapshot_command', 'redis-cli snapshot');
    config()->set('backup.runtime_files.manifest_disk', 'local');
    config()->set('backup.runtime_files.manifest_path', 'backups/runtime-manifest.json');
    config()->set('runtime_storage.documents.disk', 'local');
    config()->set('runtime_storage.documents.allowed_disks', ['local']);
    config()->set('runtime_storage.artifacts.disk', 'local');
    config()->set('runtime_storage.artifacts.allowed_disks', ['local']);

    $plan = app(BackupBaselineService::class)->backupPlan();

    expect($plan['database']['connection'])->toBe('pgsql')
        ->and($plan['database']['dump_command'])->toBe('pg_dump custom')
        ->and($plan['redis']['queue_connection'])->toBe('redis')
        ->and($plan['redis']['snapshot_command'])->toBe('redis-cli snapshot')
        ->and($plan['runtime_files']['manifest_path'])->toBe('backups/runtime-manifest.json')
        ->and($plan['runtime_files']['documents']['disk'])->toBe('local')
        ->and($plan['checks']['runtime_storage_ready'])->toBeTrue();
});

it('builds a restore plan with an ordered recovery sequence', function (): void {
    config()->set('backup.database.restore_command', 'pg_restore restore');
    config()->set('backup.redis.restore_notes', [
        'Restore Redis data first.',
        'Restart workers after restore.',
    ]);

    $plan = app(BackupBaselineService::class)->restorePlan();

    expect($plan['database']['restore_command'])->toBe('pg_restore restore')
        ->and($plan['redis']['restore_notes'])->toBe([
            'Restore Redis data first.',
            'Restart workers after restore.',
        ])
        ->and($plan['sequence'][0])->toContain('maintenance mode')
        ->and($plan['sequence'][5])->toContain('/api/health');
});

it('exposes backup and restore manifests through console commands', function (): void {
    expect(Artisan::call('backup:manifest'))->toBe(0)
        ->and(json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR))->toHaveKeys([
            'enabled',
            'database',
            'redis',
            'runtime_files',
            'checks',
        ]);

    expect(Artisan::call('restore:manifest'))->toBe(0)
        ->and(json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR))->toHaveKeys([
            'enabled',
            'sequence',
            'database',
            'redis',
        ]);
});

it('documents the backup and restore baseline', function (): void {
    $documentPath = base_path('docs/BACKUP_RESTORE_BASELINE.md');

    expect(File::exists($documentPath))->toBeTrue();

    $document = File::get($documentPath);

    expect($document)->toContain('PostgreSQL')
        ->toContain('Redis')
        ->toContain('php artisan backup:manifest')
        ->toContain('php artisan restore:manifest')
        ->toContain('pg_restore');
});
