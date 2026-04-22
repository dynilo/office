<?php

namespace App\Domain\Tasks\Enums;

enum TaskPriority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Critical = 'critical';

    public function weight(): int
    {
        return match ($this) {
            self::Low => 10,
            self::Normal => 20,
            self::High => 30,
            self::Critical => 40,
        };
    }
}
