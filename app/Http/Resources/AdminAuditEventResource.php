<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminAuditEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_name' => $this->event_name,
            'auditable_type' => $this->auditable_type,
            'auditable_id' => $this->auditable_id,
            'actor_type' => $this->actor_type,
            'actor_id' => $this->actor_id,
            'source' => $this->source,
            'metadata' => $this->metadata ?? [],
            'occurred_at' => $this->occurred_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
