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
        Schema::table('app_storage_configs', function (Blueprint $table) {
            // حقل الحد الأقصى للنسخ الاحتياطية (للاستخدام مع النسخ الاحتياطية)
            $table->integer('max_backups')->nullable()->after('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_storage_configs', function (Blueprint $table) {
            $table->dropColumn('max_backups');
        });
    }
};
