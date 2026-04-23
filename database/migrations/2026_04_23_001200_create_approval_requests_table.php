<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_requests', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignUlid('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignUlid('agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->string('action', 80);
            $table->string('status', 32);
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampTz('requested_at');
            $table->timestampTz('decided_at')->nullable();
            $table->string('decided_by_type', 80)->nullable();
            $table->string('decided_by_id')->nullable();
            $table->timestampsTz();

            $table->index(['task_id', 'action', 'status']);
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};
