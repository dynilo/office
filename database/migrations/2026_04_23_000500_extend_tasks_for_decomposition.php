<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->foreignUlid('parent_task_id')
                ->nullable()
                ->after('agent_id')
                ->constrained('tasks')
                ->nullOnDelete();
            $table->unsignedSmallInteger('decomposition_index')->nullable()->after('parent_task_id');

            $table->index(['parent_task_id', 'decomposition_index']);
            $table->index(['parent_task_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropForeign(['parent_task_id']);
            $table->dropIndex(['parent_task_id', 'decomposition_index']);
            $table->dropIndex(['parent_task_id', 'status']);
            $table->dropColumn(['parent_task_id', 'decomposition_index']);
        });
    }
};
