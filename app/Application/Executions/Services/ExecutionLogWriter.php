<?php

namespace App\Application\Executions\Services;

use App\Infrastructure\Persistence\Eloquent\Models\Execution;

final class ExecutionLogWriter
{
    public function write(Execution $execution, string $level, string $message, array $context = []): void
    {
        $sequence = (int) $execution->logs()->max('sequence') + 1;

        $execution->logs()->create([
            'sequence' => $sequence,
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'logged_at' => now(),
        ]);
    }
}
