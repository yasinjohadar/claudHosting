<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('backups')) {
            return;
        }
        
        Schema::table('backups', function (Blueprint $table) {
            $table->foreignId('schedule_id')->nullable()->after('created_by')->constrained('backup_schedules')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->dropForeign(['schedule_id']);
            $table->dropColumn('schedule_id');
        });
    }
};
