<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_profiles', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->text('system_prompt')->nullable();
            $table->json('instructions')->nullable();
            $table->json('defaults')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();

            $table->unique('agent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_profiles');
    }
};
