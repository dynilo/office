<?php

namespace App\Support\Database;

use App\Application\Memory\Services\PgvectorCapabilitiesService;
use App\Support\Security\SecretRedactor;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class PgvectorRuntimeValidation
{
    public function __construct(
        private PgvectorCapabilitiesService $capabilities,
        private SecretRedactor $redactor,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        $config = $this->capabilities->readinessReport();
        $driver = (string) ($config['driver'] ?? '');
        $distance = (string) ($config['distance'] ?? config('memory.pgvector.distance', 'cosine'));
        $dimensions = (int) ($config['dimensions'] ?? config('memory.pgvector.dimensions', 1536));

        $runtime = [
            'extension_version' => null,
            'column_data_type' => null,
            'column_udt_name' => null,
            'column_type' => null,
            'column_dimensions' => null,
            'operator' => $this->operatorFor($distance),
            'probe_distance' => null,
            'connection_error' => null,
        ];

        $checks = [
            'connection_is_pgsql' => $driver === 'pgsql',
            'extension_available' => false,
            'vector_column_present' => false,
            'vector_column_type_valid' => false,
            'vector_dimensions_aligned' => false,
            'similarity_probe_succeeded' => false,
        ];

        if ($checks['connection_is_pgsql']) {
            try {
                $extension = DB::selectOne(
                    'SELECT extversion FROM pg_extension WHERE extname = ? LIMIT 1',
                    [(string) ($config['extension'] ?? 'vector')],
                );

                $extension = is_object($extension) ? get_object_vars($extension) : (array) $extension;
                $runtime['extension_version'] = $extension['extversion'] ?? null;
                $checks['extension_available'] = filled($runtime['extension_version']);

                $column = DB::selectOne("
                    SELECT data_type, udt_name
                    FROM information_schema.columns
                    WHERE table_name = 'knowledge_items'
                      AND column_name = 'embedding'
                    LIMIT 1
                ");

                $column = is_object($column) ? get_object_vars($column) : (array) $column;
                $runtime['column_data_type'] = $column['data_type'] ?? null;
                $runtime['column_udt_name'] = $column['udt_name'] ?? null;
                $checks['vector_column_present'] = $column !== [];
                $checks['vector_column_type_valid'] = ($runtime['column_udt_name'] ?? null) === 'vector';

                $columnType = DB::selectOne("
                    SELECT format_type(a.atttypid, a.atttypmod) AS column_type
                    FROM pg_attribute a
                    JOIN pg_class c ON a.attrelid = c.oid
                    JOIN pg_namespace n ON c.relnamespace = n.oid
                    WHERE c.relname = 'knowledge_items'
                      AND a.attname = 'embedding'
                      AND NOT a.attisdropped
                    LIMIT 1
                ");

                $columnType = is_object($columnType) ? get_object_vars($columnType) : (array) $columnType;
                $runtime['column_type'] = $columnType['column_type'] ?? null;
                $runtime['column_dimensions'] = $this->dimensionsFromColumnType($runtime['column_type']);
                $checks['vector_dimensions_aligned'] = $runtime['column_dimensions'] === $dimensions;

                if ($checks['extension_available'] && $checks['vector_column_type_valid']) {
                    $probe = DB::selectOne(sprintf(
                        "SELECT '[0,0]'::vector %s '[0,0]'::vector AS distance",
                        $runtime['operator'],
                    ));

                    $probe = is_object($probe) ? get_object_vars($probe) : (array) $probe;
                    $runtime['probe_distance'] = $probe['distance'] ?? null;
                    $checks['similarity_probe_succeeded'] = array_key_exists('distance', $probe);
                }
            } catch (Throwable $exception) {
                $runtime['connection_error'] = $this->redactor->redactString($exception->getMessage());
            }
        }

        $readyForStorage = $checks['connection_is_pgsql']
            && $checks['extension_available']
            && $checks['vector_column_present']
            && $checks['vector_column_type_valid']
            && $checks['vector_dimensions_aligned'];
        $readyForSearch = $readyForStorage && $checks['similarity_probe_succeeded'];

        return [
            'environment' => (string) config('app.env'),
            'config' => $config,
            'runtime' => $runtime,
            'checks' => $checks,
            'fallback' => [
                'safe' => true,
                'metadata_only_storage_available' => true,
                'similarity_search_degrades_to_empty' => true,
            ],
            'ready_for_storage' => $readyForStorage,
            'ready_for_search' => $readyForSearch,
            'ready' => $readyForStorage && $readyForSearch,
            'unavailable_reason' => $this->firstFailedCheck($checks),
        ];
    }

    private function operatorFor(string $distance): string
    {
        return match ($distance) {
            'l2' => '<->',
            'inner_product' => '<#>',
            default => '<=>',
        };
    }

    private function dimensionsFromColumnType(?string $columnType): ?int
    {
        if (! is_string($columnType)) {
            return null;
        }

        preg_match('/vector\((\d+)\)/', $columnType, $matches);

        return isset($matches[1]) ? (int) $matches[1] : null;
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
