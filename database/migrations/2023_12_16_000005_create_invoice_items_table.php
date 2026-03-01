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
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('whmcs_invoice_item_id')->unique();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('whmcs_service_id')->nullable();
            $table->string('description');
            $table->decimal('amount', 10, 2)->default(0.00);
            $table->boolean('taxed')->default(false);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('whmcs_invoice_item_id');
            $table->index('invoice_id');
            $table->index('product_id');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};