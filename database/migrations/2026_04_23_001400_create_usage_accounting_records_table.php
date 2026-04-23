<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_accounting_records', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignUlid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->foreignUlid('task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->foreignUlid('execution_id')->nullable()->constrained('executions')->nullOnDelete();
            $table->string('metric_key', 64);
            $table->unsignedInteger('quantity')->default(1);
            $table->json('metadata')->nullable();
            $table->timestampTz('recorded_at');
            $table->timestampsTz();

            $table->index(['metric_key', 'recorded_at']);
            $table->index(['organization_id', 'metric_key']);
            $table->index(['user_id', 'metric_key']);
            $table->index(['agent_id', 'metric_key']);
            $table->index(['task_id', 'metric_key']);
            $table->index(['execution_id', 'metric_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_accounting_records');
    }
};
