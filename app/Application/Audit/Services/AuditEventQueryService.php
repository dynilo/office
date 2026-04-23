<?php

namespace App\Application\Audit\Services;

use App\Infrastructure\Persistence\Eloquent\Models\AuditEvent;
use Illuminate\Database\Eloquent\Collection;

final class AuditEventQueryService
{
    /**
     * @return Collection<int, AuditEvent>
     */
    public function forSubject(string $type, string $id): Collection
    {
        return AuditEvent::query()
            ->where('auditable_type', $type)
            ->where('auditable_id', $id)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return Collection<int, AuditEvent>
     */
    public function forEventName(string $eventName): Collection
    {
        return AuditEvent::query()
            ->where('event_name', $eventName)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->get();
    }
}
