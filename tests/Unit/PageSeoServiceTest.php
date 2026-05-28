<?php

namespace Tests\Unit;

use App\Services\PageSeoService;
use Tests\TestCase;

class PageSeoServiceTest extends TestCase
{

    public function test_resolves_home_page_seo(): void
    {
        $service = app(PageSeoService::class);
        $seo = $service->resolve('home', []);

        $this->assertNotNull($seo);
        $this->assertTrue($seo['enabled']);
        $this->assertStringContainsString('كلاودسوفت', $seo['meta_title']);
        $this->assertNotEmpty($seo['meta_description']);
        $this->assertSame('index,follow', $seo['robots']);
        $this->assertNotEmpty($seo['schemas']);
    }

    public function test_skips_blog_show_route(): void
    {
        $service = app(PageSeoService::class);
        $this->assertNull($service->resolve('frontend.blog.show', []));
    }

    public function test_package_order_is_noindex(): void
    {
        $service = app(PageSeoService::class);
        $seo = $service->resolve('frontend.package.order.form', []);

        $this->assertNotNull($seo);
        $this->assertStringContainsString('noindex', $seo['robots']);
    }
}
