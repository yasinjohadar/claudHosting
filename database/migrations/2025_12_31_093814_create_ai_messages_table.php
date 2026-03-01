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
        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('ai_conversations')->onDelete('cascade');
            $table->enum('role', ['system', 'user', 'assistant']);
            $table->text('content');
            $table->integer('tokens_used')->default(0);
            $table->integer('prompt_tokens')->default(0);
            $table->integer('completion_tokens')->default(0);
            $table->decimal('cost', 10, 6)->default(0);
            $table->integer('response_time')->nullable()->comment('Response time in milliseconds');
            $table->timestamps();

            $table->index('conversation_id');
            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_messages');
    }
};
