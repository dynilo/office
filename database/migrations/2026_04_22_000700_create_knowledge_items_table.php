<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->string('title');
            $table->longText('content');
            $table->string('content_hash', 128);
            $table->json('metadata')->nullable();
            $table->timestampTz('indexed_at')->nullable();
            $table->timestampsTz();

            $table->index('document_id');
            $table->index('content_hash');
            $table->index('indexed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_items');
    }
};
