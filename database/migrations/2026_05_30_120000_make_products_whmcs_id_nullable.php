<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'whmcs_id')) {
                try {
                    $table->dropUnique(['whmcs_id']);
                } catch (\Throwable) {
                    // index name may differ
                }
                $table->unsignedBigInteger('whmcs_id')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'whmcs_id')) {
                $table->unsignedBigInteger('whmcs_id')->nullable(false)->change();
                $table->unique('whmcs_id');
            }
        });
    }
};
