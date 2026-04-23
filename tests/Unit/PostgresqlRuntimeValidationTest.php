<?php

use App\Support\Database\PostgresqlRuntimeValidation;
use Illuminate\Support\Facades\DB;

it('reports successful live postgresql runtime validation when the configured connection responds', function (): void {
    config()->set('app.env', 'production');
    config()->set('database.default', 'pgsql');
    config()->set('database.connections.pgsql.driver', 'pgsql');
    config()->set('database.connections.pgsql.host', 'postgres.internal');
    config()->set('database.connections.pgsql.database', 'office');
    config()->set('database.connections.pgsql.charset', 'utf8');
    config()->set('database.connections.pgsql.search_path', 'public');
    config()->set('database.connections.pgsql.sslmode', 'require');

    $connection = new class
    {
        public function selectOne(string $query): object
        {
            expect($query)->toContain('select version() as server_version');

            return (object) [
                'server_version' => 'PostgreSQL 17.2',
                'current_database' => 'office',
                'current_schema' => 'public',
            ];
        }
    };

    DB::shouldReceive('connection')
        ->once()
        ->with('pgsql')
        ->andReturn($connection);

    $report = app(PostgresqlRuntimeValidation::class)->report();

    expect($report['ready'])->toBeTrue()
        ->and($report['runtime']['connected'])->toBeTrue()
        ->and($report['runtime']['server_version'])->toBe('PostgreSQL 17.2')
        ->and($report['runtime']['current_database'])->toBe('office')
        ->and($report['runtime']['current_schema'])->toBe('public')
        ->and($report['checks']['connection_opened'])->toBeTrue()
        ->and($report['checks']['schema_aligned'])->toBeTrue();
});

it('fails safely with a redacted connection error when live postgresql validation cannot connect', function (): void {
    config()->set('app.env', 'production');
    config()->set('database.default', 'pgsql');
    config()->set('database.connections.pgsql.driver', 'pgsql');
    config()->set('database.connections.pgsql.password', 'super-secret-password');
    config()->set('database.connections.pgsql.search_path', 'public');
    config()->set('database.connections.pgsql.sslmode', 'require');

    DB::shouldReceive('connection')
        ->once()
        ->with('pgsql')
        ->andThrow(new RuntimeException('could not connect with password super-secret-password'));

    $report = app(PostgresqlRuntimeValidation::class)->report();

    expect($report['ready'])->toBeFalse()
        ->and($report['runtime']['connected'])->toBeFalse()
        ->and($report['runtime']['connection_error'])->toContain('[REDACTED]')
        ->and($report['runtime']['connection_error'])->not->toContain('super-secret-password')
        ->and($report['unavailable_reason'])->toBe('connection_opened');
});

it('does not attempt a live connection when the default driver is not pgsql', function (): void {
    config()->set('database.default', 'sqlite');
    config()->set('database.connections.sqlite.driver', 'sqlite');

    DB::shouldReceive('connection')->never();

    $report = app(PostgresqlRuntimeValidation::class)->report();

    expect($report['ready'])->toBeFalse()
        ->and($report['checks']['connection_uses_pgsql'])->toBeFalse()
        ->and($report['unavailable_reason'])->toBe('config_ready');
});
