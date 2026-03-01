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
        Schema::table('backup_storage_configs', function (Blueprint $table) {
            $table->boolean('redundancy')->default(false)->after('is_active')->comment('تفعيل التخزين المتعدد');
            $table->json('pricing_config')->nullable()->after('max_backups')->comment('إعدادات التسعير');
            $table->decimal('monthly_budget', 10, 2)->nullable()->after('pricing_config')->comment('الميزانية الشهرية');
            $table->decimal('cost_alert_threshold', 10, 2)->nullable()->after('monthly_budget')->comment('حد تنبيه التكلفة');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('backup_storage_configs', function (Blueprint $table) {
            $table->dropColumn(['redundancy', 'pricing_config', 'monthly_budget', 'cost_alert_threshold']);
        });
    }
};
