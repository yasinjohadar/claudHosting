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
        Schema::create('blog_categories', function (Blueprint $table) {
            $table->id();

            // Basic Information
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->string('image')->nullable();

            // Parent Category (for hierarchical categories)
            $table->foreignId('parent_id')->nullable()->constrained('blog_categories')->nullOnDelete();

            // SEO Fields
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->string('canonical_url')->nullable();

            // Open Graph
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image')->nullable();

            // Schema.org
            $table->json('schema_data')->nullable();

            // Display & Settings
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('order')->default(0);
            $table->integer('posts_count')->default(0);

            // SEO Settings
            $table->boolean('is_indexable')->default(true);
            $table->enum('robots_meta', ['index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow'])->default('index,follow');

            $table->softDeletes();
            $table->timestamps();

            // Indexes
            $table->index('slug');
            $table->index('parent_id');
            $table->index('is_active');
            $table->index('is_featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_categories');
    }
};
