<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_settings', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->unique()->constrained('organizations')->cascadeOnDelete();
            $table->json('provider_settings')->nullable();
            $table->json('memory_settings')->nullable();
            $table->json('policy_settings')->nullable();
            $table->json('runtime_defaults')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_settings');
    }
};
