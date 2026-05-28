<?php

namespace Tests\Unit;

use App\Models\BlogPost;
use App\Services\BlogPostSeoService;
use Tests\TestCase;

class BlogPostSeoServiceTest extends TestCase
{
    public function test_resolve_returns_enabled_seo_with_canonical_and_schemas(): void
    {
        $post = $this->unsavedPost([
            'title' => 'مقال تجريبي',
            'excerpt' => 'وصف قصير للمقال التجريبي.',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'is_indexable' => true,
            'is_followable' => true,
        ]);

        $seo = app(BlogPostSeoService::class)->resolve($post);

        $this->assertTrue($seo['enabled']);
        $this->assertStringContainsString('مقال تجريبي', $seo['meta_title']);
        $this->assertNotEmpty($seo['meta_description']);
        $this->assertSame(route('frontend.blog.show', $post->slug), $seo['canonical']);
        $this->assertSame('index,follow', $seo['robots']);
        $this->assertNotEmpty($seo['schemas']);
        $this->assertSame('article', $seo['og']['type']);
    }

    public function test_draft_posts_are_noindex(): void
    {
        $post = $this->unsavedPost([
            'status' => 'draft',
            'is_indexable' => true,
        ]);

        $seo = app(BlogPostSeoService::class)->resolve($post);

        $this->assertSame('noindex,nofollow', $seo['robots']);
    }

    public function test_apply_defaults_on_save_fills_meta_and_robots(): void
    {
        $service = app(BlogPostSeoService::class);

        $data = $service->applyDefaultsOnSave([
            'title' => 'عنوان',
            'excerpt' => 'مقتطف',
            'slug' => 'test-slug',
            'status' => 'published',
            'is_indexable' => true,
            'is_followable' => false,
        ]);

        $this->assertSame('عنوان', $data['meta_title']);
        $this->assertSame('مقتطف', $data['meta_description']);
        $this->assertSame('index,nofollow', $data['robots_meta']);
        $this->assertSame('BlogPosting', $data['schema_type']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function unsavedPost(array $attributes = []): BlogPost
    {
        return new BlogPost(array_merge([
            'title' => 'مقال',
            'slug' => 'test-post',
            'content' => '<p>محتوى</p>',
            'status' => 'published',
            'published_at' => now(),
            'is_indexable' => true,
            'is_followable' => true,
        ], $attributes));
    }
}
