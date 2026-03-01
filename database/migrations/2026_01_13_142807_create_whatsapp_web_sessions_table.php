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
        Schema::create('whatsapp_web_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique()->comment('Session ID from Node.js service');
            $table->string('phone_number')->nullable()->comment('Phone number of connected device');
            $table->string('name')->nullable()->comment('Name of the WhatsApp account');
            $table->enum('status', ['connecting', 'connected', 'disconnected', 'error'])->default('connecting');
            $table->text('qr_code')->nullable()->comment('QR Code data (base64)');
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('disconnected_at')->nullable();
            $table->json('settings')->nullable()->comment('Additional settings (JSON)');
            $table->text('error_message')->nullable()->comment('Error message if status is error');
            $table->timestamps();

            $table->index('status');
            $table->index('session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_web_sessions');
    }
};
