<?php

namespace App\Domain\Tasks\Enums;

enum TaskStatus: string
{
    case Draft = 'draft';
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
            self::Draft, self::Pending, self::Queued, self::InProgress => false,
        };
    }

    /**
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Queued, self::Cancelled],
            self::Pending => [self::Queued, self::Cancelled],
            self::Queued => [self::InProgress, self::Cancelled],
            self::InProgress => [self::Completed, self::Failed, self::Cancelled],
            self::Completed, self::Failed, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
