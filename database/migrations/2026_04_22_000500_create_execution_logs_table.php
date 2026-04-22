<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('execution_logs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('execution_id')->constrained('executions')->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            $table->string('level', 32);
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestampTz('logged_at');
            $table->timestampsTz();

            $table->unique(['execution_id', 'sequence']);
            $table->index(['level', 'logged_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('execution_logs');
    }
};
