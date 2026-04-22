<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_assignment_decisions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignUlid('agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->string('outcome', 32);
            $table->string('reason_code', 64)->nullable();
            $table->string('matched_by', 64)->nullable();
            $table->json('context')->nullable();
            $table->timestampsTz();

            $table->index(['task_id', 'created_at']);
            $table->index(['outcome', 'reason_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_assignment_decisions');
    }
};
