<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

it('exposes live pgvector runtime validation through a console command', function (): void {
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

    expect(Artisan::call('pgvector:validate-runtime'))->toBe(0);

    $output = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);

    expect($output['ready'])->toBeTrue()
        ->and($output['runtime']['extension_version'])->toBe('0.8.0')
        ->and($output['ready_for_search'])->toBeTrue();
});

it('documents pgvector runtime validation and fallback behavior', function (): void {
    $document = File::get(base_path('docs/PGVECTOR_PRODUCTION.md'));

    expect($document)->toContain('php artisan pgvector:validate-runtime')
        ->toContain('similarity search degrades to an empty result set')
        ->toContain('configured vector dimensions alignment');
});
