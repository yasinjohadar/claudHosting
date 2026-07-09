<?php

namespace App\Services;

use App\Models\BlogPost;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlogPostSeoService
{
    protected const BRAND_SUFFIX = ' | استضافة كلاودسوفت';

    /**
     * @return array<string, mixed>
     */
    public function resolve(BlogPost $post): array
    {
        $this->ensureRelations($post);

        $canonical = $this->resolveCanonical($post);
        $metaTitle = $this->resolveMetaTitle($post);
        $metaDescription = $this->resolveMetaDescription($post);
        $metaKeywords = $this->resolveKeywords($post);

        $ogImage = $this->resolveImageUrl($post->og_image)
            ?? $this->resolveImageUrl($post->schema_image)
            ?? $this->resolveImageUrl($post->featured_image)
            ?? $this->defaultOgImage();

        $twitterImage = $this->resolveImageUrl($post->twitter_image) ?? $ogImage;

        $ogTitle = Str::limit(trim((string) ($post->og_title ?: $post->title)), 70, '');
        $ogDescription = Str::limit(trim(strip_tags((string) ($post->og_description ?: $post->excerpt ?: $metaDescription))), 200, '');

        $twitterTitle = Str::limit(trim((string) ($post->twitter_title ?: $ogTitle)), 70, '');
        $twitterDescription = Str::limit(trim(strip_tags((string) ($post->twitter_description ?: $ogDescription))), 200, '');

        $published = $post->schema_published_time ?? $post->published_at;
        $modified = $post->schema_modified_time ?? $post->updated_at;

        return [
            'enabled' => true,
            'route' => 'frontend.blog.show',
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'meta_keywords' => $metaKeywords,
            'robots' => $this->resolveRobots($post),
            'canonical' => $canonical,
            'og' => [
                'type' => $post->og_type ?: 'article',
                'url' => $canonical,
                'title' => $ogTitle,
                'description' => $ogDescription,
                'image' => $ogImage,
                'locale' => $post->og_locale ?: 'ar_AR',
            ],
            'twitter' => [
                'card' => $post->twitter_card ?: app(GlobalSeoService::class)->twitterCardDefault(),
                'title' => $twitterTitle,
                'description' => $twitterDescription,
                'image' => $twitterImage,
                'creator' => $post->twitter_creator,
                'site' => app(GlobalSeoService::class)->twitterSite(),
            ],
            'article' => [
                'published_time' => $published?->toIso8601String(),
                'modified_time' => $modified?->toIso8601String(),
                'section' => $post->category?->name,
                'tags' => $post->tags->pluck('name')->filter()->values()->all(),
                'author' => $post->author?->name,
            ],
            'schemas' => $this->buildSchemas($post, $canonical, $metaTitle, $metaDescription, $ogImage, $published, $modified),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function applyDefaultsOnSave(array $data, ?BlogPost $existing = null): array
    {
        $title = (string) ($data['title'] ?? $existing?->title ?? '');
        $excerpt = (string) ($data['excerpt'] ?? $existing?->excerpt ?? '');
        $authorName = $existing?->author?->name ?? auth()->user()?->name;

        $fills = [
            'meta_title' => $title,
            'meta_description' => $excerpt,
            'og_title' => $title,
            'og_description' => $excerpt,
            'twitter_title' => $title,
            'twitter_description' => $excerpt,
            'schema_headline' => $title,
            'schema_description' => $excerpt,
            'schema_author_name' => $authorName,
        ];

        foreach ($fills as $field => $fallback) {
            if (empty(trim((string) ($data[$field] ?? ''))) && $fallback !== '') {
                $data[$field] = $fallback;
            }
        }

        if (empty($data['canonical_url'] ?? null) && ! empty($data['slug'] ?? $existing?->slug)) {
            $slug = $data['slug'] ?? $existing->slug;
            $data['canonical_url'] = route('frontend.blog.show', $slug);
        }

        if (empty($data['meta_keywords'] ?? '') && ! empty($data['tags'] ?? null) && is_array($data['tags'])) {
            $names = \App\Models\BlogTag::whereIn('id', $data['tags'])->pluck('name')->implode(', ');
            if ($names !== '') {
                $data['meta_keywords'] = $names;
            }
        }

        $index = filter_var($data['is_indexable'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $follow = filter_var($data['is_followable'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $data['robots_meta'] = $this->robotsMetaFromFlags($index, $follow);

        if (($data['status'] ?? $existing?->status) !== 'published') {
            $data['robots_meta'] = 'noindex,nofollow';
        }

        if (empty($data['schema_type'] ?? '')) {
            $data['schema_type'] = 'BlogPosting';
        }

        if (empty($data['og_type'] ?? '')) {
            $data['og_type'] = 'article';
        }

        if (empty($data['og_locale'] ?? '')) {
            $data['og_locale'] = 'ar_AR';
        }

        if (empty($data['twitter_card'] ?? '')) {
            $data['twitter_card'] = 'summary_large_image';
        }

        $publishedAt = $data['published_at'] ?? $existing?->published_at;
        if (empty($data['schema_published_time'] ?? null) && $publishedAt) {
            $data['schema_published_time'] = $publishedAt;
        }

        $data['schema_modified_time'] = now();

        return $data;
    }

    public function robotsMetaFromFlags(bool $indexable, bool $followable): string
    {
        $index = $indexable ? 'index' : 'noindex';
        $follow = $followable ? 'follow' : 'nofollow';

        return "{$index},{$follow}";
    }

    /**
     * @return array<string, string>
     */
    public function seoValidationRules(): array
    {
        return [
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:1000',
            'canonical_url' => 'nullable|url|max:500',
            'focus_keyword' => 'nullable|string|max:255',
            'focus_keyword_synonyms' => 'nullable|string|max:500',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string|max:500',
            'og_image' => 'nullable|image|max:2048',
            'og_type' => 'nullable|in:article,website,blog',
            'og_locale' => 'nullable|string|max:10',
            'twitter_card' => 'nullable|in:summary,summary_large_image,app,player',
            'twitter_title' => 'nullable|string|max:255',
            'twitter_description' => 'nullable|string|max:500',
            'twitter_image' => 'nullable|image|max:2048',
            'twitter_creator' => 'nullable|string|max:100',
            'schema_type' => 'nullable|in:Article,BlogPosting,NewsArticle,TechArticle',
            'schema_headline' => 'nullable|string|max:500',
            'schema_description' => 'nullable|string|max:1000',
            'schema_image' => 'nullable|image|max:2048',
            'schema_published_time' => 'nullable|date',
            'schema_modified_time' => 'nullable|date',
            'schema_author_name' => 'nullable|string|max:255',
            'schema_author_url' => 'nullable|url|max:500',
            'robots_meta' => ['nullable', Rule::in(['index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow'])],
            'is_indexable' => 'boolean',
            'is_followable' => 'boolean',
        ];
    }

    protected function ensureRelations(BlogPost $post): void
    {
        if (! $post->exists) {
            if (! $post->relationLoaded('tags')) {
                $post->setRelation('tags', collect());
            }
            if (! $post->relationLoaded('category')) {
                $post->setRelation('category', null);
            }
            if (! $post->relationLoaded('author')) {
                $post->setRelation('author', null);
            }

            return;
        }

        $post->loadMissing(['author', 'category', 'tags']);
    }

    protected function resolveMetaTitle(BlogPost $post): string
    {
        $base = trim((string) ($post->meta_title ?: $post->title));
        if ($base === '') {
            $base = 'مقال';
        }

        $withBrand = $base;
        if (! str_contains($withBrand, 'كلاودسوفت') && ! str_contains($withBrand, 'CloudSoft')) {
            $withBrand .= self::BRAND_SUFFIX;
        }

        return Str::limit($withBrand, 70, '');
    }

    protected function resolveMetaDescription(BlogPost $post): string
    {
        $raw = $post->meta_description ?: $post->excerpt ?: '';

        return Str::limit(trim(strip_tags((string) $raw)), 160, '');
    }

    protected function resolveKeywords(BlogPost $post): string
    {
        if (! empty(trim((string) $post->meta_keywords))) {
            return trim((string) $post->meta_keywords);
        }

        $fromTags = $post->tags->pluck('name')->filter()->implode(', ');
        if ($fromTags !== '') {
            return $fromTags;
        }

        if ($post->focus_keyword) {
            return (string) $post->focus_keyword;
        }

        return '';
    }

    protected function resolveCanonical(BlogPost $post): string
    {
        $canonical = trim((string) ($post->canonical_url ?: ''));
        if ($canonical === '') {
            return route('frontend.blog.show', $post->slug);
        }

        if (! str_starts_with($canonical, 'http')) {
            return url($canonical);
        }

        return $canonical;
    }

    protected function resolveRobots(BlogPost $post): string
    {
        if ($post->status !== 'published') {
            return 'noindex,nofollow';
        }

        if (! empty($post->robots_meta)) {
            return (string) $post->robots_meta;
        }

        $index = $post->is_indexable !== false;
        $follow = $post->is_followable !== false;

        return $this->robotsMetaFromFlags($index, $follow);
    }

    protected function resolveImageUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (function_exists('blog_image_url')) {
            return blog_image_url($path);
        }

        return asset('storage/'.ltrim($path, '/'));
    }

    protected function defaultOgImage(): string
    {
        return app(GlobalSeoService::class)->defaultOgImageUrl();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function buildSchemas(
        BlogPost $post,
        string $canonical,
        string $metaTitle,
        string $metaDescription,
        string $ogImage,
        $published,
        $modified
    ): array {
        $org = app(GlobalSeoService::class)->organization();
        $orgUrl = rtrim((string) ($org['url'] ?? config('app.url', url('/'))), '/');
        $logoUrl = app(PageSeoService::class)->resolveImageUrl($org['logo'] ?? null)
            ?? app(GlobalSeoService::class)->defaultOgImageUrl();

        $authorName = trim((string) ($post->schema_author_name ?: $post->author?->name ?: 'كلاودسوفت'));
        $authorUrl = $post->schema_author_url ?: ($post->author ? $orgUrl : null);

        $schemaType = $post->schema_type ?: 'BlogPosting';
        $headline = Str::limit(strip_tags((string) ($post->schema_headline ?: $post->title)), 110, '');
        $description = Str::limit(strip_tags((string) ($post->schema_description ?: $post->excerpt ?: $metaDescription)), 500, '');

        $article = [
            '@type' => $schemaType,
            'headline' => $headline,
            'description' => $description,
            'image' => [$ogImage],
            'url' => $canonical,
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $canonical,
            ],
            'datePublished' => ($published ?? now())->format('c'),
            'dateModified' => ($modified ?? now())->format('c'),
            'author' => array_filter([
                '@type' => 'Person',
                'name' => $authorName,
                'url' => $authorUrl,
            ]),
            'publisher' => [
                '@type' => 'Organization',
                'name' => $org['name'] ?? config('app.name'),
                'url' => $orgUrl,
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $logoUrl,
                ],
            ],
            'inLanguage' => $post->language ?: 'ar',
        ];

        if ($post->category?->name) {
            $article['articleSection'] = $post->category->name;
        }

        $keywords = $this->resolveKeywords($post);
        if ($keywords !== '') {
            $article['keywords'] = $keywords;
        }

        if ($post->reading_time) {
            $article['timeRequired'] = 'PT'.$post->reading_time.'M';
        }

        $schemas = [$article];

        $breadcrumb = $this->buildBreadcrumbSchema($post, $canonical);
        if ($breadcrumb !== null) {
            $schemas[] = $breadcrumb;
        }

        return $schemas;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function buildBreadcrumbSchema(BlogPost $post, string $canonical): ?array
    {
        if (is_array($post->breadcrumb_schema) && ! empty($post->breadcrumb_schema['itemListElement'])) {
            return $post->breadcrumb_schema;
        }

        $orgUrl = rtrim((string) (app(GlobalSeoService::class)->organization()['url'] ?? config('app.url', url('/'))), '/');
        $items = [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'الرئيسية',
                'item' => $orgUrl.'/',
            ],
        ];

        if (Route::has('frontend.blog')) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => 'المدونة',
                'item' => route('frontend.blog'),
            ];
        }

        if ($post->category?->name && Route::has('frontend.blog.category') && $post->category?->slug) {
            $categoryUrl = route('frontend.blog.category', $post->category->slug);
        } elseif ($post->category?->name && Route::has('frontend.blog')) {
            $categoryUrl = route('frontend.blog');
        } else {
            $categoryUrl = null;
        }

        if ($categoryUrl) {
            $position = count($items) + 1;
            $items[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $post->category->name,
                'item' => $categoryUrl,
            ];
        } else {
            $position = count($items) + 1;
        }

        $items[] = [
            '@type' => 'ListItem',
            'position' => $position,
            'name' => Str::limit($post->title, 60, ''),
            'item' => $canonical,
        ];

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }
}
