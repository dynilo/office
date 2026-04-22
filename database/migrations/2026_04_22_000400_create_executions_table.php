<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('executions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignUlid('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('status', 32);
            $table->unsignedSmallInteger('attempt')->default(1);
            $table->json('input_snapshot')->nullable();
            $table->json('output_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('finished_at')->nullable();
            $table->timestampsTz();

            $table->index(['task_id', 'status']);
            $table->index(['agent_id', 'status']);
            $table->index('finished_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('executions');
    }
};
