<?php

namespace App\Application\Memory\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class PgvectorCapabilitiesService
{
    public function supportsVectorStorage(): bool
    {
        return $this->readinessReport()['ready_for_storage'] === true;
    }

    public function supportsSimilaritySearch(): bool
    {
        return $this->readinessReport()['ready_for_search'] === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function readinessReport(): array
    {
        $driver = DB::getDriverName();
        $extension = (string) config('memory.pgvector.extension', 'vector');
        $dimensions = (int) config('memory.pgvector.dimensions', 1536);
        $distance = (string) config('memory.pgvector.distance', 'cosine');
        $production = config('app.env') === 'production';

        $checks = [
            'connection_is_pgsql' => $driver === 'pgsql',
            'extension_configured' => $extension !== '',
            'dimensions_valid' => $dimensions > 0,
            'distance_supported' => in_array($distance, ['cosine', 'l2', 'inner_product'], true),
            'extension_installed' => false,
            'knowledge_items_table_exists' => false,
            'embedding_column_exists' => false,
            'production_requirement_satisfied' => ! $production || ! $this->requiredInProduction(),
        ];

        if ($checks['connection_is_pgsql']) {
            $checks['extension_installed'] = $this->hasPgvectorExtension($extension);
            $checks['knowledge_items_table_exists'] = $this->hasKnowledgeItemsTable();
            $checks['embedding_column_exists'] = $this->hasVectorColumn();
            $checks['production_requirement_satisfied'] = ! $production
                || ! $this->requiredInProduction()
                || ($checks['extension_installed'] && $checks['embedding_column_exists']);
        }

        $readyForStorage = $checks['connection_is_pgsql']
            && $checks['extension_configured']
            && $checks['dimensions_valid']
            && $checks['extension_installed']
            && $checks['knowledge_items_table_exists']
            && $checks['embedding_column_exists'];

        return [
            'driver' => $driver,
            'extension' => $extension,
            'dimensions' => $dimensions,
            'distance' => $distance,
            'index' => [
                'enabled' => (bool) config('memory.pgvector.index.enabled', true),
                'method' => config('memory.pgvector.index.method', 'hnsw'),
                'name' => config('memory.pgvector.index.name', 'knowledge_items_embedding_hnsw_idx'),
            ],
            'required_in_production' => $this->requiredInProduction(),
            'checks' => $checks,
            'ready_for_storage' => $readyForStorage,
            'ready_for_search' => $readyForStorage && $checks['distance_supported'],
            'unavailable_reason' => $this->firstFailedCheck($checks),
        ];
    }

    public function vectorDimensionsAreValid(array $embedding): bool
    {
        $dimensions = (int) config('memory.pgvector.dimensions', 1536);

        return $dimensions > 0 && count($embedding) === $dimensions;
    }

    private function hasPgvectorExtension(string $extension): bool
    {
        if (DB::getDriverName() !== 'pgsql') {
            return false;
        }

        try {
            $result = DB::selectOne('SELECT 1 FROM pg_extension WHERE extname = ? LIMIT 1', [$extension]);

            return $result !== null;
        } catch (Throwable) {
            return false;
        }
    }

    private function hasKnowledgeItemsTable(): bool
    {
        try {
            return Schema::hasTable('knowledge_items');
        } catch (Throwable) {
            return false;
        }
    }

    private function hasVectorColumn(): bool
    {
        if (DB::getDriverName() !== 'pgsql') {
            return false;
        }

        try {
            $result = DB::selectOne("
                SELECT 1
                FROM information_schema.columns
                WHERE table_name = 'knowledge_items'
                  AND column_name = 'embedding'
                LIMIT 1
            ");

            return $result !== null;
        } catch (Throwable) {
            return false;
        }
    }

    private function requiredInProduction(): bool
    {
        return (bool) config('memory.pgvector.require_in_production', false);
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
