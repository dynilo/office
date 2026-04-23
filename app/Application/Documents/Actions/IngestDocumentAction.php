<?php

namespace App\Application\Documents\Actions;

use App\Application\Documents\Services\DocumentParserRegistry;
use App\Infrastructure\Persistence\Eloquent\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class IngestDocumentAction
{
    public function __construct(
        private readonly DocumentParserRegistry $parsers,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(array $payload): Document
    {
        /** @var UploadedFile $file */
        $file = $payload['file'];
        $disk = (string) config('documents.storage_disk', 'local');
        $storedPath = $file->storeAs(
            'documents/'.now()->format('Y/m/d'),
            Str::ulid().'_'.$file->getClientOriginalName(),
            $disk,
        );
        $mimeType = $file->getMimeType() ?: $file->getClientMimeType() ?: 'application/octet-stream';
        $parsed = $this->parsers->parse($disk, $storedPath, $mimeType);

        return Document::query()->create([
            'title' => $payload['title'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'mime_type' => $mimeType,
            'storage_disk' => $disk,
            'storage_path' => $storedPath,
            'checksum' => hash('sha256', Storage::disk($disk)->get($storedPath)),
            'size_bytes' => $file->getSize() ?? Storage::disk($disk)->size($storedPath),
            'raw_text' => $parsed->rawText,
            'metadata' => [
                'original_filename' => $file->getClientOriginalName(),
                'extension' => $file->getClientOriginalExtension(),
                'ingestion' => [
                    'parser' => $parsed->metadata['parser'] ?? null,
                ],
                ...($payload['metadata'] ?? []),
                'extraction' => $parsed->metadata,
            ],
            'ingested_at' => now(),
            'text_extracted_at' => now(),
        ]);
    }
}
