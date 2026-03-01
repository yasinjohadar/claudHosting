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
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();

            // Basic Information
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->string('featured_image')->nullable();
            $table->string('featured_image_alt')->nullable();

            // Author & Category
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('blog_category_id')->nullable()->constrained('blog_categories')->nullOnDelete();

            // SEO Fields - Basic
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();

            // SEO Fields - Advanced
            $table->string('canonical_url')->nullable();
            $table->string('focus_keyword')->nullable();
            $table->string('focus_keyword_synonyms')->nullable();

            // Open Graph (Facebook, LinkedIn, etc.)
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image')->nullable();
            $table->enum('og_type', ['article', 'website', 'blog'])->default('article');
            $table->string('og_locale')->default('ar_SA');

            // Twitter Card
            $table->string('twitter_card')->default('summary_large_image');
            $table->string('twitter_title')->nullable();
            $table->text('twitter_description')->nullable();
            $table->string('twitter_image')->nullable();
            $table->string('twitter_creator')->nullable();

            // Schema.org / Rich Snippets
            $table->enum('schema_type', ['Article', 'BlogPosting', 'NewsArticle', 'TechArticle'])->default('BlogPosting');
            $table->text('schema_headline')->nullable();
            $table->text('schema_description')->nullable();
            $table->string('schema_image')->nullable();
            $table->timestamp('schema_published_time')->nullable();
            $table->timestamp('schema_modified_time')->nullable();
            $table->string('schema_author_name')->nullable();
            $table->string('schema_author_url')->nullable();

            // Breadcrumb Schema
            $table->json('breadcrumb_schema')->nullable();

            // Reading & Engagement
            $table->integer('reading_time')->nullable()->comment('Reading time in minutes');
            $table->integer('views_count')->default(0);
            $table->integer('shares_count')->default(0);
            $table->integer('comments_count')->default(0);

            // Publishing
            $table->enum('status', ['draft', 'published', 'scheduled', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();

            // SEO Settings
            $table->boolean('is_indexable')->default(true)->comment('Allow search engines to index');
            $table->boolean('is_followable')->default(true)->comment('Allow search engines to follow links');
            $table->enum('robots_meta', ['index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow'])->default('index,follow');

            // Featured & Priority
            $table->boolean('is_featured')->default(false);
            $table->integer('priority')->default(0)->comment('Higher number = higher priority');
            $table->integer('order')->default(0);

            // Content Quality Scores (for SEO analysis)
            $table->integer('seo_score')->nullable()->comment('Overall SEO score 0-100');
            $table->integer('readability_score')->nullable()->comment('Content readability score 0-100');
            $table->integer('keyword_density')->nullable()->comment('Focus keyword density percentage');

            // Related Content
            $table->json('related_posts')->nullable()->comment('Array of related post IDs');

            // Analytics & Tracking
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();

            // Additional Metadata
            $table->json('custom_meta')->nullable()->comment('Any custom meta tags');

            // Language & Localization
            $table->string('language', 5)->default('ar');
            $table->foreignId('translation_group_id')->nullable()->comment('Group ID for translations');

            $table->softDeletes();
            $table->timestamps();

            // Indexes for performance
            $table->index('slug');
            $table->index('status');
            $table->index('published_at');
            $table->index('is_featured');
            $table->index('blog_category_id');
            $table->index('author_id');
            $table->index(['status', 'published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
