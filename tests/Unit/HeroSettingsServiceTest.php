<?php

namespace Tests\Unit;

use App\Services\HeroSettingsService;
use App\Services\Storage\StorageHelperService;
use Mockery;
use Tests\TestCase;

class HeroSettingsServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_defaults_contain_content_and_themes(): void
    {
        $service = new HeroSettingsService(Mockery::mock(StorageHelperService::class));
        $defaults = $service->getDefaults();

        $this->assertTrue($defaults['enabled']);
        $this->assertNotEmpty($defaults['content']['title_prefix']);
        $this->assertCount(3, $defaults['content']['typing_texts']);
        $this->assertArrayHasKey('light', $defaults);
        $this->assertArrayHasKey('dark', $defaults);
    }

    public function test_build_background_css_gradient(): void
    {
        $service = new HeroSettingsService(Mockery::mock(StorageHelperService::class));

        $css = $service->buildBackgroundCss([
            'mode' => 'gradient',
            'gradient_from' => '#ffffff',
            'gradient_to' => '#000000',
            'gradient_angle' => 90,
        ]);

        $this->assertStringContainsString('linear-gradient(90deg', $css);
        $this->assertStringContainsString('#ffffff', $css);
    }

    public function test_build_background_css_inherit_returns_null(): void
    {
        $service = new HeroSettingsService(Mockery::mock(StorageHelperService::class));

        $this->assertNull($service->buildBackgroundCss(['mode' => 'inherit']));
    }

    public function test_resolve_hero_image_url_uses_fallback_asset(): void
    {
        $service = new HeroSettingsService(Mockery::mock(StorageHelperService::class));

        $url = $service->resolveHeroImageUrl(null, 'frontend/assets/images/hero-light.webp');

        $this->assertStringContainsString('hero-light.webp', $url);
    }

    public function test_build_background_css_color(): void
    {
        $service = new HeroSettingsService(Mockery::mock(StorageHelperService::class));

        $this->assertSame('#f0f2f5', $service->buildBackgroundCss([
            'mode' => 'color',
            'color' => '#f0f2f5',
        ]));
    }
}
