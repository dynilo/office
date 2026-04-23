<?php

$parseCsv = static function (string $value): array {
    return array_values(array_filter(array_map(
        static fn (string $item): string => trim($item),
        explode(',', $value),
    ), static fn (string $item): bool => $item !== ''));
};

return [
    'enabled' => (bool) env('BACKUP_BASELINE_ENABLED', true),

    'database' => [
        'include' => (bool) env('BACKUP_INCLUDE_DATABASE', true),
        'dump_command' => env('BACKUP_DATABASE_DUMP_COMMAND', 'pg_dump --format=custom --file=storage/app/private/backups/database.dump'),
        'restore_command' => env('BACKUP_DATABASE_RESTORE_COMMAND', 'pg_restore --clean --if-exists --no-owner --dbname=${DB_DATABASE} storage/app/private/backups/database.dump'),
    ],

    'redis' => [
        'include' => (bool) env('BACKUP_INCLUDE_REDIS', true),
        'snapshot_command' => env('BACKUP_REDIS_SNAPSHOT_COMMAND', 'redis-cli --rdb storage/app/private/backups/redis.rdb'),
        'restore_notes' => $parseCsv((string) env(
            'BACKUP_REDIS_RESTORE_NOTES',
            'Restore Redis data out-of-band before restarting workers,Verify queue backlog handling after restore'
        )),
    ],

    'runtime_files' => [
        'include' => (bool) env('BACKUP_INCLUDE_RUNTIME_FILES', true),
        'manifest_disk' => env('BACKUP_MANIFEST_DISK', env('FILESYSTEM_DISK', 'local')),
        'manifest_path' => env('BACKUP_MANIFEST_PATH', 'backups/runtime-manifest.json'),
    ],
];
