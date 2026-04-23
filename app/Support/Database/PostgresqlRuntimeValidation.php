<?php

namespace App\Support\Database;

use App\Support\Security\SecretRedactor;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class PostgresqlRuntimeValidation
{
    public function __construct(
        private PostgresqlProductionReadiness $readiness,
        private SecretRedactor $redactor,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        $readiness = $this->readiness->report();
        $connection = (string) ($readiness['default_connection'] ?? config('database.default', ''));
        $expectedSchema = $this->expectedSchema();
        $runtime = [
            'connected' => false,
            'server_version' => null,
            'current_database' => null,
            'current_schema' => null,
            'connection_error' => null,
        ];

        $checks = [
            'config_ready' => (bool) ($readiness['ready'] ?? false),
            'connection_uses_pgsql' => (string) ($readiness['default_driver'] ?? '') === 'pgsql',
            'connection_opened' => false,
            'server_version_detected' => false,
            'current_database_detected' => false,
            'schema_aligned' => false,
        ];

        if (! $checks['connection_uses_pgsql']) {
            return [
                'environment' => (string) config('app.env'),
                'connection' => $connection,
                'expected_schema' => $expectedSchema,
                'config' => $readiness,
                'runtime' => $runtime,
                'checks' => $checks,
                'ready' => false,
                'unavailable_reason' => $this->firstFailedCheck($checks),
            ];
        }

        try {
            $summary = DB::connection($connection)->selectOne(
                'select version() as server_version, current_database() as current_database, current_schema() as current_schema'
            );

            $summary = is_object($summary) ? get_object_vars($summary) : (array) $summary;
            $runtime = [
                'connected' => true,
                'server_version' => $summary['server_version'] ?? null,
                'current_database' => $summary['current_database'] ?? null,
                'current_schema' => $summary['current_schema'] ?? null,
                'connection_error' => null,
            ];

            $checks['connection_opened'] = true;
            $checks['server_version_detected'] = filled($runtime['server_version']);
            $checks['current_database_detected'] = filled($runtime['current_database']);
            $checks['schema_aligned'] = filled($expectedSchema)
                ? $runtime['current_schema'] === $expectedSchema
                : filled($runtime['current_schema']);
        } catch (Throwable $exception) {
            $runtime['connection_error'] = $this->redactor->redactString($exception->getMessage());
        }

        return [
            'environment' => (string) config('app.env'),
            'connection' => $connection,
            'expected_schema' => $expectedSchema,
            'config' => $readiness,
            'runtime' => $runtime,
            'checks' => $checks,
            'ready' => ! in_array(false, $checks, true),
            'unavailable_reason' => $this->firstFailedCheck($checks),
        ];
    }

    private function expectedSchema(): string
    {
        $searchPath = (string) config('database.connections.pgsql.search_path', 'public');
        $schemas = array_values(array_filter(array_map(
            static fn (string $schema): string => trim($schema),
            explode(',', $searchPath),
        )));

        return $schemas[0] ?? 'public';
    }

    /**
     * @param  array<string, bool>  $checks
     */
    private function firstFailedCheck(array $checks): ?string
    {
        foreach ($checks as $check => $passed) {
            if (! $passed) {
                return $check;
            }
        }

        return null;
    }
}
