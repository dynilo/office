<?php

namespace App\Application\Audit\Data;

use Carbon\CarbonImmutable;

final readonly class AuditEventData
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $eventName,
        public ?AuditSubjectData $subject = null,
        public ?AuditActorData $actor = null,
        public ?string $source = null,
        public array $metadata = [],
        public ?CarbonImmutable $occurredAt = null,
    ) {
    }
}
