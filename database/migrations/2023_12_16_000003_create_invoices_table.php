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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('whmcs_id')->unique();
            $table->unsignedBigInteger('whmcs_client_id');
            $table->string('invoicenum')->nullable();
            $table->dateTime('date');
            $table->dateTime('duedate');
            $table->dateTime('datepaid')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0.00);
            $table->decimal('credit', 10, 2)->default(0.00);
            $table->decimal('tax', 10, 2)->default(0.00);
            $table->decimal('taxrate', 10, 2)->default(0.00);
            $table->decimal('tax2', 10, 2)->default(0.00);
            $table->decimal('taxrate2', 10, 2)->default(0.00);
            $table->decimal('total', 10, 2)->default(0.00);
            $table->string('status')->default('Unpaid');
            $table->string('paymentmethod')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('whmcs_id');
            $table->index('whmcs_client_id');
            $table->index('status');
            $table->index('duedate');
            $table->foreign('whmcs_client_id')->references('whmcs_id')->on('customers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};