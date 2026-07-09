<?php

namespace App\Services;

use App\Models\BlogCategory;
use Illuminate\Support\Str;

class BlogCategorySeoService
{
    public function __construct(
        protected PageSeoService $pageSeo,
        protected GlobalSeoService $globalSeo
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function resolve(BlogCategory $category, ?int $page = null): array
    {
        $canonical = $this->resolveCanonical($category, $page);
        $metaTitle = $this->resolveMetaTitle($category, $page);
        $metaDescription = $this->resolveMetaDescription($category);
        $metaKeywords = trim((string) ($category->meta_keywords ?? ''));

        $ogImage = $this->pageSeo->resolveImageUrl($category->og_image)
            ?? $this->pageSeo->resolveImageUrl($category->image)
            ?? $this->globalSeo->defaultOgImageUrl();

        $ogTitle = Str::limit(trim((string) ($category->og_title ?: $category->name)), 70, '');
        $ogDescription = Str::limit(trim(strip_tags((string) ($category->og_description ?: $category->description ?: $metaDescription))), 200, '');

        return [
            'enabled' => true,
            'route' => 'frontend.blog.category',
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'meta_keywords' => $metaKeywords,
            'robots' => $this->resolveRobots($category, $page),
            'canonical' => $canonical,
            'og' => [
                'type' => 'website',
                'url' => $canonical,
                'title' => $ogTitle ?: $metaTitle,
                'description' => $ogDescription ?: $metaDescription,
                'image' => $ogImage,
                'locale' => 'ar_AR',
            ],
            'twitter' => [
                'card' => $this->globalSeo->twitterCardDefault(),
                'title' => $ogTitle ?: $metaTitle,
                'description' => Str::limit($ogDescription ?: $metaDescription, 200, ''),
                'image' => $ogImage,
                'site' => $this->globalSeo->twitterSite(),
            ],
            'schemas' => $this->buildSchemas($category, $canonical, $metaTitle, $metaDescription),
        ];
    }

    protected function resolveMetaTitle(BlogCategory $category, ?int $page): string
    {
        $title = trim((string) ($category->meta_title ?: $category->name));
        if ($page && $page > 1) {
            $template = $this->globalSeo->blogSettings()['paginated_title_template'] ?? 'صفحة {page}';
            $suffix = str_replace('{page}', (string) $page, $template);

            return Str::limit($title.' — '.$suffix, 70, '');
        }

        return Str::limit($title.' | المدونة | كلاودسوفت', 70, '');
    }

    protected function resolveMetaDescription(BlogCategory $category): string
    {
        $raw = $category->meta_description ?: $category->description ?: 'مقالات في تصنيف '.$category->name;

        return Str::limit(trim(strip_tags((string) $raw)), 160, '');
    }

    protected function resolveCanonical(BlogCategory $category, ?int $page): string
    {
        $canonical = trim((string) ($category->canonical_url ?: ''));
        if ($canonical !== '') {
            return str_starts_with($canonical, 'http') ? $canonical : url($canonical);
        }

        $url = route('frontend.blog.category', $category->slug);
        if ($page && $page > 1) {
            $url .= '?page='.$page;
        }

        return $url;
    }

    protected function resolveRobots(BlogCategory $category, ?int $page): string
    {
        if (! $category->is_indexable) {
            return 'noindex,nofollow';
        }

        if (! empty($category->robots_meta)) {
            $robots = (string) $category->robots_meta;
        } else {
            $robots = 'index,follow';
        }

        if ($page && $page > 1) {
            return $this->globalSeo->blogSettings()['paginated_robots'] ?? 'noindex,follow';
        }

        return $robots;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function buildSchemas(BlogCategory $category, string $canonical, string $title, string $description): array
    {
        $org = $this->globalSeo->organization();
        $baseUrl = rtrim((string) ($org['url'] ?? config('app.url')), '/');
        $logoUrl = $this->pageSeo->resolveImageUrl($org['logo'] ?? null) ?? $this->globalSeo->defaultOgImageUrl();

        return [
            [
                '@type' => 'Organization',
                '@id' => $baseUrl.'/#organization',
                'name' => $org['name'] ?? 'استضافة كلاودسوفت',
                'url' => $baseUrl,
                'logo' => ['@type' => 'ImageObject', 'url' => $logoUrl],
            ],
            [
                '@type' => 'CollectionPage',
                'name' => $title,
                'description' => $description,
                'url' => $canonical,
                'inLanguage' => 'ar',
            ],
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'الرئيسية', 'item' => $baseUrl.'/'],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'المدونة', 'item' => route('frontend.blog')],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $category->name, 'item' => $canonical],
                ],
            ],
        ];
    }
}
