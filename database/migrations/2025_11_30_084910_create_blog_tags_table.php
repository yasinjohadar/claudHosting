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
        Schema::create('blog_tags', function (Blueprint $table) {
            $table->id();

            // Basic Information
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('color')->nullable();

            // SEO Fields
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('canonical_url')->nullable();

            // Display Settings
            $table->boolean('is_active')->default(true);
            $table->integer('posts_count')->default(0);
            $table->integer('order')->default(0);

            // SEO Settings
            $table->boolean('is_indexable')->default(true);

            $table->timestamps();

            // Indexes
            $table->index('slug');
            $table->index('is_active');
        });

        // Pivot table for Many-to-Many relationship
        Schema::create('blog_post_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_post_id')->constrained('blog_posts')->cascadeOnDelete();
            $table->foreignId('blog_tag_id')->constrained('blog_tags')->cascadeOnDelete();
            $table->timestamps();

            // Indexes
            $table->unique(['blog_post_id', 'blog_tag_id']);
            $table->index('blog_post_id');
            $table->index('blog_tag_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_post_tag');
        Schema::dropIfExists('blog_tags');
    }
};
