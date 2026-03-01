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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('whmcs_id')->unique();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('whmcs_invoice_id');
            $table->unsignedBigInteger('whmcs_client_id');
            $table->dateTime('date');
            $table->decimal('amount', 10, 2)->default(0.00);
            $table->decimal('fees', 10, 2)->default(0.00);
            $table->string('paymentmethod');
            $table->string('transid')->nullable();
            $table->string('status')->default('Completed');
            $table->dateTime('synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('whmcs_id');
            $table->index('invoice_id');
            $table->index('whmcs_invoice_id');
            $table->index('whmcs_client_id');
            $table->index('status');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            $table->foreign('whmcs_client_id')->references('whmcs_id')->on('customers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};