<?php

namespace App\Application\Executions\Services;

use App\Application\Organizations\Services\OrganizationSettingsService;
use App\Infrastructure\Persistence\Eloquent\Models\DeadLetterRecord;
use App\Infrastructure\Persistence\Eloquent\Models\Execution;

final class DeadLetterService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function captureForExecution(
        Execution $execution,
        string $reasonCode,
        string $errorMessage,
        array $payload = [],
    ): ?DeadLetterRecord {
        if (! $this->captureEnabled($execution->organization_id)) {
            return null;
        }

        return DeadLetterRecord::query()->updateOrCreate(
            ['execution_id' => $execution->id],
            [
                'organization_id' => $execution->organization_id,
                'task_id' => $execution->task_id,
                'agent_id' => $execution->agent_id,
                'reason_code' => $reasonCode,
                'error_message' => $errorMessage,
                'payload' => $payload,
                'captured_at' => now(),
            ],
        );
    }

    private function captureEnabled(?string $organizationId): bool
    {
        if ($organizationId === null) {
            return (bool) config('executions.dead_letter.capture_enabled', true);
        }

        $settings = app(OrganizationSettingsService::class)
            ->resolve($organizationId);

        return (bool) data_get(
            $settings->runtimeDefaults,
            'dead_letter.capture_enabled',
            config('executions.dead_letter.capture_enabled', true),
        );
    }
}
