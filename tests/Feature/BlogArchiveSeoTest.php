<?php

namespace Tests\Feature;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\Setting;
use App\Services\GlobalSeoService;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\CreatesSeoTestSchema;
use Tests\TestCase;

class BlogArchiveSeoTest extends TestCase
{
    use CreatesSeoTestSchema;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->createBlogArchiveTables();
        $this->seedMinimalBlogArchive();
    }

    protected function tearDown(): void
    {
        $this->dropSeoTestTables();
        parent::tearDown();
    }

    public function test_category_archive_route_returns_ok(): void
    {
        $response = $this->get(route('frontend.blog.category', 'tech'));

        $response->assertOk();
        $response->assertSee('تقنية', false);
    }

    public function test_tag_archive_route_returns_ok(): void
    {
        $response = $this->get(route('frontend.blog.tag', 'laravel'));

        $response->assertOk();
        $response->assertSee('لارافيل', false);
    }

    public function test_blog_index_redirects_category_query_to_clean_url(): void
    {
        $response = $this->get(route('frontend.blog', ['category' => 'tech']));

        $response->assertRedirect(route('frontend.blog.category', 'tech'));
        $response->assertStatus(301);
    }

    public function test_blog_index_redirects_tag_query_to_clean_url(): void
    {
        $response = $this->get(route('frontend.blog', ['tag' => 'laravel']));

        $response->assertRedirect(route('frontend.blog.tag', 'laravel'));
        $response->assertStatus(301);
    }

    public function test_blog_page_two_uses_paginated_robots(): void
    {
        Setting::set(GlobalSeoService::SETTING_KEY, json_encode([
            'blog' => [
                'paginated_title_template' => 'المدونة — صفحة {page}',
                'paginated_robots' => 'noindex,follow',
                'paginated_canonical_self' => true,
                'enable_prev_next' => true,
            ],
        ], JSON_UNESCAPED_UNICODE), GlobalSeoService::SETTING_GROUP);

        Cache::forget('frontend_global_seo_resolved');

        $response = $this->get(route('frontend.blog', ['page' => 2]));

        $response->assertOk();
        $response->assertSee('noindex,follow', false);
        $response->assertSee('rel="prev"', false);
    }

    public function test_robots_txt_uses_global_disallow_paths(): void
    {
        Setting::set(GlobalSeoService::SETTING_KEY, json_encode([
            'robots' => [
                'enable_sitemap_line' => true,
                'disallow_paths' => ['/secret'],
            ],
        ], JSON_UNESCAPED_UNICODE), GlobalSeoService::SETTING_GROUP);

        Cache::forget('frontend_global_seo_resolved');

        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertSee('Disallow: /secret');
        $response->assertSee('Sitemap:');
    }

    protected function seedMinimalBlogArchive(): void
    {
        $category = BlogCategory::create([
            'name' => 'تقنية',
            'slug' => 'tech',
            'is_active' => true,
            'is_indexable' => true,
            'order' => 1,
        ]);

        $tag = BlogTag::create([
            'name' => 'لارافيل',
            'slug' => 'laravel',
            'is_active' => true,
            'is_indexable' => true,
        ]);

        for ($i = 1; $i <= 13; $i++) {
            $post = BlogPost::create([
                'title' => "مقال {$i}",
                'slug' => "post-{$i}",
                'content' => '<p>محتوى</p>',
                'excerpt' => 'مقتطف',
                'status' => 'published',
                'published_at' => now()->subDays($i),
                'blog_category_id' => $category->id,
                'is_indexable' => true,
                'is_followable' => true,
            ]);
            $post->tags()->attach($tag->id);
        }
    }
}
