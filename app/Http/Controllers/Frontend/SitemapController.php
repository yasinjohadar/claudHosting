<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\Product;
use App\Services\GlobalSeoService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

class SitemapController extends Controller
{
    public function __invoke(GlobalSeoService $globalSeo): Response
    {
        $urls = [];
        $staticLastmod = $globalSeo->settingsUpdatedAt() ?? now()->toAtomString();

        if ($globalSeo->isSitemapSectionEnabled('static_pages')) {
            foreach (config('seo.pages', []) as $routeName => $page) {
                if (empty($page['sitemap'])) {
                    continue;
                }
                if (! Route::has($routeName)) {
                    continue;
                }
                if (in_array($routeName, ['frontend.package-detail', 'frontend.package.order.form'], true)) {
                    continue;
                }

                try {
                    $loc = route($routeName);
                } catch (\Throwable) {
                    continue;
                }

                $urls[] = [
                    'loc' => $loc,
                    'lastmod' => $staticLastmod,
                    'changefreq' => $page['sitemap']['changefreq'] ?? 'monthly',
                    'priority' => $page['sitemap']['priority'] ?? '0.5',
                ];
            }
        }

        if ($globalSeo->isSitemapSectionEnabled('products')) {
            Product::query()
                ->where('hidden', false)
                ->where('status', 'Active')
                ->orderBy('updated_at', 'desc')
                ->get(['id', 'updated_at'])
                ->each(function (Product $product) use (&$urls) {
                    $urls[] = [
                        'loc' => route('frontend.package-detail', $product->id),
                        'lastmod' => $product->updated_at?->toAtomString() ?? now()->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.8',
                    ];
                });
        }

        if ($globalSeo->isSitemapSectionEnabled('blog_posts')) {
            BlogPost::published()
                ->indexable()
                ->where(function ($query) {
                    $query->whereNull('robots_meta')
                        ->orWhere('robots_meta', 'not like', '%noindex%');
                })
                ->orderByDesc('updated_at')
                ->get(['slug', 'updated_at'])
                ->each(function (BlogPost $post) use (&$urls) {
                    $urls[] = [
                        'loc' => route('frontend.blog.show', $post->slug),
                        'lastmod' => $post->updated_at?->toAtomString() ?? now()->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.7',
                    ];
                });
        }

        if ($globalSeo->isSitemapSectionEnabled('blog_categories')) {
            BlogCategory::query()
                ->active()
                ->where('is_indexable', true)
                ->where(function ($query) {
                    $query->whereNull('robots_meta')
                        ->orWhere('robots_meta', 'not like', '%noindex%');
                })
                ->orderByDesc('updated_at')
                ->get(['slug', 'updated_at'])
                ->each(function (BlogCategory $category) use (&$urls) {
                    if (! Route::has('frontend.blog.category')) {
                        return;
                    }
                    $urls[] = [
                        'loc' => route('frontend.blog.category', $category->slug),
                        'lastmod' => $category->updated_at?->toAtomString() ?? now()->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.65',
                    ];
                });
        }

        if ($globalSeo->isSitemapSectionEnabled('blog_tags')) {
            BlogTag::query()
                ->active()
                ->where('is_indexable', true)
                ->where(function ($query) {
                    $query->whereNull('robots_meta')
                        ->orWhere('robots_meta', 'not like', '%noindex%');
                })
                ->orderByDesc('updated_at')
                ->get(['slug', 'updated_at'])
                ->each(function (BlogTag $tag) use (&$urls) {
                    if (! Route::has('frontend.blog.tag')) {
                        return;
                    }
                    $urls[] = [
                        'loc' => route('frontend.blog.tag', $tag->slug),
                        'lastmod' => $tag->updated_at?->toAtomString() ?? now()->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.6',
                    ];
                });
        }

        $xml = view('frontend.sitemap', ['urls' => $urls])->render();

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
