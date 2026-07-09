<?php

namespace App\View\Composers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Services\BlogCategorySeoService;
use App\Services\BlogPostSeoService;
use App\Services\BlogTagSeoService;
use App\Services\GlobalSeoService;
use App\Services\PageSeoService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class SeoComposer
{
    public function __construct(
        protected PageSeoService $pageSeo,
        protected BlogPostSeoService $blogSeo,
        protected BlogCategorySeoService $categorySeo,
        protected BlogTagSeoService $tagSeo
    ) {}

    public function compose(View $view): void
    {
        $routeName = request()->route()?->getName();
        $data = $view->getData();

        if ($routeName === 'frontend.blog.show') {
            $post = $data['post'] ?? null;
            if ($post instanceof BlogPost) {
                $view->with('seo', $this->blogSeo->resolve($post));

                return;
            }
        }

        if ($routeName === 'frontend.blog.category') {
            $category = $data['category'] ?? null;
            if ($category instanceof BlogCategory) {
                $page = $this->currentPageFromPaginator($data['posts'] ?? null);
                $seo = $this->categorySeo->resolve($category, $page);
                $view->with('seo', $this->appendPaginationLinks($seo, $data['posts'] ?? null));

                return;
            }
        }

        if ($routeName === 'frontend.blog.tag') {
            $tag = $data['tag'] ?? null;
            if ($tag instanceof BlogTag) {
                $page = $this->currentPageFromPaginator($data['posts'] ?? null);
                $seo = $this->tagSeo->resolve($tag, $page);
                $view->with('seo', $this->appendPaginationLinks($seo, $data['posts'] ?? null));

                return;
            }
        }

        if ($routeName === 'frontend.blog') {
            $page = $this->currentPageFromPaginator($data['posts'] ?? null);
            $seo = $this->pageSeo->resolve($routeName, array_merge($data, ['_blog_page' => $page]));
            if ($seo) {
                $view->with('seo', $this->appendPaginationLinks($seo, $data['posts'] ?? null));
            }

            return;
        }

        $seo = $this->pageSeo->resolve($routeName, $data);
        $view->with('seo', $seo);
    }

    protected function currentPageFromPaginator(mixed $paginator): ?int
    {
        if ($paginator instanceof LengthAwarePaginator) {
            return $paginator->currentPage();
        }

        $page = (int) request()->query('page', 1);

        return $page > 1 ? $page : null;
    }

    /**
     * @param  array<string, mixed>  $seo
     * @return array<string, mixed>
     */
    protected function appendPaginationLinks(array $seo, mixed $paginator): array
    {
        if (! app(GlobalSeoService::class)->blogSettings()['enable_prev_next'] ?? true) {
            return $seo;
        }

        if (! $paginator instanceof LengthAwarePaginator) {
            return $seo;
        }

        if ($paginator->previousPageUrl()) {
            $seo['prev_url'] = $paginator->previousPageUrl();
        }
        if ($paginator->nextPageUrl()) {
            $seo['next_url'] = $paginator->nextPageUrl();
        }

        return $seo;
    }
}
