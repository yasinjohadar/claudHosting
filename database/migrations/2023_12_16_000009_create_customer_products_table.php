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
        Schema::create('customer_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('whmcs_service_id')->unique();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('orderid')->nullable();
            $table->dateTime('regdate');
            $table->string('domain')->nullable();
            $table->string('paymentmethod')->nullable();
            $table->decimal('firstpaymentamount', 10, 2)->default(0.00);
            $table->decimal('amount', 10, 2)->default(0.00);
            $table->string('billingcycle')->default('Monthly');
            $table->dateTime('nextduedate')->nullable();
            $table->dateTime('nextinvoicedate')->nullable();
            $table->dateTime('termination_date')->nullable();
            $table->dateTime('completed_date')->nullable();
            $table->string('domainstatus')->default('Pending');
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->text('notes')->nullable();
            $table->string('subscriptionid')->nullable();
            $table->unsignedBigInteger('promoid')->nullable();
            $table->boolean('overideautosuspend')->default(false);
            $table->dateTime('overidesuspenduntil')->nullable();
            $table->dateTime('lastupdate')->nullable();
            $table->dateTime('synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('whmcs_service_id');
            $table->index('customer_id');
            $table->index('product_id');
            $table->index('domainstatus');
            $table->index('nextduedate');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_products');
    }
};