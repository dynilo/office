<?php

$parseCsv = static function (string $value): array {
    return array_values(array_filter(array_map(
        static fn (string $item): string => trim($item),
        explode(',', $value),
    ), static fn (string $item): bool => $item !== ''));
};

return [
    /*
    |--------------------------------------------------------------------------
    | Runtime Storage Strategy
    |--------------------------------------------------------------------------
    |
    | Documents and artifacts are stored as private runtime assets by default.
    | Cloud disks can be configured later, but this slice only makes disk and
    | path intent explicit without introducing a cloud integration.
    |
    */

    'documents' => [
        'disk' => env('DOCUMENT_STORAGE_DISK', env('FILESYSTEM_DISK', 'local')),
        'path_prefix' => trim((string) env('DOCUMENT_STORAGE_PREFIX', 'documents'), '/'),
        'allowed_disks' => $parseCsv((string) env('DOCUMENT_STORAGE_ALLOWED_DISKS', 'local,s3')),
    ],

    'artifacts' => [
        'disk' => env('ARTIFACT_STORAGE_DISK', env('FILESYSTEM_DISK', 'local')),
        'path_prefix' => trim((string) env('ARTIFACT_STORAGE_PREFIX', 'artifacts'), '/'),
        'allowed_disks' => $parseCsv((string) env('ARTIFACT_STORAGE_ALLOWED_DISKS', 'local,s3,private')),
    ],

    'production' => [
        'disallow_public_runtime_storage' => env('RUNTIME_STORAGE_DISALLOW_PUBLIC_IN_PRODUCTION', true),
    ],
];
