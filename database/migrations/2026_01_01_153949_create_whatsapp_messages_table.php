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
        if (Schema::hasTable('whatsapp_messages')) {
            return;
        }

        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->enum('direction', ['inbound', 'outbound']);
            $table->foreignId('contact_id')->constrained('whatsapp_contacts')->onDelete('cascade');
            $table->string('meta_message_id')->nullable()->comment('Message ID from Meta API');
            $table->string('type')->default('text')->comment('text, template, image, etc.');
            $table->text('body')->nullable();
            $table->enum('status', ['queued', 'sent', 'delivered', 'read', 'failed'])->default('queued');
            $table->json('payload')->nullable()->comment('Full API payload');
            $table->json('error')->nullable()->comment('Error details if failed');
            $table->timestamps();

            $table->index(['contact_id', 'created_at']);
            $table->index('meta_message_id');
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
