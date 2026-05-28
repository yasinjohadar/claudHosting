<?php

namespace App\View\Composers;

use App\Models\BlogPost;
use App\Services\BlogPostSeoService;
use App\Services\PageSeoService;
use Illuminate\View\View;

class SeoComposer
{
    public function __construct(
        protected PageSeoService $pageSeo,
        protected BlogPostSeoService $blogSeo
    ) {}

    public function compose(View $view): void
    {
        $routeName = request()->route()?->getName();

        if ($routeName === 'frontend.blog.show') {
            $post = $view->getData()['post'] ?? null;
            if ($post instanceof BlogPost) {
                $view->with('seo', $this->blogSeo->resolve($post));

                return;
            }
        }

        $seo = $this->pageSeo->resolve($routeName, $view->getData());

        $view->with('seo', $seo);
    }
}
