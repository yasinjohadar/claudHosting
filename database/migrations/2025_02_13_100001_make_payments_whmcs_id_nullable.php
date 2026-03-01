<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('whmcs_id')->nullable()->change();
            $table->unsignedBigInteger('whmcs_client_id')->nullable()->change();
            $table->unsignedBigInteger('whmcs_invoice_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('whmcs_id')->nullable(false)->change();
            $table->unsignedBigInteger('whmcs_client_id')->nullable(false)->change();
            $table->unsignedBigInteger('whmcs_invoice_id')->nullable(false)->change();
        });
    }
};
