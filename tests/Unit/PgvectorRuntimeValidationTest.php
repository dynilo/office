<?php

use App\Support\Database\PgvectorRuntimeValidation;
use Illuminate\Support\Facades\DB;

it('reports successful live pgvector runtime validation when the configured runtime supports storage and search', function (): void {
    config()->set('memory.pgvector.extension', 'vector');
    config()->set('memory.pgvector.dimensions', 1536);
    config()->set('memory.pgvector.distance', 'cosine');

    $schemaBuilder = new class
    {
        public function hasTable(string $table): bool
        {
            expect($table)->toBe('knowledge_items');

            return true;
        }
    };
    $connection = new class($schemaBuilder)
    {
        public function __construct(
            private readonly object $schemaBuilder,
        ) {}

        public function getSchemaBuilder(): object
        {
            return $this->schemaBuilder;
        }
    };

    DB::shouldReceive('getDriverName')->times(3)->andReturn('pgsql');
    DB::shouldReceive('connection')->once()->andReturn($connection);

    DB::shouldReceive('selectOne')->once()
        ->with(Mockery::pattern('/pg_extension/'), ['vector'])
        ->andReturn((object) ['extversion' => '0.8.0']);
    DB::shouldReceive('selectOne')->once()
        ->with(Mockery::pattern('/information_schema\\.columns/'))
        ->andReturn((object) ['column_exists' => 1]);
    DB::shouldReceive('selectOne')->once()
        ->with(Mockery::pattern('/pg_extension/'), ['vector'])
        ->andReturn((object) ['extversion' => '0.8.0']);
    DB::shouldReceive('selectOne')->once()
        ->with(Mockery::pattern('/information_schema\\.columns/'))
        ->andReturn((object) ['data_type' => 'USER-DEFINED', 'udt_name' => 'vector']);
    DB::shouldReceive('selectOne')->once()
        ->with(Mockery::pattern('/format_type/'))
        ->andReturn((object) ['column_type' => 'vector(1536)']);
    DB::shouldReceive('selectOne')->once()
        ->with(Mockery::pattern('/::vector <=>/'))
        ->andReturn((object) ['distance' => 0.0]);

    $report = app(PgvectorRuntimeValidation::class)->report();

    expect($report['ready'])->toBeTrue()
        ->and($report['ready_for_storage'])->toBeTrue()
        ->and($report['ready_for_search'])->toBeTrue()
        ->and($report['runtime']['extension_version'])->toBe('0.8.0')
        ->and($report['runtime']['column_type'])->toBe('vector(1536)')
        ->and($report['runtime']['column_dimensions'])->toBe(1536)
        ->and($report['runtime']['probe_distance'])->toBe(0.0);
});

it('fails safely with fallback metadata mode when pgvector runtime support is unavailable', function (): void {
    config()->set('database.connections.pgsql.password', 'pgvector-secret');
    config()->set('memory.pgvector.extension', 'vector');
    config()->set('memory.pgvector.dimensions', 1536);
    config()->set('memory.pgvector.distance', 'cosine');

    $schemaBuilder = new class
    {
        public function hasTable(string $table): bool
        {
            expect($table)->toBe('knowledge_items');

            return true;
        }
    };
    $connection = new class($schemaBuilder)
    {
        public function __construct(
            private readonly object $schemaBuilder,
        ) {}

        public function getSchemaBuilder(): object
        {
            return $this->schemaBuilder;
        }
    };

    DB::shouldReceive('getDriverName')->times(3)->andReturn('pgsql');
    DB::shouldReceive('connection')->once()->andReturn($connection);
    DB::shouldReceive('selectOne')->once()
        ->with(Mockery::pattern('/pg_extension/'), ['vector'])
        ->andThrow(new RuntimeException('pgvector connection failed with pgvector-secret'));
    DB::shouldReceive('selectOne')->once()
        ->with(Mockery::pattern('/information_schema\\.columns/'))
        ->andThrow(new RuntimeException('pgvector connection failed with pgvector-secret'));

    DB::shouldReceive('selectOne')->once()
        ->with(Mockery::pattern('/pg_extension/'), ['vector'])
        ->andThrow(new RuntimeException('pgvector connection failed with pgvector-secret'));

    $report = app(PgvectorRuntimeValidation::class)->report();

    expect($report['ready'])->toBeFalse()
        ->and($report['fallback']['safe'])->toBeTrue()
        ->and($report['fallback']['metadata_only_storage_available'])->toBeTrue()
        ->and($report['fallback']['similarity_search_degrades_to_empty'])->toBeTrue()
        ->and($report['runtime']['connection_error'])->toContain('[REDACTED]')
        ->and($report['runtime']['connection_error'])->not->toContain('pgvector-secret')
        ->and($report['unavailable_reason'])->toBe('extension_available');
});

it('does not attempt live pgvector queries on non postgresql connections and keeps fallback mode explicit', function (): void {
    DB::shouldReceive('getDriverName')->once()->andReturn('sqlite');
    DB::shouldReceive('connection')->never();
    DB::shouldReceive('selectOne')->never();

    $report = app(PgvectorRuntimeValidation::class)->report();

    expect($report['ready'])->toBeFalse()
        ->and($report['checks']['connection_is_pgsql'])->toBeFalse()
        ->and($report['fallback']['safe'])->toBeTrue()
        ->and($report['unavailable_reason'])->toBe('connection_is_pgsql');
});
