<?php

namespace App\Application\Executions\Services;

use App\Application\Organizations\Services\OrganizationSettingsService;
use App\Domain\Executions\Enums\ExecutionStatus;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;

final class ExecutionSlaService
{
    public function __construct(
        private readonly ExecutionLifecycleService $lifecycle,
        private readonly ExecutionLogWriter $logs,
        private readonly DeadLetterService $deadLetters,
    ) {}

    public function failExpiredPendingExecutions(): int
    {
        $processed = 0;

        Execution::query()
            ->where('status', ExecutionStatus::Pending->value)
            ->orderBy('created_at')
            ->get()
            ->each(function (Execution $execution) use (&$processed): void {
                if (! $this->isExpiredPending($execution)) {
                    return;
                }

                $threshold = $this->pendingExpirationSeconds($execution);
                $ageSeconds = $execution->created_at?->diffInSeconds(now()) ?? $threshold;

                $failed = $this->lifecycle->markFailed(
                    executionId: $execution->id,
                    errorMessage: 'Execution expired before it started.',
                    context: [
                        'reason_code' => 'expired_work',
                        'age_seconds' => $ageSeconds,
                        'pending_expiration_seconds' => $threshold,
                    ],
                    failureClassification: 'expired_work',
                );

                $this->deadLetters->captureForExecution(
                    execution: $failed,
                    reasonCode: 'expired_work',
                    errorMessage: 'Execution expired before it started.',
                    payload: [
                        'age_seconds' => $ageSeconds,
                        'pending_expiration_seconds' => $threshold,
                    ],
                );

                $this->logs->write($failed, 'warning', 'execution.dead_lettered', [
                    'reason_code' => 'expired_work',
                ]);

                $processed++;
            });

        return $processed;
    }

    public function failTimedOutRunningExecutions(): int
    {
        $processed = 0;

        Execution::query()
            ->where('status', ExecutionStatus::Running->value)
            ->orderBy('started_at')
            ->get()
            ->each(function (Execution $execution) use (&$processed): void {
                if (! $this->hasTimedOut($execution)) {
                    return;
                }

                $threshold = $this->runningTimeoutSeconds($execution);
                $startedAt = $execution->started_at ?? $execution->created_at;
                $elapsedSeconds = $startedAt?->diffInSeconds(now()) ?? $threshold;

                $failed = $this->lifecycle->markFailed(
                    executionId: $execution->id,
                    errorMessage: 'Execution timed out while running.',
                    context: [
                        'reason_code' => 'execution_timeout',
                        'elapsed_seconds' => $elapsedSeconds,
                        'running_timeout_seconds' => $threshold,
                    ],
                    failureClassification: 'execution_timeout',
                );

                $this->deadLetters->captureForExecution(
                    execution: $failed,
                    reasonCode: 'execution_timeout',
                    errorMessage: 'Execution timed out while running.',
                    payload: [
                        'elapsed_seconds' => $elapsedSeconds,
                        'running_timeout_seconds' => $threshold,
                    ],
                );

                $this->logs->write($failed, 'warning', 'execution.dead_lettered', [
                    'reason_code' => 'execution_timeout',
                ]);

                $processed++;
            });

        return $processed;
    }

    private function isExpiredPending(Execution $execution): bool
    {
        $createdAt = $execution->created_at;

        if ($createdAt === null) {
            return false;
        }

        return $createdAt->lte(now()->subSeconds($this->pendingExpirationSeconds($execution)));
    }

    private function hasTimedOut(Execution $execution): bool
    {
        $startedAt = $execution->started_at ?? $execution->created_at;

        if ($startedAt === null) {
            return false;
        }

        return $startedAt->lte(now()->subSeconds($this->runningTimeoutSeconds($execution)));
    }

    private function pendingExpirationSeconds(Execution $execution): int
    {
        return max(1, (int) $this->runtimeSetting(
            $execution,
            'sla.pending_expiration_seconds',
            config('executions.sla.pending_expiration_seconds', 900),
        ));
    }

    private function runningTimeoutSeconds(Execution $execution): int
    {
        return max(1, (int) $this->runtimeSetting(
            $execution,
            'sla.running_timeout_seconds',
            config('executions.sla.running_timeout_seconds', 1800),
        ));
    }

    private function runtimeSetting(Execution $execution, string $key, mixed $default): mixed
    {
        if ($execution->organization_id === null) {
            return $default;
        }

        $settings = app(OrganizationSettingsService::class)
            ->resolve($execution->organization_id);

        return data_get($settings->runtimeDefaults, $key, $default);
    }
}
