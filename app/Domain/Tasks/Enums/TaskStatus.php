<?php

namespace App\Domain\Tasks\Enums;

enum TaskStatus: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Completed, self::Failed, self::Cancelled => true,
            self::Pending, self::Queued, self::InProgress => false,
        };
    }
}
