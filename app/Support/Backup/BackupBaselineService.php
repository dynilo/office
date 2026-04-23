<?php

namespace App\Support\Backup;

use App\Support\Storage\RuntimeStorageStrategy;

final class BackupBaselineService
{
    public function __construct(
        private readonly RuntimeStorageStrategy $runtimeStorage,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function backupPlan(): array
    {
        $runtimeStorage = $this->runtimeStorage->report();

        return [
            'enabled' => (bool) config('backup.enabled', true),
            'database' => [
                'include' => (bool) config('backup.database.include', true),
                'connection' => (string) config('database.default', 'pgsql'),
                'dump_command' => (string) config('backup.database.dump_command'),
            ],
            'redis' => [
                'include' => (bool) config('backup.redis.include', true),
                'queue_connection' => (string) config('queue.default', 'redis'),
                'cache_store' => (string) config('cache.default', config('cache.store', 'redis')),
                'snapshot_command' => (string) config('backup.redis.snapshot_command'),
            ],
            'runtime_files' => [
                'include' => (bool) config('backup.runtime_files.include', true),
                'manifest_disk' => (string) config('backup.runtime_files.manifest_disk', 'local'),
                'manifest_path' => (string) config('backup.runtime_files.manifest_path', 'backups/runtime-manifest.json'),
                'documents' => $runtimeStorage['documents'],
                'artifacts' => $runtimeStorage['artifacts'],
            ],
            'checks' => $this->checks($runtimeStorage),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function restorePlan(): array
    {
        return [
            'enabled' => (bool) config('backup.enabled', true),
            'sequence' => [
                'Enable maintenance mode before restore.',
                'Restore the PostgreSQL backup before queue or worker restart.',
                'Restore runtime document and artifact files onto their configured disks and paths.',
                'Restore Redis snapshots out-of-band if queue or cache continuity is required.',
                'Run migrations only if the restored database is behind the application code.',
                'Restart queues and verify /api/health after restore.',
            ],
            'database' => [
                'restore_command' => (string) config('backup.database.restore_command'),
            ],
            'redis' => [
                'restore_notes' => array_values((array) config('backup.redis.restore_notes', [])),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $runtimeStorage
     * @return array<string, bool>
     */
    private function checks(array $runtimeStorage): array
    {
        return [
            'backup_enabled' => (bool) config('backup.enabled', true),
            'database_included' => (bool) config('backup.database.include', true),
            'runtime_storage_ready' => (bool) ($runtimeStorage['ready'] ?? false),
            'manifest_disk_configured' => filled((string) config('backup.runtime_files.manifest_disk', 'local')),
            'manifest_path_configured' => filled((string) config('backup.runtime_files.manifest_path', 'backups/runtime-manifest.json')),
        ];
    }
}
