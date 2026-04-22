<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('title');
            $table->string('mime_type', 191);
            $table->string('storage_disk', 64);
            $table->string('storage_path');
            $table->string('checksum', 128);
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->json('metadata')->nullable();
            $table->timestampTz('ingested_at')->nullable();
            $table->timestampsTz();

            $table->unique(['storage_disk', 'storage_path']);
            $table->index('checksum');
            $table->index('ingested_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
