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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('whmcs_id')->unique();
            $table->string('firstname');
            $table->string('lastname');
            $table->string('fullname')->nullable();
            $table->string('email');
            $table->string('companyname')->nullable();
            $table->string('address1')->nullable();
            $table->string('address2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postcode')->nullable();
            $table->string('country', 2)->default('US');
            $table->string('phonenumber')->nullable();
            $table->unsignedInteger('currency')->default(1);
            $table->unsignedInteger('groupid')->default(1);
            $table->string('status')->default('Active');
            $table->text('notes')->nullable();
            $table->dateTime('last_login')->nullable();
            $table->dateTime('date_created')->nullable();
            $table->dateTime('synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('whmcs_id');
            $table->index('email');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};