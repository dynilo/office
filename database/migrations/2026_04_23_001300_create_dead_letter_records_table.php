<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dead_letter_records', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignUlid('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignUlid('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignUlid('execution_id')->constrained('executions')->cascadeOnDelete();
            $table->string('reason_code', 64);
            $table->text('error_message');
            $table->json('payload')->nullable();
            $table->timestampTz('captured_at');
            $table->timestampsTz();

            $table->unique('execution_id');
            $table->index(['organization_id', 'captured_at']);
            $table->index(['task_id', 'reason_code']);
            $table->index(['agent_id', 'reason_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dead_letter_records');
    }
};
