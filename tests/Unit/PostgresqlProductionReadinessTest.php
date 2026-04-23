<?php

use App\Support\Database\PostgresqlProductionReadiness;

it('reports postgresql production readiness from configuration without opening a database connection', function (): void {
    config()->set('app.env', 'production');
    config()->set('database.default', 'pgsql');
    config()->set('database.connections.pgsql.driver', 'pgsql');
    config()->set('database.connections.pgsql.host', 'postgres.internal');
    config()->set('database.connections.pgsql.database', 'office');
    config()->set('database.connections.pgsql.charset', 'utf8');
    config()->set('database.connections.pgsql.search_path', 'public');
    config()->set('database.connections.pgsql.sslmode', 'require');

    $report = app(PostgresqlProductionReadiness::class)->report();

    expect($report['ready'])->toBeTrue()
        ->and($report['default_connection'])->toBe('pgsql')
        ->and($report['default_driver'])->toBe('pgsql')
        ->and($report['pgsql']['host_configured'])->toBeTrue()
        ->and($report['pgsql']['database_configured'])->toBeTrue()
        ->and($report['checks']['production_sslmode_is_strict'])->toBeTrue();
});

it('fails fast in production when the default database is not postgresql', function (): void {
    config()->set('app.env', 'production');
    config()->set('database.default', 'sqlite');
    config()->set('database.connections.sqlite.driver', 'sqlite');
    config()->set('database.production.enforce_pgsql', true);

    expect(fn () => app(PostgresqlProductionReadiness::class)->assertProductionSafe())
        ->toThrow(RuntimeException::class, 'default_connection_is_pgsql');
});

it('allows non-postgresql test and local environments as an explicit safe fallback', function (): void {
    config()->set('app.env', 'testing');
    config()->set('database.default', 'sqlite');
    config()->set('database.connections.sqlite.driver', 'sqlite');

    app(PostgresqlProductionReadiness::class)->assertProductionSafe();

    expect(true)->toBeTrue();
});

it('requires strict ssl mode for production postgresql unless the guard is explicitly relaxed', function (): void {
    config()->set('app.env', 'production');
    config()->set('database.default', 'pgsql');
    config()->set('database.connections.pgsql.driver', 'pgsql');
    config()->set('database.connections.pgsql.charset', 'utf8');
    config()->set('database.connections.pgsql.search_path', 'public');
    config()->set('database.connections.pgsql.sslmode', 'prefer');
    config()->set('database.production.require_ssl', true);

    expect(fn () => app(PostgresqlProductionReadiness::class)->assertProductionSafe())
        ->toThrow(RuntimeException::class, 'production_sslmode_is_strict');

    config()->set('database.production.require_ssl', false);

    app(PostgresqlProductionReadiness::class)->assertProductionSafe();

    expect(true)->toBeTrue();
});
