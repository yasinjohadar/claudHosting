<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [];

        foreach (config('seo.pages', []) as $routeName => $page) {
            if (empty($page['sitemap'])) {
                continue;
            }
            if (! Route::has($routeName)) {
                continue;
            }

            if ($routeName === 'frontend.package-detail' || $routeName === 'frontend.package.order.form') {
                continue;
            }

            try {
                $loc = route($routeName);
            } catch (\Throwable) {
                continue;
            }

            $urls[] = [
                'loc' => $loc,
                'lastmod' => now()->toAtomString(),
                'changefreq' => $page['sitemap']['changefreq'] ?? 'monthly',
                'priority' => $page['sitemap']['priority'] ?? '0.5',
            ];
        }

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

        BlogPost::published()
            ->indexable()
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

        $xml = view('frontend.sitemap', ['urls' => $urls])->render();

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
