<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('whmcs_id')->unique();
            $table->unsignedBigInteger('customer_id');
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('companyname')->nullable();
            $table->string('email')->nullable();
            $table->string('address1')->nullable();
            $table->string('address2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postcode')->nullable();
            $table->string('country', 2)->nullable();
            $table->string('phonenumber')->nullable();
            $table->boolean('generalemails')->default(true);
            $table->boolean('productemails')->default(true);
            $table->boolean('domainemails')->default(true);
            $table->boolean('invoiceemails')->default(true);
            $table->boolean('supportemails')->default(true);
            $table->boolean('affiliateemails')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->index('customer_id');
            $table->index('whmcs_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
