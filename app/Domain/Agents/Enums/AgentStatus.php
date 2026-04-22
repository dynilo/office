<?php

namespace App\Domain\Agents\Enums;

enum AgentStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';

    public function isOperational(): bool
    {
        return $this === self::Active;
    }
}
