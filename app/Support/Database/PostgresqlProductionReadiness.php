<?php

namespace App\Support\Database;

use RuntimeException;

final class PostgresqlProductionReadiness
{
    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        $defaultConnection = (string) config('database.default');
        $defaultDriver = $this->driverFor($defaultConnection);
        $pgsql = config('database.connections.pgsql', []);
        $sslmode = (string) data_get($pgsql, 'sslmode', '');
        $searchPath = (string) data_get($pgsql, 'search_path', '');
        $production = $this->isProduction();

        $checks = [
            'default_connection_is_pgsql' => $defaultConnection === 'pgsql' && $defaultDriver === 'pgsql',
            'pgsql_connection_is_defined' => is_array($pgsql) && ($pgsql['driver'] ?? null) === 'pgsql',
            'pgsql_uses_utf8_charset' => ($pgsql['charset'] ?? null) === 'utf8',
            'pgsql_uses_public_search_path' => $searchPath === 'public',
            'pgsql_sslmode_is_configured' => $sslmode !== '',
            'production_sslmode_is_strict' => ! $production || ! $this->requiresStrictSsl() || $this->hasStrictSslMode($sslmode),
        ];

        return [
            'environment' => (string) config('app.env'),
            'default_connection' => $defaultConnection,
            'default_driver' => $defaultDriver,
            'pgsql' => [
                'host_configured' => filled(data_get($pgsql, 'host')) || filled(data_get($pgsql, 'url')),
                'database_configured' => filled(data_get($pgsql, 'database')) || filled(data_get($pgsql, 'url')),
                'charset' => data_get($pgsql, 'charset'),
                'search_path' => $searchPath,
                'sslmode' => $sslmode,
            ],
            'checks' => $checks,
            'ready' => ! in_array(false, $checks, true),
        ];
    }

    public function assertProductionSafe(): void
    {
        if (! $this->isProduction() || ! $this->enforcesPostgresql()) {
            return;
        }

        $report = $this->report();

        if ($report['ready'] === true) {
            return;
        }

        $failed = collect($report['checks'])
            ->filter(static fn (bool $passed): bool => ! $passed)
            ->keys()
            ->implode(', ');

        throw new RuntimeException("PostgreSQL production readiness failed: {$failed}.");
    }

    private function isProduction(): bool
    {
        return config('app.env') === 'production';
    }

    private function enforcesPostgresql(): bool
    {
        return (bool) config('database.production.enforce_pgsql', true);
    }

    private function requiresStrictSsl(): bool
    {
        return (bool) config('database.production.require_ssl', true);
    }

    private function driverFor(string $connection): ?string
    {
        $driver = config("database.connections.{$connection}.driver");

        return is_string($driver) ? $driver : null;
    }

    private function hasStrictSslMode(string $sslmode): bool
    {
        return in_array($sslmode, config('database.production.allowed_sslmodes', []), true);
    }
}
