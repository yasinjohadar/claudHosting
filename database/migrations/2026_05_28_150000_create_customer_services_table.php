<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('offered_service_id')->constrained('offered_services')->restrictOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('name');
            $table->decimal('price', 12, 2)->default(0);
            $table->string('currency', 10)->default('SAR');
            $table->string('execution_duration')->nullable();
            $table->unsignedSmallInteger('execution_days')->nullable();
            $table->date('subscribed_at')->nullable();
            $table->date('renewal_at')->nullable();
            $table->decimal('amount_due', 12, 2)->default(0);
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'status']);
            $table->index('renewal_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_services');
    }
};
