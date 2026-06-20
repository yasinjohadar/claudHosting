<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coolify_project_snapshots', function (Blueprint $table) {
            $table->string('restore_status', 32)->nullable()->after('status');
            $table->timestamp('restore_started_at')->nullable()->after('completed_at');
            $table->timestamp('restore_completed_at')->nullable()->after('restore_started_at');
        });

        Schema::table('coolify_project_snapshot_items', function (Blueprint $table) {
            $table->string('restore_status', 32)->nullable()->after('status');
            $table->text('restore_error')->nullable()->after('error_message');
        });
    }

    public function down(): void
    {
        Schema::table('coolify_project_snapshot_items', function (Blueprint $table) {
            $table->dropColumn(['restore_status', 'restore_error']);
        });

        Schema::table('coolify_project_snapshots', function (Blueprint $table) {
            $table->dropColumn(['restore_status', 'restore_started_at', 'restore_completed_at']);
        });
    }
};
