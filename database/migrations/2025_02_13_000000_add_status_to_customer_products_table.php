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
        Schema::table('customer_products', function (Blueprint $table) {
            if (! Schema::hasColumn('customer_products', 'status')) {
                $table->string('status')->nullable()->after('domainstatus');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_products', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
