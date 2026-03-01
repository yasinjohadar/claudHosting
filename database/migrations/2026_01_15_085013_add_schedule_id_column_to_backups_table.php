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
        Schema::table('backups', function (Blueprint $table) {
            // إضافة حقل schedule_id إذا لم يكن موجوداً
            if (!Schema::hasColumn('backups', 'schedule_id')) {
                $table->foreignId('schedule_id')->nullable()->after('created_by')
                      ->constrained('backup_schedules')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            if (Schema::hasColumn('backups', 'schedule_id')) {
                $table->dropForeign(['schedule_id']);
                $table->dropColumn('schedule_id');
            }
        });
    }
};
