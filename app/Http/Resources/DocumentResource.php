<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'mime_type' => $this->mime_type,
            'storage_disk' => $this->storage_disk,
            'storage_path' => $this->storage_path,
            'checksum' => $this->checksum,
            'size_bytes' => $this->size_bytes,
            'raw_text' => $this->raw_text,
            'metadata' => $this->metadata ?? [],
            'ingested_at' => $this->ingested_at?->toIso8601String(),
            'text_extracted_at' => $this->text_extracted_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
