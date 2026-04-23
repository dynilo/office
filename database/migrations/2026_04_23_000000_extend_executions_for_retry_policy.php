<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('executions', function (Blueprint $table): void {
            $table->unsignedSmallInteger('retry_count')->default(0)->after('attempt');
            $table->unsignedSmallInteger('max_retries')->default(2)->after('retry_count');
            $table->timestampTz('next_retry_at')->nullable()->after('finished_at');
            $table->string('failure_classification', 64)->nullable()->after('error_message');
            $table->foreignUlid('retry_of_execution_id')->nullable()->after('idempotency_key')
                ->constrained('executions')->nullOnDelete();

            $table->index(['status', 'next_retry_at']);
            $table->index('retry_of_execution_id');
        });
    }

    public function down(): void
    {
        Schema::table('executions', function (Blueprint $table): void {
            $table->dropIndex(['status', 'next_retry_at']);
            $table->dropIndex(['retry_of_execution_id']);
            $table->dropConstrainedForeignId('retry_of_execution_id');
            $table->dropColumn([
                'retry_count',
                'max_retries',
                'next_retry_at',
                'failure_classification',
            ]);
        });
    }
};
