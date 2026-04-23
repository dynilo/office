<?php

namespace App\Support\Storage;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class RuntimeStorageStrategy
{
    public function documentDisk(): string
    {
        return $this->disk('documents', (string) config('documents.storage_disk', 'local'));
    }

    public function artifactDisk(): string
    {
        return $this->disk('artifacts', (string) config('filesystems.default', 'local'));
    }

    public function documentPathForUpload(UploadedFile $file): string
    {
        $originalName = $file->getClientOriginalName();
        $safeName = $this->safeFilename($originalName !== '' ? $originalName : 'document');

        return $this->joinPath(
            $this->pathPrefix('documents', 'documents'),
            now()->format('Y/m/d'),
            Str::ulid().'_'.$safeName,
        );
    }

    /**
     * @param  array<string, mixed>|null  $fileMetadata
     * @return array<string, mixed>|null
     */
    public function normalizeArtifactFileMetadata(?array $fileMetadata): ?array
    {
        if ($fileMetadata === null) {
            return null;
        }

        $disk = (string) ($fileMetadata['disk'] ?? $this->artifactDisk());
        $path = (string) ($fileMetadata['path'] ?? '');

        if ($path === '') {
            throw new InvalidArgumentException('Artifact file metadata requires a storage path.');
        }

        $this->ensureAllowedDisk('artifacts', $disk);

        return [
            ...$fileMetadata,
            'disk' => $disk,
            'path' => $this->normalizeRelativePath($path),
            'storage_intent' => 'runtime_artifact',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        $documentDisk = $this->documentDisk();
        $artifactDisk = $this->artifactDisk();
        $production = config('app.env') === 'production';

        $checks = [
            'document_disk_configured' => filled($documentDisk),
            'artifact_disk_configured' => filled($artifactDisk),
            'document_prefix_configured' => filled($this->pathPrefix('documents', 'documents')),
            'artifact_prefix_configured' => filled($this->pathPrefix('artifacts', 'artifacts')),
            'document_disk_allowed' => $this->isAllowedDisk('documents', $documentDisk),
            'artifact_disk_allowed' => $this->isAllowedDisk('artifacts', $artifactDisk),
            'production_private_runtime_storage' => ! $production
                || ! (bool) config('runtime_storage.production.disallow_public_runtime_storage', true)
                || ($documentDisk !== 'public' && $artifactDisk !== 'public'),
        ];

        return [
            'environment' => (string) config('app.env'),
            'documents' => [
                'disk' => $documentDisk,
                'path_prefix' => $this->pathPrefix('documents', 'documents'),
                'allowed_disks' => $this->allowedDisks('documents'),
            ],
            'artifacts' => [
                'disk' => $artifactDisk,
                'path_prefix' => $this->pathPrefix('artifacts', 'artifacts'),
                'allowed_disks' => $this->allowedDisks('artifacts'),
            ],
            'checks' => $checks,
            'ready' => ! in_array(false, $checks, true),
            'unavailable_reason' => $this->firstFailedCheck($checks),
        ];
    }

    public function normalizeRelativePath(string $path): string
    {
        $normalized = str_replace('\\', '/', trim($path));
        $normalized = preg_replace('#/+#', '/', $normalized) ?: '';
        $normalized = ltrim($normalized, '/');

        $segments = array_filter(explode('/', $normalized), static fn (string $segment): bool => $segment !== '');

        foreach ($segments as $segment) {
            if ($segment === '.' || $segment === '..') {
                throw new InvalidArgumentException('Runtime storage paths must be relative and must not contain traversal segments.');
            }
        }

        if ($normalized === '') {
            throw new InvalidArgumentException('Runtime storage paths must not be blank.');
        }

        return $normalized;
    }

    private function disk(string $scope, string $fallback): string
    {
        return (string) config("runtime_storage.{$scope}.disk", $fallback);
    }

    private function pathPrefix(string $scope, string $fallback): string
    {
        return $this->normalizeRelativePath((string) config("runtime_storage.{$scope}.path_prefix", $fallback));
    }

    private function safeFilename(string $filename): string
    {
        $basename = basename(str_replace('\\', '/', $filename));
        $extension = pathinfo($basename, PATHINFO_EXTENSION);
        $name = pathinfo($basename, PATHINFO_FILENAME);
        $slug = Str::slug($name !== '' ? $name : 'file');

        return $extension !== '' ? "{$slug}.{$extension}" : $slug;
    }

    private function joinPath(string ...$segments): string
    {
        return implode('/', array_map(
            fn (string $segment): string => trim($this->normalizeRelativePath($segment), '/'),
            $segments,
        ));
    }

    /**
     * @return array<int, string>
     */
    private function allowedDisks(string $scope): array
    {
        return array_values((array) config("runtime_storage.{$scope}.allowed_disks", []));
    }

    private function ensureAllowedDisk(string $scope, string $disk): void
    {
        if (! $this->isAllowedDisk($scope, $disk)) {
            throw new InvalidArgumentException("Disk [{$disk}] is not allowed for {$scope} runtime storage.");
        }
    }

    private function isAllowedDisk(string $scope, string $disk): bool
    {
        return in_array($disk, $this->allowedDisks($scope), true);
    }

    /**
     * @param  array<string, bool>  $checks
     */
    private function firstFailedCheck(array $checks): ?string
    {
        foreach ($checks as $check => $passed) {
            if (! $passed) {
                return $check;
            }
        }

        return null;
    }
}
