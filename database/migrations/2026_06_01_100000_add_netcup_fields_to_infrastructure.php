<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vps_action_logs', function (Blueprint $table) {
            $table->string('provider_task_uuid', 64)->nullable()->after('action');
            $table->index('provider_task_uuid');
        });
    }

    public function down(): void
    {
        Schema::table('vps_action_logs', function (Blueprint $table) {
            $table->dropIndex(['provider_task_uuid']);
            $table->dropColumn('provider_task_uuid');
        });
    }
};
