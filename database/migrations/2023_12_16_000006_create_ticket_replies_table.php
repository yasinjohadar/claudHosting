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
        Schema::create('ticket_replies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('whmcs_id')->unique();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('whmcs_ticket_id');
            $table->unsignedBigInteger('userid')->nullable();
            $table->string('name');
            $table->string('email');
            $table->string('type')->default('client');
            $table->dateTime('date');
            $table->text('message');
            $table->string('attachment')->nullable();
            $table->string('admin')->nullable();
            $table->dateTime('synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('whmcs_id');
            $table->index('ticket_id');
            $table->index('whmcs_ticket_id');
            $table->index('userid');
            $table->index('type');
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_replies');
    }
};