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
        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('provider', ['openai', 'anthropic', 'google', 'openrouter', 'zai', 'manus', 'groq', 'local', 'custom']);
            $table->string('model_key');
            $table->text('api_key')->comment('Encrypted API key');
            $table->string('api_endpoint')->nullable();
            $table->string('base_url')->nullable();
            $table->integer('max_tokens')->default(2000);
            $table->decimal('temperature', 3, 2)->default(0.7);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('priority')->default(0);
            $table->decimal('cost_per_1k_tokens', 10, 6)->nullable();
            $table->json('capabilities')->nullable();
            $table->json('settings')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('provider');
            $table->index('is_active');
            $table->index('is_default');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_models');
    }
};
