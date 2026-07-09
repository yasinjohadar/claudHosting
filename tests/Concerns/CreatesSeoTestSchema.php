<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

trait CreatesSeoTestSchema
{
    protected function createSettingsTable(): void
    {
        if (Schema::hasTable('settings')) {
            return;
        }

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->nullable();
            $table->timestamps();
        });
    }

    protected function createBlogArchiveTables(): void
    {
        $this->createSettingsTable();

        if (! Schema::hasTable('blog_categories')) {
            Schema::create('blog_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_indexable')->default(true);
                $table->string('robots_meta')->nullable();
                $table->integer('order')->default(0);
                $table->integer('posts_count')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('blog_tags')) {
            Schema::create('blog_tags', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('canonical_url')->nullable();
                $table->string('og_title')->nullable();
                $table->text('og_description')->nullable();
                $table->string('og_image')->nullable();
                $table->string('robots_meta')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_indexable')->default(true);
                $table->integer('posts_count')->default(0);
                $table->integer('order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('blog_posts')) {
            Schema::create('blog_posts', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('excerpt')->nullable();
                $table->longText('content');
                $table->foreignId('blog_category_id')->nullable();
                $table->enum('status', ['draft', 'published', 'scheduled', 'archived'])->default('draft');
                $table->timestamp('published_at')->nullable();
                $table->boolean('is_indexable')->default(true);
                $table->boolean('is_followable')->default(true);
                $table->string('robots_meta')->nullable();
                $table->timestamp('schema_published_time')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('blog_post_tag')) {
            Schema::create('blog_post_tag', function (Blueprint $table) {
                $table->id();
                $table->foreignId('blog_post_id');
                $table->foreignId('blog_tag_id');
                $table->timestamps();
            });
        }
    }

    protected function dropSeoTestTables(): void
    {
        Schema::dropIfExists('blog_post_tag');
        Schema::dropIfExists('blog_posts');
        Schema::dropIfExists('blog_tags');
        Schema::dropIfExists('blog_categories');
        Schema::dropIfExists('settings');
        Cache::forget('frontend_global_seo_resolved');
        Cache::forget('app_settings_key_value');
    }
}
