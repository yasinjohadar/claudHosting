<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PageSeoService
{
    public const SETTING_KEY = 'frontend_page_seo';

    public const SETTING_GROUP = 'seo';

    protected const CACHE_KEY = 'frontend_page_seo_resolved';

    protected const CACHE_TTL = 600;

    /**
     * Routes that manage their own head tags (blog article).
     *
     * @var list<string>
     */
    protected array $skipRoutes = [
        'frontend.blog.show',
        'frontend.blog.category',
        'frontend.blog.tag',
    ];

    /**
     * @return list<string>
     */
    public function manageableRouteNames(): array
    {
        return array_keys(config('seo.pages', []));
    }

    /**
     * @return array<string, string> route => label
     */
    public function manageablePagesForAdmin(): array
    {
        $pages = config('seo.pages', []);
        $out = [];
        foreach ($pages as $route => $page) {
            if (($page['schema'] ?? '') === 'package') {
                continue;
            }
            $out[$route] = $page['label'] ?? $route;
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    public function getStoredPages(): array
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
    public function getDefaultsForRoute(string $routeName): array
    {
        $pages = config('seo.pages', []);
        $defaults = $pages[$routeName] ?? [];

        return is_array($defaults) ? $defaults : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getPageConfig(string $routeName): array
    {
        $defaults = $this->getDefaultsForRoute($routeName);
        $stored = $this->getStoredPages()[$routeName] ?? [];

        if (! is_array($stored)) {
            $stored = [];
        }

        return array_replace_recursive($defaults, $stored);
    }

    /**
     * @param  array<string, mixed>  $viewData
     * @return array<string, mixed>|null null = use legacy @section tags
     */
    public function resolve(?string $routeName = null, array $viewData = []): ?array
    {
        $routeName = $routeName ?? request()->route()?->getName();

        if ($routeName === null || in_array($routeName, $this->skipRoutes, true)) {
            return null;
        }

        $defaults = $this->getDefaultsForRoute($routeName);
        if ($defaults === [] && $routeName !== 'home') {
            return null;
        }

        $page = $routeName === 'home'
            ? $this->getHomePageConfig()
            : $this->getPageConfig($routeName);

        $page = $this->applyDynamicPlaceholders($routeName, $page, $viewData);
        $page = $this->applySitePlaceholders($page);

        $globalSeo = app(GlobalSeoService::class);

        $canonical = $page['canonical'] ?? null;
        if (empty($canonical)) {
            $canonical = url()->current();
        } elseif (! str_starts_with($canonical, 'http')) {
            $canonical = url($canonical);
        }

        $blogPage = (int) ($viewData['_blog_page'] ?? request()->query('page', 1));
        if ($routeName === 'frontend.blog' && $blogPage > 1) {
            $blogSettings = $globalSeo->blogSettings();
            if (! ($blogSettings['paginated_canonical_self'] ?? true)) {
                $canonical = route('frontend.blog');
            }
        }

        $ogImage = $this->resolveImageUrl($page['og_image'] ?? null)
            ?? $globalSeo->defaultOgImageUrl();

        $metaTitle = Str::limit(trim((string) ($page['meta_title'] ?? '')), 70, '');
        $metaDescription = Str::limit(trim(strip_tags((string) ($page['meta_description'] ?? ''))), 160, '');

        if ($routeName === 'frontend.blog' && $blogPage > 1) {
            $template = $globalSeo->blogSettings()['paginated_title_template'] ?? 'المدونة — صفحة {page}';
            $metaTitle = Str::limit(
                $globalSeo->replaceSitePlaceholders(str_replace('{page}', (string) $blogPage, $template)),
                70,
                ''
            );
        }

        $ogTitle = trim((string) ($page['og_title'] ?? '')) ?: $metaTitle;
        $ogDescription = trim(strip_tags((string) ($page['og_description'] ?? ''))) ?: $metaDescription;

        $twitterTitle = trim((string) ($page['twitter_title'] ?? '')) ?: $ogTitle;
        $twitterDescription = trim(strip_tags((string) ($page['twitter_description'] ?? ''))) ?: $ogDescription;
        $twitterImage = $this->resolveImageUrl($page['twitter_image'] ?? null) ?? $ogImage;

        $robots = $page['robots'] ?? 'index,follow';
        if ($routeName === 'frontend.blog' && $blogPage > 1) {
            $robots = $globalSeo->blogSettings()['paginated_robots'] ?? 'noindex,follow';
        }

        $schemas = $this->buildSchemas($routeName, $page, $viewData, $canonical, $metaTitle, $metaDescription);

        return [
            'enabled' => true,
            'route' => $routeName,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'meta_keywords' => $globalSeo->replaceSitePlaceholders(trim((string) ($page['meta_keywords'] ?? ''))),
            'robots' => $robots,
            'canonical' => $canonical,
            'og' => [
                'type' => $page['og_type'] ?? 'website',
                'url' => $canonical,
                'title' => $ogTitle,
                'description' => Str::limit($ogDescription, 200, ''),
                'image' => $ogImage,
                'locale' => $page['og_locale'] ?? 'ar_AR',
            ],
            'twitter' => [
                'card' => $page['twitter_card'] ?? $globalSeo->twitterCardDefault(),
                'title' => $twitterTitle,
                'description' => Str::limit($twitterDescription, 200, ''),
                'image' => $twitterImage,
                'site' => $globalSeo->twitterSite(),
            ],
            'schemas' => $schemas,
        ];
    }

    /**
     * @param  array<string, mixed>  $viewData
     * @return array<string, mixed>
     */
    protected function applyDynamicPlaceholders(string $routeName, array $page, array $viewData): array
    {
        if ($routeName !== 'frontend.package-detail') {
            return $page;
        }

        $product = $viewData['product'] ?? null;
        if (! $product instanceof Product) {
            return $page;
        }

        $desc = Str::limit(strip_tags($product->description ?? $product->name), 160);
        $replacements = [
            '{name}' => $product->name,
            '{description}' => $desc,
        ];

        foreach (['meta_title', 'meta_description', 'og_title', 'og_description', 'twitter_title', 'twitter_description'] as $field) {
            if (! empty($page[$field])) {
                $page[$field] = str_replace(array_keys($replacements), array_values($replacements), $page[$field]);
            }
        }

        if (str_contains($page['meta_title'] ?? '', '{name}')) {
            $page['meta_title'] = $product->name.' | باقة استضافة | كلاودسوفت';
        }
        if (str_contains($page['meta_description'] ?? '', '{description}') || empty(trim($page['meta_description'] ?? ''))) {
            $page['meta_description'] = $desc ?: 'باقة استضافة من استضافة كلاودسوفت.';
        }

        $page['_product'] = $product;

        return $page;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getHomePageConfig(): array
    {
        $page = $this->getDefaultsForRoute('home');
        $globalHome = app(GlobalSeoService::class)->homepageSeo();

        foreach (['meta_title', 'meta_description', 'meta_keywords', 'og_title', 'og_description', 'robots'] as $field) {
            if (! empty(trim((string) ($globalHome[$field] ?? '')))) {
                $page[$field] = $globalHome[$field];
            }
        }

        $stored = $this->getStoredPages()['home'] ?? [];
        if (is_array($stored)) {
            foreach ($stored as $key => $value) {
                if (is_string($value) && trim($value) !== '') {
                    $page[$key] = $value;
                } elseif (! is_string($value) && $value !== null && $value !== '') {
                    $page[$key] = $value;
                }
            }
        }

        return $page;
    }

    /**
     * @param  array<string, mixed>  $page
     * @return array<string, mixed>
     */
    protected function applySitePlaceholders(array $page): array
    {
        $globalSeo = app(GlobalSeoService::class);

        foreach (['meta_title', 'meta_description', 'meta_keywords', 'og_title', 'og_description', 'twitter_title', 'twitter_description', 'canonical'] as $field) {
            if (! empty($page[$field]) && is_string($page[$field])) {
                $page[$field] = $globalSeo->replaceSitePlaceholders($page[$field]);
            }
        }

        return $page;
    }

    /**
     * @param  array<string, mixed>  $viewData
     * @return list<array<string, mixed>>
     */
    protected function buildSchemas(
        string $routeName,
        array $page,
        array $viewData,
        string $canonical,
        string $title,
        string $description
    ): array {
        $template = $page['schema'] ?? 'webpage';
        $globalSeo = app(GlobalSeoService::class);
        $org = $globalSeo->organization();
        $baseUrl = rtrim((string) ($org['url'] ?? config('app.url', url('/'))), '/');
        $logoUrl = $this->resolveImageUrl($org['logo'] ?? null) ?? $globalSeo->defaultOgImageUrl();

        $organization = [
            '@type' => 'Organization',
            '@id' => $baseUrl.'/#organization',
            'name' => $org['name'] ?? 'استضافة كلاودسوفت',
            'url' => $baseUrl,
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $logoUrl,
            ],
        ];
        if (! empty($org['email'])) {
            $organization['email'] = $org['email'];
        }

        $schemas = [];

        switch ($template) {
            case 'home':
                $schemas[] = $organization;
                $schemas[] = [
                    '@type' => 'WebSite',
                    '@id' => $baseUrl.'/#website',
                    'url' => $baseUrl,
                    'name' => $org['name'] ?? 'استضافة كلاودسوفت',
                    'description' => $description,
                    'inLanguage' => 'ar',
                    'publisher' => ['@id' => $baseUrl.'/#organization'],
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => [
                            '@type' => 'EntryPoint',
                            'urlTemplate' => $globalSeo->searchActionUrlTemplate(),
                        ],
                        'query-input' => 'required name=search_term_string',
                    ],
                ];
                break;

            case 'service':
                $serviceName = $page['service_name'] ?? $title;
                $schemas[] = $organization;
                $schemas[] = [
                    '@type' => 'Service',
                    'name' => $serviceName,
                    'description' => $description,
                    'url' => $canonical,
                    'provider' => ['@id' => $baseUrl.'/#organization'],
                    'areaServed' => 'SY',
                    'serviceType' => $serviceName,
                ];
                $breadcrumb = $this->breadcrumbSchema($page['breadcrumbs'] ?? [], $canonical, $baseUrl);
                if ($breadcrumb) {
                    $schemas[] = $breadcrumb;
                }
                break;

            case 'contact':
                $schemas[] = $organization;
                $schemas[] = [
                    '@type' => 'ContactPage',
                    'name' => $title,
                    'description' => $description,
                    'url' => $canonical,
                    'inLanguage' => 'ar',
                ];
                break;

            case 'consultation':
                $schemas[] = $organization;
                $schemas[] = [
                    '@type' => 'Service',
                    'name' => 'استشارة تقنية',
                    'description' => $description,
                    'url' => $canonical,
                    'provider' => ['@id' => $baseUrl.'/#organization'],
                ];
                $schemas[] = $this->consultationFaqSchema();
                break;

            case 'packages_list':
                $schemas[] = $organization;
                $schemas[] = [
                    '@type' => 'CollectionPage',
                    'name' => $title,
                    'description' => $description,
                    'url' => $canonical,
                    'inLanguage' => 'ar',
                ];
                break;

            case 'package':
                $product = $page['_product'] ?? ($viewData['product'] ?? null);
                $schemas[] = $organization;
                if ($product instanceof Product) {
                    $schemas[] = [
                        '@type' => 'Product',
                        'name' => $product->name,
                        'description' => $description,
                        'url' => $canonical,
                        'brand' => ['@type' => 'Brand', 'name' => $org['name'] ?? 'كلاودسوفت'],
                        'offers' => [
                            '@type' => 'Offer',
                            'url' => $canonical,
                            'priceCurrency' => $product->currency ?? 'USD',
                            'availability' => 'https://schema.org/InStock',
                        ],
                    ];
                }
                $schemas[] = [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        [
                            '@type' => 'ListItem',
                            'position' => 1,
                            'name' => 'الرئيسية',
                            'item' => $baseUrl,
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 2,
                            'name' => 'الباقات',
                            'item' => route('frontend.packages'),
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 3,
                            'name' => $product instanceof Product ? $product->name : 'الباقة',
                            'item' => $canonical,
                        ],
                    ],
                ];
                break;

            case 'blog_list':
                $schemas[] = $organization;
                $schemas[] = [
                    '@type' => 'Blog',
                    'name' => $title,
                    'description' => $description,
                    'url' => $canonical,
                    'inLanguage' => 'ar',
                    'publisher' => ['@id' => $baseUrl.'/#organization'],
                ];
                break;

            case 'domain_search':
                $schemas[] = $organization;
                $schemas[] = [
                    '@type' => 'WebPage',
                    'name' => $title,
                    'description' => $description,
                    'url' => $canonical,
                    'inLanguage' => 'ar',
                ];
                break;

            case 'about':
                $schemas[] = $organization;
                $schemas[] = [
                    '@type' => 'AboutPage',
                    'name' => $title,
                    'description' => $description,
                    'url' => $canonical,
                    'inLanguage' => 'ar',
                ];
                break;

            default:
                $schemas[] = $organization;
                $schemas[] = [
                    '@type' => 'WebPage',
                    'name' => $title,
                    'description' => $description,
                    'url' => $canonical,
                    'inLanguage' => 'ar',
                ];
                break;
        }

        return array_values(array_filter($schemas));
    }

    /**
     * @param  list<array{name: string, url: string|null}>  $crumbs
     * @return array<string, mixed>|null
     */
    protected function breadcrumbSchema(array $crumbs, string $canonical, string $baseUrl): ?array
    {
        if ($crumbs === []) {
            return null;
        }

        $items = [];
        $position = 1;
        foreach ($crumbs as $index => $crumb) {
            $item = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $crumb['name'] ?? '',
            ];
            $url = $crumb['url'] ?? null;
            if ($url !== null && $url !== '') {
                $item['item'] = str_starts_with($url, 'http') ? $url : $baseUrl.'/'.ltrim($url, '/');
            } elseif ($index === count($crumbs) - 1) {
                $item['item'] = $canonical;
            }
            $items[] = $item;
        }

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function consultationFaqSchema(): array
    {
        return [
            '@type' => 'FAQPage',
            'mainEntity' => [
                [
                    '@type' => 'Question',
                    'name' => 'كيف تتم الجلسة — أونلاين أم حضورياً؟',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'غالباً تكون الجلسة أونلاين عبر مكالمة فيديو (زوم، Google Meet، تيمز أو واتساب).',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name' => 'ما مدة الانتظار حتى تأكيد الموعد؟',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'نحاول الرد على طلبات الحجز خلال 24–48 ساعة.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name' => 'هل يمكن إلغاء أو تأجيل الموعد؟',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'نعم، يُفضّل الإبلاغ قبل الموعد بـ 24 ساعة على الأقل.',
                    ],
                ],
            ],
        ];
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

        return asset($normalized);
    }

    /**
     * @param  array<string, array<string, mixed>>  $pagesPayload
     */
    public function save(array $pagesPayload, Request $request): void
    {
        $current = $this->getStoredPages();

        foreach ($pagesPayload as $routeName => $fields) {
            if (! is_array($fields)) {
                continue;
            }

            $existing = $current[$routeName] ?? [];

            $merged = array_merge($existing, array_filter([
                'meta_title' => $fields['meta_title'] ?? null,
                'meta_description' => $fields['meta_description'] ?? null,
                'meta_keywords' => $fields['meta_keywords'] ?? null,
                'robots' => $fields['robots'] ?? null,
                'canonical' => $fields['canonical'] ?? null,
                'og_title' => $fields['og_title'] ?? null,
                'og_description' => $fields['og_description'] ?? null,
                'og_type' => $fields['og_type'] ?? null,
                'twitter_title' => $fields['twitter_title'] ?? null,
                'twitter_description' => $fields['twitter_description'] ?? null,
                'twitter_card' => $fields['twitter_card'] ?? null,
            ], fn ($v) => $v !== null && $v !== ''));

            $fileKey = "og_image_{$routeName}";
            if ($request->hasFile($fileKey)) {
                $this->deleteStoredFile($existing['og_image'] ?? null);
                $merged['og_image'] = $this->storeImage($request->file($fileKey));
            } elseif ($request->boolean("remove_{$fileKey}")) {
                $this->deleteStoredFile($existing['og_image'] ?? null);
                $merged['og_image'] = null;
            } else {
                $merged['og_image'] = $existing['og_image'] ?? null;
            }

            $current[$routeName] = $merged;
        }

        Setting::set(self::SETTING_KEY, json_encode($current, JSON_UNESCAPED_UNICODE), self::SETTING_GROUP);
        Cache::forget(self::CACHE_KEY);
    }

    public function resetRouteToDefaults(string $routeName): void
    {
        $current = $this->getStoredPages();
        $existing = $current[$routeName] ?? [];
        if (! empty($existing['og_image'])) {
            $this->deleteStoredFile($existing['og_image']);
        }
        unset($current[$routeName]);
        Setting::set(self::SETTING_KEY, json_encode($current, JSON_UNESCAPED_UNICODE), self::SETTING_GROUP);
        Cache::forget(self::CACHE_KEY);
    }

    protected function storeImage(UploadedFile $file): string
    {
        return $file->store('seo/og', 'public');
    }

    protected function deleteStoredFile(?string $path): void
    {
        if (empty($path) || filter_var($path, FILTER_VALIDATE_URL)) {
            return;
        }

        $path = ltrim(str_replace('storage/', '', $path), '/');

        try {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
        } catch (\Throwable) {
            // ignore
        }
    }
}
