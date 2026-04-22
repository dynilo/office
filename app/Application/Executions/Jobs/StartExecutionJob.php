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
    }

    public function handle(ExecutionLifecycleService $service): void
    {
        $execution = $service->markRunning($this->executionId);

        if ($execution->status !== ExecutionStatus::Running) {
            return;
        }
    }
}
