<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->foreignId('offered_service_id')->nullable()->after('product_id')
                ->constrained('offered_services')->nullOnDelete();
            $table->foreignId('customer_service_id')->nullable()->after('offered_service_id')
                ->constrained('customer_services')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropForeign(['offered_service_id']);
            $table->dropForeign(['customer_service_id']);
            $table->dropColumn(['offered_service_id', 'customer_service_id']);
        });
    }
};
