<?php

namespace App\Application\Audit\Services;

use App\Application\Audit\Data\AuditEventData;
use App\Infrastructure\Persistence\Eloquent\Models\AuditEvent;

final class AuditEventWriter
{
    public function write(AuditEventData $event): AuditEvent
    {
        return AuditEvent::query()->create([
            'event_name' => $event->eventName,
            'auditable_type' => $event->subject?->type,
            'auditable_id' => $event->subject?->id,
            'actor_type' => $event->actor?->type,
            'actor_id' => $event->actor?->id,
            'source' => $event->source,
            'metadata' => $event->metadata,
            'occurred_at' => $event->occurredAt ?? now(),
        ]);
    }
}
