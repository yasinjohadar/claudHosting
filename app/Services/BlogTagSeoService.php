<?php

namespace App\Services;

use App\Models\BlogTag;
use Illuminate\Support\Str;

class BlogTagSeoService
{
    public function __construct(
        protected PageSeoService $pageSeo,
        protected GlobalSeoService $globalSeo
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function resolve(BlogTag $tag, ?int $page = null): array
    {
        $canonical = $this->resolveCanonical($tag, $page);
        $metaTitle = $this->resolveMetaTitle($tag, $page);
        $metaDescription = $this->resolveMetaDescription($tag);

        $ogImage = $this->pageSeo->resolveImageUrl($tag->og_image)
            ?? $this->globalSeo->defaultOgImageUrl();

        $ogTitle = Str::limit(trim((string) ($tag->og_title ?: $tag->name)), 70, '');
        $ogDescription = Str::limit(trim(strip_tags((string) ($tag->og_description ?: $tag->description ?: $metaDescription))), 200, '');

        return [
            'enabled' => true,
            'route' => 'frontend.blog.tag',
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'meta_keywords' => $tag->name,
            'robots' => $this->resolveRobots($tag, $page),
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
            'schemas' => $this->buildSchemas($tag, $canonical, $metaTitle, $metaDescription),
        ];
    }

    protected function resolveMetaTitle(BlogTag $tag, ?int $page): string
    {
        $title = trim((string) ($tag->meta_title ?: $tag->name));
        if ($page && $page > 1) {
            $template = $this->globalSeo->blogSettings()['paginated_title_template'] ?? 'صفحة {page}';
            $suffix = str_replace('{page}', (string) $page, $template);

            return Str::limit($title.' — '.$suffix, 70, '');
        }

        return Str::limit($title.' | المدونة | كلاودسوفت', 70, '');
    }

    protected function resolveMetaDescription(BlogTag $tag): string
    {
        $raw = $tag->meta_description ?: $tag->description ?: 'مقالات موسومة بـ '.$tag->name;

        return Str::limit(trim(strip_tags((string) $raw)), 160, '');
    }

    protected function resolveCanonical(BlogTag $tag, ?int $page): string
    {
        $canonical = trim((string) ($tag->canonical_url ?: ''));
        if ($canonical !== '') {
            return str_starts_with($canonical, 'http') ? $canonical : url($canonical);
        }

        $url = route('frontend.blog.tag', $tag->slug);
        if ($page && $page > 1) {
            $url .= '?page='.$page;
        }

        return $url;
    }

    protected function resolveRobots(BlogTag $tag, ?int $page): string
    {
        if (! $tag->is_indexable) {
            return 'noindex,nofollow';
        }

        $robots = ! empty($tag->robots_meta) ? (string) $tag->robots_meta : 'index,follow';

        if ($page && $page > 1) {
            return $this->globalSeo->blogSettings()['paginated_robots'] ?? 'noindex,follow';
        }

        return $robots;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function buildSchemas(BlogTag $tag, string $canonical, string $title, string $description): array
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
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $tag->name, 'item' => $canonical],
                ],
            ],
        ];
    }
}
