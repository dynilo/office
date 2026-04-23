<?php

namespace App\Application\Executions\Jobs;

use App\Application\Executions\Services\ExecutionLifecycleService;
use App\Domain\Executions\Enums\ExecutionStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StartExecutionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $executionId,
    ) {
        $this->onConnection((string) config('queue.runtime.execution_connection', 'redis'));
        $this->onQueue((string) config('queue.runtime.execution_queue', 'executions'));
    }

    public function tries(): int
    {
        return (int) config('queue.runtime.execution_tries', 3);
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return config('queue.runtime.execution_backoff', [5, 30, 120]);
    }

    public function handle(ExecutionLifecycleService $service): void
    {
        $execution = $service->markRunning($this->executionId);

        if ($execution->status !== ExecutionStatus::Running) {
            return;
        }
    }
}
