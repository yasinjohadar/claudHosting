<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Services\GlobalSeoService;
use App\Services\PageSeoService;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\CreatesSeoTestSchema;
use Tests\TestCase;

class PageSeoServiceTest extends TestCase
{
    use CreatesSeoTestSchema;

    protected function tearDown(): void
    {
        $this->dropSeoTestTables();
        parent::tearDown();
    }

    public function test_resolves_home_page_seo_with_site_name_placeholder(): void
    {
        $service = app(PageSeoService::class);
        $seo = $service->resolve('home', []);

        $this->assertNotNull($seo);
        $this->assertTrue($seo['enabled']);
        $this->assertStringContainsString('استضافة كلاودسوفت', $seo['meta_title']);
        $this->assertStringNotContainsString('{site_name}', $seo['meta_title']);
        $this->assertNotEmpty($seo['meta_description']);
        $this->assertSame('index,follow', $seo['robots']);
        $this->assertNotEmpty($seo['schemas']);
    }

    public function test_home_seo_uses_custom_organization_name_from_global_settings(): void
    {
        $this->createSettingsTable();

        Setting::set('site_name', 'موقعي الجديد', 'general');
        Setting::set('contact_email', 'info@example.com', 'general');

        Setting::set(GlobalSeoService::SETTING_KEY, json_encode([
            'homepage' => [
                'meta_title' => '{site_name} | استضافة احترافية',
                'meta_description' => '{site_name} — {email}',
            ],
        ], JSON_UNESCAPED_UNICODE), GlobalSeoService::SETTING_GROUP);

        Cache::forget('frontend_global_seo_resolved');
        Cache::forget('app_settings_key_value');

        $seo = app(PageSeoService::class)->resolve('home', []);

        $this->assertStringContainsString('موقعي الجديد', $seo['meta_title']);
        $this->assertStringContainsString('info@example.com', $seo['meta_description']);
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

    public function test_blog_page_two_uses_paginated_title_and_robots(): void
    {
        $service = app(PageSeoService::class);
        $seo = $service->resolve('frontend.blog', ['_blog_page' => 2]);

        $this->assertNotNull($seo);
        $this->assertStringContainsString('صفحة 2', $seo['meta_title']);
        $this->assertSame('noindex,follow', $seo['robots']);
    }
}
