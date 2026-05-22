<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whmcs_domains', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('whmcs_domain_id')->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->unsignedBigInteger('whmcs_client_id');
            $table->string('domain');
            $table->string('status', 50)->nullable();
            $table->dateTime('registrationdate')->nullable();
            $table->dateTime('expirydate')->nullable();
            $table->dateTime('nextduedate')->nullable();
            $table->decimal('recurringamount', 12, 2)->nullable();
            $table->string('registrar', 100)->nullable();
            $table->string('paymentmethod', 100)->nullable();
            $table->string('billingcycle', 50)->nullable();
            $table->string('domainstatus', 50)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index('domain');
            $table->index('status');
            $table->index('expirydate');
            $table->index('whmcs_client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whmcs_domains');
    }
};
