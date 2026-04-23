<?php

namespace App\Application\Audit\Data;

final readonly class AuditSubjectData
{
    public function __construct(
        public ?string $type = null,
        public ?string $id = null,
    ) {
    }
}
