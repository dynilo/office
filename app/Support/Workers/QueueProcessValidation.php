<?php

namespace App\Support\Workers;

use Illuminate\Support\Facades\File;

final readonly class QueueProcessValidation
{
    public function __construct(
        private WorkerSupervisionReadiness $readiness,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        $readiness = $this->readiness->report();
        $driver = (string) config('workers.validation.supervisor_driver', 'none');
        $statusOutputPath = config('workers.validation.status_output_path');
        $processSnapshotPath = config('workers.validation.process_snapshot_path');
        $evidence = $this->evidence($statusOutputPath, $processSnapshotPath);
        $commandObserved = $evidence['content'] !== null
            ? $this->matchesExpectedWorkerCommand((string) $evidence['content'])
            : false;
        $fallbackSafe = ! $this->requiresSupervision()
            || ! $this->isProduction()
            || $driver === 'none';

        $checks = [
            'worker_configuration_ready' => (bool) $readiness['ready'],
            'supervisor_driver_supported' => in_array($driver, ['none', 'generic', 'supervisor', 'systemd'], true),
            'process_evidence_present' => $evidence['content'] !== null,
            'expected_worker_command_observed' => $commandObserved,
            'fallback_safe_without_supervisor' => $fallbackSafe,
        ];

        $ready = $checks['worker_configuration_ready']
            && $checks['supervisor_driver_supported']
            && (
                ($checks['process_evidence_present'] && $checks['expected_worker_command_observed'])
                || (! $checks['process_evidence_present'] && $checks['fallback_safe_without_supervisor'])
            );

        return [
            'environment' => (string) config('app.env'),
            'supervision' => [
                'driver' => $driver,
                'require_in_production' => $this->requiresSupervision(),
                'status_output_path' => $statusOutputPath,
                'process_snapshot_path' => $processSnapshotPath,
                'expected_command' => $readiness['worker']['command'],
                'evidence_source' => $evidence['source'],
            ],
            'worker_readiness' => $readiness,
            'process' => [
                'evidence_present' => $checks['process_evidence_present'],
                'command_observed' => $checks['expected_worker_command_observed'],
                'fallback_safe' => $checks['fallback_safe_without_supervisor'],
            ],
            'checks' => $checks,
            'ready' => $ready,
            'unavailable_reason' => $this->firstFailedCheck($checks, $evidence['content'] !== null),
        ];
    }

    private function isProduction(): bool
    {
        return config('app.env') === 'production';
    }

    private function requiresSupervision(): bool
    {
        return (bool) config('workers.validation.require_in_production', false);
    }

    /**
     * @return array{source: string|null, content: string|null}
     */
    private function evidence(mixed $statusOutputPath, mixed $processSnapshotPath): array
    {
        if (is_string($statusOutputPath) && $statusOutputPath !== '' && File::exists($statusOutputPath)) {
            return [
                'source' => 'status_output',
                'content' => File::get($statusOutputPath),
            ];
        }

        if (is_string($processSnapshotPath) && $processSnapshotPath !== '' && File::exists($processSnapshotPath)) {
            return [
                'source' => 'process_snapshot',
                'content' => File::get($processSnapshotPath),
            ];
        }

        return [
            'source' => null,
            'content' => null,
        ];
    }

    private function matchesExpectedWorkerCommand(string $content): bool
    {
        $worker = config('workers.execution', []);
        $fragments = [
            'queue:work',
            (string) data_get($worker, 'connection', 'redis'),
            '--queue='.(string) data_get($worker, 'queue', 'executions'),
            '--tries='.(int) data_get($worker, 'tries', 3),
            '--timeout='.(int) data_get($worker, 'timeout', 60),
        ];

        foreach ($fragments as $fragment) {
            if (! str_contains($content, $fragment)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, bool>  $checks
     */
    private function firstFailedCheck(array $checks, bool $hasEvidence): ?string
    {
        foreach ($checks as $check => $passed) {
            if ($check === 'expected_worker_command_observed' && ! $hasEvidence) {
                continue;
            }

            if (! $passed) {
                return $check;
            }
        }

        return null;
    }
}
