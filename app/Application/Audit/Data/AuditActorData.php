<?php

namespace App\Application\Audit\Data;

final readonly class AuditActorData
{
    public function __construct(
        public ?string $type = null,
        public ?string $id = null,
    ) {
    }
}
