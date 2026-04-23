<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $tenantTables = [
        'agents',
        'agent_profiles',
        'tasks',
        'task_dependencies',
        'task_assignment_decisions',
        'executions',
        'execution_logs',
        'documents',
        'knowledge_items',
        'artifacts',
        'agent_communication_logs',
        'audit_events',
        'provider_usage_records',
    ];

    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status', 32)->default('active');
            $table->json('metadata')->nullable();
            $table->timestampsTz();

            $table->index(['status', 'name']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignUlid('current_organization_id')
                ->nullable()
                ->after('id')
                ->constrained('organizations')
                ->nullOnDelete();
        });

        Schema::create('organization_user', function (Blueprint $table): void {
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestampsTz();

            $table->primary(['organization_id', 'user_id']);
            $table->index('user_id');
        });

        foreach ($this->tenantTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignUlid('organization_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('organizations')
                    ->nullOnDelete();
                $table->index('organization_id');
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tenantTables) as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropConstrainedForeignId('organization_id');
            });
        }

        Schema::dropIfExists('organization_user');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('current_organization_id');
        });

        Schema::dropIfExists('organizations');
    }
};
