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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('whmcs_id')->unique();
            $table->unsignedBigInteger('whmcs_client_id');
            $table->string('tid')->nullable();
            $table->unsignedInteger('deptid')->default(1);
            $table->unsignedBigInteger('userid')->nullable();
            $table->string('name');
            $table->string('email');
            $table->string('subject');
            $table->text('message');
            $table->string('status')->default('Open');
            $table->string('priority')->default('Medium');
            $table->string('admin')->nullable();
            $table->dateTime('lastreply')->nullable();
            $table->dateTime('lastadminreply')->nullable();
            $table->dateTime('date');
            $table->dateTime('lastmodified')->nullable();
            $table->unsignedBigInteger('service')->nullable();
            $table->dateTime('synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('whmcs_id');
            $table->index('whmcs_client_id');
            $table->index('status');
            $table->index('priority');
            $table->index('deptid');
            $table->foreign('whmcs_client_id')->references('whmcs_id')->on('customers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};