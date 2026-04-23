<?php

namespace App\Application\Memory\Services;

use Illuminate\Support\Facades\DB;
use Throwable;

final class PgvectorCapabilitiesService
{
    public function supportsVectorStorage(): bool
    {
        return $this->hasPgvectorExtension() && $this->hasVectorColumn();
    }

    public function supportsSimilaritySearch(): bool
    {
        return $this->supportsVectorStorage();
    }

    private function hasPgvectorExtension(): bool
    {
        if (DB::getDriverName() !== 'pgsql') {
            return false;
        }

        try {
            $result = DB::selectOne("SELECT 1 FROM pg_extension WHERE extname = 'vector' LIMIT 1");

            return $result !== null;
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
}
