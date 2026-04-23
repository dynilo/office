<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

it('exposes live postgresql runtime validation through a console command', function (): void {
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
        public function selectOne(): object
        {
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

    expect(Artisan::call('postgresql:validate-runtime'))->toBe(0);

    $output = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);

    expect($output['ready'])->toBeTrue()
        ->and($output['runtime']['server_version'])->toBe('PostgreSQL 17.2');
});

it('documents the live postgresql runtime validation command', function (): void {
    $document = File::get(base_path('docs/POSTGRESQL_PRODUCTION.md'));

    expect($document)->toContain('php artisan postgresql:validate-runtime')
        ->toContain('live runtime validation')
        ->toContain('redacted connection error');
});
