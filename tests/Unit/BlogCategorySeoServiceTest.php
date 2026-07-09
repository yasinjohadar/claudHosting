<?php

namespace Tests\Unit;

use App\Models\BlogCategory;
use App\Services\BlogCategorySeoService;
use Tests\TestCase;

class BlogCategorySeoServiceTest extends TestCase
{
    public function test_resolve_returns_canonical_and_indexable_robots(): void
    {
        $category = new BlogCategory([
            'name' => 'أخبار',
            'slug' => 'news',
            'description' => 'آخر الأخبار التقنية.',
            'is_indexable' => true,
            'robots_meta' => 'index,follow',
            'is_active' => true,
        ]);

        $seo = app(BlogCategorySeoService::class)->resolve($category);

        $this->assertTrue($seo['enabled']);
        $this->assertStringContainsString('أخبار', $seo['meta_title']);
        $this->assertSame(route('frontend.blog.category', 'news'), $seo['canonical']);
        $this->assertSame('index,follow', $seo['robots']);
        $this->assertNotEmpty($seo['schemas']);
    }

    public function test_non_indexable_category_is_noindex(): void
    {
        $category = new BlogCategory([
            'name' => 'خاص',
            'slug' => 'private',
            'is_indexable' => false,
        ]);

        $seo = app(BlogCategorySeoService::class)->resolve($category);

        $this->assertSame('noindex,nofollow', $seo['robots']);
    }

    public function test_paginated_category_uses_global_robots(): void
    {
        $category = new BlogCategory([
            'name' => 'أخبار',
            'slug' => 'news',
            'is_indexable' => true,
        ]);

        $seo = app(BlogCategorySeoService::class)->resolve($category, 2);

        $this->assertStringContainsString('news', $seo['canonical']);
        $this->assertStringContainsString('page=2', $seo['canonical']);
        $this->assertSame('noindex,follow', $seo['robots']);
    }
}
