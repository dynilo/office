<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artifacts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('task_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('execution_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 32);
            $table->string('name', 120);
            $table->text('content_text')->nullable();
            $table->jsonb('content_json')->nullable();
            $table->jsonb('file_metadata')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index(['task_id', 'kind']);
            $table->index(['execution_id', 'kind']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artifacts');
    }
};
