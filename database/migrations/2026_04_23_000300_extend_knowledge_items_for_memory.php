<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_items', function (Blueprint $table): void {
            $table->string('embedding_model', 191)->nullable()->after('content_hash');
            $table->unsignedSmallInteger('embedding_dimensions')->nullable()->after('embedding_model');
            $table->timestampTz('embedding_generated_at')->nullable()->after('indexed_at');

            $table->index('embedding_model');
            $table->index('embedding_generated_at');
        });

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        try {
            DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
            DB::statement(sprintf(
                'ALTER TABLE knowledge_items ADD COLUMN IF NOT EXISTS embedding vector(%d)',
                (int) config('memory.pgvector.dimensions', 1536),
            ));
        } catch (\Throwable) {
            // Leave the non-vector columns in place so the runtime can degrade safely.
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            try {
                DB::statement('ALTER TABLE knowledge_items DROP COLUMN IF EXISTS embedding');
            } catch (\Throwable) {
                // No-op if the column was never created.
            }
        }

        Schema::table('knowledge_items', function (Blueprint $table): void {
            $table->dropIndex(['embedding_model']);
            $table->dropIndex(['embedding_generated_at']);
            $table->dropColumn([
                'embedding_model',
                'embedding_dimensions',
                'embedding_generated_at',
            ]);
        });
    }
};
