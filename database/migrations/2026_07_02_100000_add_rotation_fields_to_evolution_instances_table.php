<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evolution_instances', function (Blueprint $table) {
            $table->boolean('rotation_enabled')->default(true)->after('is_default');
            $table->timestamp('last_used_at')->nullable()->after('rotation_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('evolution_instances', function (Blueprint $table) {
            $table->dropColumn(['rotation_enabled', 'last_used_at']);
        });
    }
};
