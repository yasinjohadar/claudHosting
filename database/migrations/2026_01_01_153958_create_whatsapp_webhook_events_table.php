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
        if (Schema::hasTable('whatsapp_webhook_events')) {
            return;
        }

        Schema::create('whatsapp_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique()->comment('Unique hash for idempotency');
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['event_id']);
            $table->index(['processed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_webhook_events');
    }
};
