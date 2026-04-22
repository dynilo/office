<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->string('summary')->nullable()->after('title');
            $table->text('description')->nullable()->after('summary');
            $table->string('source', 100)->nullable()->after('priority');
            $table->string('requested_agent_role', 100)->nullable()->after('source');
            $table->timestampTz('due_at')->nullable()->after('scheduled_at');

            $table->index(['requested_agent_role', 'status']);
            $table->index('due_at');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropIndex(['requested_agent_role', 'status']);
            $table->dropIndex(['due_at']);
            $table->dropColumn([
                'summary',
                'description',
                'source',
                'requested_agent_role',
                'due_at',
            ]);
        });
    }
};
