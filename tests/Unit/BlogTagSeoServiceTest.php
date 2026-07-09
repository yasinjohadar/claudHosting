<?php

namespace Tests\Unit;

use App\Models\BlogTag;
use App\Services\BlogTagSeoService;
use Tests\TestCase;

class BlogTagSeoServiceTest extends TestCase
{
    public function test_resolve_returns_canonical_and_meta(): void
    {
        $tag = new BlogTag([
            'name' => 'لارافيل',
            'slug' => 'laravel',
            'description' => 'مقالات عن Laravel.',
            'is_indexable' => true,
            'robots_meta' => 'index,follow',
            'is_active' => true,
        ]);

        $seo = app(BlogTagSeoService::class)->resolve($tag);

        $this->assertTrue($seo['enabled']);
        $this->assertStringContainsString('لارافيل', $seo['meta_title']);
        $this->assertSame(route('frontend.blog.tag', 'laravel'), $seo['canonical']);
        $this->assertSame('index,follow', $seo['robots']);
    }

    public function test_non_indexable_tag_is_noindex(): void
    {
        $tag = new BlogTag([
            'name' => 'داخلي',
            'slug' => 'internal',
            'is_indexable' => false,
        ]);

        $seo = app(BlogTagSeoService::class)->resolve($tag);

        $this->assertSame('noindex,nofollow', $seo['robots']);
    }
}
