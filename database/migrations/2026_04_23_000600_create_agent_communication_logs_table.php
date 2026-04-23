<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_communication_logs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('sender_agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->foreignUlid('recipient_agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->foreignUlid('task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->string('message_type', 80);
            $table->string('subject', 255)->nullable();
            $table->text('body');
            $table->jsonb('metadata')->default('{}');
            $table->timestampTz('sent_at');
            $table->timestampsTz();

            $table->index(['sender_agent_id', 'sent_at']);
            $table->index(['recipient_agent_id', 'sent_at']);
            $table->index(['task_id', 'sent_at']);
            $table->index(['message_type', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_communication_logs');
    }
};
