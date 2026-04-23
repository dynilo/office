<?php

return [
    'storage_disk' => env('DOCUMENT_STORAGE_DISK', env('FILESYSTEM_DISK', 'local')),
    'storage_prefix' => env('DOCUMENT_STORAGE_PREFIX', 'documents'),
];
