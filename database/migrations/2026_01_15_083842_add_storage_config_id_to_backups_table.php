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
            // إضافة حقل storage_config_id للربط مع AppStorageConfig
            $table->foreignId('storage_config_id')->nullable()->after('storage_driver')
                  ->constrained('app_storage_configs')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->dropForeign(['storage_config_id']);
            $table->dropColumn('storage_config_id');
        });
    }
};
