<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

class GlobalSeoService
{
    public const SETTING_KEY = 'frontend_global_seo';

    public const SETTING_GROUP = 'seo';

    protected const CACHE_KEY = 'frontend_global_seo_resolved';

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        $org = config('seo.organization', []);
        $domainSearchUrl = Route::has('frontend.domain-search')
            ? route('frontend.domain-search').'?domain={search_term_string}'
            : url('/domain-search').'?domain={search_term_string}';

        return [
            'organization' => [
                'name' => $org['name'] ?? 'استضافة كلاودسوفت',
                'legal_name' => $org['legal_name'] ?? 'CloudSoft Hosting',
                'url' => $org['url'] ?? config('app.url', url('/')),
                'email' => $org['email'] ?? '',
                'phone' => $org['phone'] ?? '',
                'logo' => $org['logo'] ?? 'frontend/assets/images/logo.png',
            ],
            'default_og_image' => config('seo.default_og_image', 'frontend/assets/images/logo.png'),
            'twitter_site' => '',
            'twitter_card_default' => 'summary_large_image',
            'search_action_url_template' => $domainSearchUrl,
            'homepage_fallback_h1' => '{site_name} | استضافة مواقع سحابية',
            'homepage' => [
                'meta_title' => '{site_name} | باقات استضافة ودعم فني',
                'meta_description' => '{site_name} — استضافة مواقع سحابية سريعة وآمنة مع SSL مجاني، نسخ احتياطي يومي، باقات مرنة للمواقع والمتاجر، ودعم فني عربي مستمر. ابدأ خلال دقائق.',
                'meta_keywords' => 'استضافة مواقع, استضافة سحابية, استضافة ويب, باقات استضافة, VPS, نطاقات, SSL, {site_name}',
                'og_title' => '{site_name} | استضافة مواقع سحابية',
                'og_description' => '{site_name} — استضافة مواقع سحابية سريعة وآمنة مع دعم فني عربي.',
                'robots' => 'index,follow',
            ],
            'blog' => [
                'paginated_title_template' => 'المدونة — صفحة {page} | {site_name}',
                'paginated_robots' => 'noindex,follow',
                'paginated_canonical_self' => true,
                'enable_prev_next' => true,
            ],
            'robots' => [
                'enable_sitemap_line' => true,
                'disallow_paths' => [
                    '/admin',
                    '/admin/',
                    '/client',
                    '/client/',
                    '/login',
                    '/register',
                    '/password/',
                ],
            ],
            'sitemap' => [
                'static_pages' => true,
                'products' => true,
                'blog_posts' => true,
                'blog_categories' => true,
                'blog_tags' => true,
            ],
            'updated_at' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getStored(): array
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('settings')) {
                return [];
            }
        } catch (\Throwable) {
            return [];
        }

        $raw = Setting::getByKey(self::SETTING_KEY);
        if ($raw === null || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function resolve(): array
    {
        return Cache::remember(self::CACHE_KEY, 600, function () {
            $defaults = $this->defaults();
            $stored = $this->getStored();

            return array_replace_recursive($defaults, $stored);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function siteSettings(): array
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('settings')) {
                return [];
            }
        } catch (\Throwable) {
            return [];
        }

        return Setting::getAllKeyValue();
    }

    public function siteDescription(): string
    {
        return trim((string) ($this->siteSettings()['footer_description'] ?? ''));
    }

    /**
     * @return array<string, mixed>
     */
    public function organization(): array
    {
        $defaults = $this->defaults()['organization'];
        $site = $this->siteSettings();
        $storedOrg = $this->getStored()['organization'] ?? [];

        $org = [
            'name' => trim((string) ($site['site_name'] ?? '')) ?: ($storedOrg['name'] ?? $defaults['name']),
            'legal_name' => trim((string) ($storedOrg['legal_name'] ?? ''))
                ?: (trim((string) ($site['site_name'] ?? '')) ?: $defaults['legal_name']),
            'url' => trim((string) ($storedOrg['url'] ?? '')) ?: ($defaults['url'] ?? config('app.url', url('/'))),
            'email' => trim((string) ($site['contact_email'] ?? '')) ?: ($storedOrg['email'] ?? $defaults['email']),
            'phone' => trim((string) ($site['contact_phone'] ?? '')) ?: ($storedOrg['phone'] ?? $defaults['phone']),
            'logo' => $storedOrg['logo'] ?? $defaults['logo'],
        ];

        return $org;
    }

    public function defaultOgImagePath(): string
    {
        return (string) ($this->resolve()['default_og_image'] ?? config('seo.default_og_image'));
    }

    public function defaultOgImageUrl(): string
    {
        return $this->resolveImageUrl($this->defaultOgImagePath())
            ?? asset('frontend/assets/images/logo.png');
    }

    public function twitterSite(): ?string
    {
        $site = trim((string) ($this->resolve()['twitter_site'] ?? ''));

        return $site !== '' ? $site : null;
    }

    public function twitterCardDefault(): string
    {
        return (string) ($this->resolve()['twitter_card_default'] ?? 'summary_large_image');
    }

    public function searchActionUrlTemplate(): string
    {
        $template = trim((string) ($this->resolve()['search_action_url_template'] ?? ''));

        if ($template === '') {
            return $this->defaults()['search_action_url_template'];
        }

        return $template;
    }

    public function homepageFallbackH1(): string
    {
        $h1 = trim((string) ($this->resolve()['homepage_fallback_h1'] ?? ''));

        $h1 = $h1 !== '' ? $h1 : $this->defaults()['homepage_fallback_h1'];

        return $this->replaceSitePlaceholders($h1);
    }

    /**
     * @return array<string, mixed>
     */
    public function homepageSeo(): array
    {
        $defaults = $this->defaults()['homepage'] ?? [];
        $stored = $this->resolve()['homepage'] ?? [];

        return array_replace_recursive($defaults, is_array($stored) ? $stored : []);
    }

    /**
     * @return list<string>
     */
    public static function sitePlaceholderKeys(): array
    {
        return ['{site_name}', '{organization}', '{legal_name}', '{site_url}', '{email}', '{phone}', '{site_description}', '{address}'];
    }

    public function replaceSitePlaceholders(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        $org = $this->organization();
        $site = $this->siteSettings();

        return str_replace(
            ['{site_name}', '{organization}', '{legal_name}', '{site_url}', '{email}', '{phone}', '{site_description}', '{address}'],
            [
                (string) ($org['name'] ?? ''),
                (string) ($org['name'] ?? ''),
                (string) ($org['legal_name'] ?? ''),
                (string) ($org['url'] ?? config('app.url', url('/'))),
                (string) ($org['email'] ?? ''),
                (string) ($org['phone'] ?? ''),
                $this->siteDescription(),
                trim((string) ($site['contact_address'] ?? '')),
            ],
            $text
        );
    }

    /**
     * @param  array<string, mixed>  $homepage
     */
    public function saveHomepage(array $homepage): void
    {
        $current = array_replace_recursive($this->defaults(), $this->getStored());

        $current['homepage'] = array_merge($current['homepage'] ?? $this->defaults()['homepage'], array_filter([
            'meta_title' => $homepage['meta_title'] ?? null,
            'meta_description' => $homepage['meta_description'] ?? null,
            'meta_keywords' => $homepage['meta_keywords'] ?? null,
            'og_title' => $homepage['og_title'] ?? null,
            'og_description' => $homepage['og_description'] ?? null,
            'robots' => $homepage['robots'] ?? null,
        ], fn ($v) => $v !== null));

        if (array_key_exists('homepage_fallback_h1', $homepage)) {
            $current['homepage_fallback_h1'] = $homepage['homepage_fallback_h1'];
        }

        $current['updated_at'] = now()->toAtomString();

        Setting::set(self::SETTING_KEY, json_encode($current, JSON_UNESCAPED_UNICODE), self::SETTING_GROUP);
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, mixed>
     */
    public function blogSettings(): array
    {
        return $this->resolve()['blog'] ?? $this->defaults()['blog'];
    }

    /**
     * @return array<string, mixed>
     */
    public function robotsSettings(): array
    {
        return $this->resolve()['robots'] ?? $this->defaults()['robots'];
    }

    /**
     * @return array<string, mixed>
     */
    public function sitemapSettings(): array
    {
        return $this->resolve()['sitemap'] ?? $this->defaults()['sitemap'];
    }

    public function isSitemapSectionEnabled(string $section): bool
    {
        $settings = $this->sitemapSettings();

        return (bool) ($settings[$section] ?? true);
    }

    /**
     * @return list<string>
     */
    public function robotsDisallowPaths(): array
    {
        $paths = $this->robotsSettings()['disallow_paths'] ?? [];

        if (! is_array($paths)) {
            return $this->defaults()['robots']['disallow_paths'];
        }

        return array_values(array_filter(array_map('trim', $paths)));
    }

    public function resolveImageUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        if (function_exists('hero_asset_url')) {
            $url = hero_asset_url($path);
            if ($url) {
                return $url;
            }
        }

        $normalized = ltrim($path, '/');
        if (str_starts_with($normalized, 'storage/')) {
            return asset($normalized);
        }

        if (Storage::disk('public')->exists($normalized)) {
            return asset('storage/'.$normalized);
        }

        return asset($normalized);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function save(array $payload, Request $request): void
    {
        $current = array_replace_recursive($this->defaults(), $this->getStored());

        if (isset($payload['organization']) && is_array($payload['organization'])) {
            $current['organization'] = array_merge($current['organization'], array_filter([
                'name' => $payload['organization']['name'] ?? null,
                'legal_name' => $payload['organization']['legal_name'] ?? null,
                'url' => $payload['organization']['url'] ?? null,
                'email' => $payload['organization']['email'] ?? null,
                'phone' => $payload['organization']['phone'] ?? null,
            ], fn ($v) => $v !== null));
        }

        foreach (['twitter_site', 'twitter_card_default', 'search_action_url_template', 'homepage_fallback_h1'] as $field) {
            if (array_key_exists($field, $payload)) {
                $current[$field] = $payload[$field];
            }
        }

        if (isset($payload['homepage']) && is_array($payload['homepage'])) {
            $current['homepage'] = array_merge($current['homepage'] ?? $this->defaults()['homepage'], array_filter([
                'meta_title' => $payload['homepage']['meta_title'] ?? null,
                'meta_description' => $payload['homepage']['meta_description'] ?? null,
                'meta_keywords' => $payload['homepage']['meta_keywords'] ?? null,
                'og_title' => $payload['homepage']['og_title'] ?? null,
                'og_description' => $payload['homepage']['og_description'] ?? null,
                'robots' => $payload['homepage']['robots'] ?? null,
            ], fn ($v) => $v !== null));
        }

        if (isset($payload['blog']) && is_array($payload['blog'])) {
            $current['blog'] = array_merge($current['blog'], [
                'paginated_title_template' => $payload['blog']['paginated_title_template'] ?? $current['blog']['paginated_title_template'],
                'paginated_robots' => $payload['blog']['paginated_robots'] ?? $current['blog']['paginated_robots'],
                'paginated_canonical_self' => $request->boolean('blog_paginated_canonical_self', $current['blog']['paginated_canonical_self'] ?? true),
                'enable_prev_next' => $request->boolean('blog_enable_prev_next', $current['blog']['enable_prev_next'] ?? true),
            ]);
        }

        if (isset($payload['robots']) && is_array($payload['robots'])) {
            $disallowRaw = $payload['robots']['disallow_paths'] ?? '';
            $paths = is_string($disallowRaw)
                ? preg_split('/\r\n|\r|\n/', $disallowRaw) ?: []
                : (array) $disallowRaw;

            $current['robots'] = [
                'enable_sitemap_line' => $request->boolean('robots_enable_sitemap_line', true),
                'disallow_paths' => array_values(array_filter(array_map('trim', $paths))),
            ];
        }

        if (isset($payload['sitemap']) && is_array($payload['sitemap'])) {
            $current['sitemap'] = [
                'static_pages' => $request->boolean('sitemap_static_pages', true),
                'products' => $request->boolean('sitemap_products', true),
                'blog_posts' => $request->boolean('sitemap_blog_posts', true),
                'blog_categories' => $request->boolean('sitemap_blog_categories', true),
                'blog_tags' => $request->boolean('sitemap_blog_tags', true),
            ];
        }

        if ($request->hasFile('default_og_image')) {
            $this->deleteStoredFile($current['default_og_image'] ?? null);
            $current['default_og_image'] = $this->storeImage($request->file('default_og_image'));
        } elseif ($request->boolean('remove_default_og_image')) {
            $this->deleteStoredFile($current['default_og_image'] ?? null);
            $current['default_og_image'] = $this->defaults()['default_og_image'];
        }

        if ($request->hasFile('organization_logo')) {
            $this->deleteStoredFile($current['organization']['logo'] ?? null);
            $current['organization']['logo'] = $this->storeImage($request->file('organization_logo'));
        } elseif ($request->boolean('remove_organization_logo')) {
            $this->deleteStoredFile($current['organization']['logo'] ?? null);
            $current['organization']['logo'] = $this->defaults()['organization']['logo'];
        }

        $current['updated_at'] = now()->toAtomString();

        Setting::set(self::SETTING_KEY, json_encode($current, JSON_UNESCAPED_UNICODE), self::SETTING_GROUP);
        Cache::forget(self::CACHE_KEY);
    }

    public function resetToDefaults(): void
    {
        $stored = $this->getStored();
        if (! empty($stored['default_og_image'])) {
            $this->deleteStoredFile($stored['default_og_image']);
        }
        if (! empty($stored['organization']['logo'])) {
            $this->deleteStoredFile($stored['organization']['logo']);
        }

        Setting::set(self::SETTING_KEY, '', self::SETTING_GROUP);
        Cache::forget(self::CACHE_KEY);
    }

    public function settingsUpdatedAt(): ?string
    {
        return $this->resolve()['updated_at'] ?? null;
    }

    protected function storeImage(UploadedFile $file): string
    {
        return $file->store('seo/global', 'public');
    }

    protected function deleteStoredFile(?string $path): void
    {
        if (empty($path) || filter_var($path, FILTER_VALIDATE_URL)) {
            return;
        }

        $path = ltrim(str_replace('storage/', '', $path), '/');

        try {
            Storage::disk('public')->delete($path);
        } catch (\Throwable) {
            // ignore
        }
    }
}
