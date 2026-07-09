<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Services\GlobalSeoService;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\CreatesSeoTestSchema;
use Tests\TestCase;

class GlobalSeoServiceTest extends TestCase
{
    use CreatesSeoTestSchema;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        $this->dropSeoTestTables();
        parent::tearDown();
    }

    public function test_defaults_include_organization_and_blog_settings(): void
    {
        $defaults = app(GlobalSeoService::class)->defaults();

        $this->assertArrayHasKey('organization', $defaults);
        $this->assertArrayHasKey('blog', $defaults);
        $this->assertArrayHasKey('robots', $defaults);
        $this->assertArrayHasKey('sitemap', $defaults);
        $this->assertStringContainsString('{page}', $defaults['blog']['paginated_title_template']);
    }

    public function test_resolve_uses_stored_settings(): void
    {
        $this->createSettingsTable();

        Setting::set(GlobalSeoService::SETTING_KEY, json_encode([
            'homepage_fallback_h1' => 'عنوان رئيسي مخصص',
            'twitter_site' => '@cloudsoft',
        ], JSON_UNESCAPED_UNICODE), GlobalSeoService::SETTING_GROUP);

        Cache::forget('frontend_global_seo_resolved');

        $service = app(GlobalSeoService::class);

        $this->assertSame('عنوان رئيسي مخصص', $service->homepageFallbackH1());
        $this->assertSame('@cloudsoft', $service->twitterSite());
    }

    public function test_replace_site_placeholders(): void
    {
        $service = app(GlobalSeoService::class);

        $result = $service->replaceSitePlaceholders('{site_name} | {legal_name}');

        $this->assertStringContainsString('استضافة كلاودسوفت', $result);
        $this->assertStringContainsString('CloudSoft Hosting', $result);
    }

    public function test_homepage_seo_defaults_use_site_name_placeholder(): void
    {
        $homepage = app(GlobalSeoService::class)->homepageSeo();

        $this->assertStringContainsString('{site_name}', $homepage['meta_title'] ?? '');
    }

    public function test_organization_reads_from_site_settings(): void
    {
        $this->createSettingsTable();

        Setting::set('site_name', 'ClaudSoft Hosting', 'general');
        Setting::set('contact_email', 'info@cloudsofthosting.com', 'general');
        Cache::forget('app_settings_key_value');

        $org = app(GlobalSeoService::class)->organization();

        $this->assertSame('ClaudSoft Hosting', $org['name']);
        $this->assertSame('info@cloudsofthosting.com', $org['email']);
    }

    public function test_robots_disallow_paths_from_settings(): void
    {
        $this->createSettingsTable();

        Setting::set(GlobalSeoService::SETTING_KEY, json_encode([
            'robots' => [
                'disallow_paths' => ['/custom', '/private/'],
            ],
        ], JSON_UNESCAPED_UNICODE), GlobalSeoService::SETTING_GROUP);

        Cache::forget('frontend_global_seo_resolved');

        $paths = app(GlobalSeoService::class)->robotsDisallowPaths();

        $this->assertContains('/custom', $paths);
        $this->assertContains('/private/', $paths);
    }
}
