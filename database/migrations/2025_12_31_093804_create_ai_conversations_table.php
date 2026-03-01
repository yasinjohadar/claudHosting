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
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('course_id')->nullable()->comment('يمكن استخدامه لربط المحادثة بكورس في المستقبل');
            $table->unsignedBigInteger('lesson_id')->nullable()->comment('يمكن استخدامه لربط المحادثة بدرس في المستقبل');
            $table->enum('conversation_type', ['general', 'subject', 'lesson'])->default('general');
            $table->string('title')->nullable();
            $table->foreignId('ai_model_id')->nullable()->constrained('ai_models')->onDelete('set null');
            $table->integer('total_tokens')->default(0);
            $table->decimal('total_cost', 10, 6)->default(0);
            $table->timestamps();

            $table->index('user_id');
            $table->index('ai_model_id');
            $table->index('conversation_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_conversations');
    }
};
