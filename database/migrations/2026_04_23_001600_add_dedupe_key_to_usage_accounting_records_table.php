<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usage_accounting_records', function (Blueprint $table): void {
            $table->string('dedupe_key', 191)->nullable()->after('metric_key');
            $table->unique('dedupe_key');
        });
    }

    public function down(): void
    {
        Schema::table('usage_accounting_records', function (Blueprint $table): void {
            $table->dropUnique(['dedupe_key']);
            $table->dropColumn('dedupe_key');
        });
    }
};
