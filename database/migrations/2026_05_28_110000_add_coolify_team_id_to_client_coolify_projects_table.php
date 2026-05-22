<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_coolify_projects', function (Blueprint $table) {
            $table->unsignedInteger('coolify_team_id')->nullable()->after('user_id');
            $table->index('coolify_team_id');
        });
    }

    public function down(): void
    {
        Schema::table('client_coolify_projects', function (Blueprint $table) {
            $table->dropIndex(['coolify_team_id']);
            $table->dropColumn('coolify_team_id');
        });
    }
};
