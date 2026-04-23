<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->longText('raw_text')->nullable()->after('size_bytes');
            $table->timestampTz('text_extracted_at')->nullable()->after('ingested_at');

            $table->index('text_extracted_at');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->dropIndex(['text_extracted_at']);
            $table->dropColumn([
                'raw_text',
                'text_extracted_at',
            ]);
        });
    }
};
