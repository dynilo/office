<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table): void {
            $table->string('code')->nullable()->after('id');
            $table->string('role')->nullable()->after('name');
        });

        Schema::table('agent_profiles', function (Blueprint $table): void {
            $table->string('model_preference')->nullable()->after('system_prompt');
            $table->json('temperature_policy')->nullable()->after('model_preference');
        });
    }

    public function down(): void
    {
        Schema::table('agent_profiles', function (Blueprint $table): void {
            $table->dropColumn(['model_preference', 'temperature_policy']);
        });

        Schema::table('agents', function (Blueprint $table): void {
            $table->dropColumn(['code', 'role']);
        });
    }
};
