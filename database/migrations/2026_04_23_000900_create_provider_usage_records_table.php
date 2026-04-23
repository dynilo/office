<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_usage_records', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('execution_id')->constrained('executions')->cascadeOnDelete();
            $table->foreignUlid('task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->foreignUlid('agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->string('provider', 120);
            $table->string('model', 160)->nullable();
            $table->string('response_id', 160)->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->unsignedBigInteger('estimated_cost_micros')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('pricing_source', 160)->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestampsTz();

            $table->unique('execution_id');
            $table->index(['provider', 'model']);
            $table->index(['agent_id', 'created_at']);
            $table->index(['task_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_usage_records');
    }
};
