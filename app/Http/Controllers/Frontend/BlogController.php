<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->filled('category')) {
            return redirect()->route('frontend.blog.category', $request->string('category')->toString(), 301);
        }

        if ($request->filled('tag')) {
            return redirect()->route('frontend.blog.tag', $request->string('tag')->toString(), 301);
        }

        $posts = BlogPost::published()
            ->with(['category', 'tags', 'author'])
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        return view('frontend.pages.blog', compact('posts'));
    }

    public function category(string $slug): View
    {
        $category = BlogCategory::query()
            ->where('slug', $slug)
            ->active()
            ->firstOrFail();

        $posts = BlogPost::published()
            ->where('blog_category_id', $category->id)
            ->with(['category', 'tags', 'author'])
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        return view('frontend.pages.blog-category', compact('category', 'posts'));
    }

    public function tag(string $slug): View
    {
        $tag = BlogTag::query()
            ->where('slug', $slug)
            ->active()
            ->firstOrFail();

        $posts = $tag->publishedPosts()
            ->with(['category', 'tags', 'author'])
            ->paginate(12)
            ->withQueryString();

        return view('frontend.pages.blog-tag', compact('tag', 'posts'));
    }

    public function show(string $slug): View
    {
        $post = BlogPost::where('slug', $slug)
            ->published()
            ->with(['category', 'tags', 'author'])
            ->firstOrFail();

        $recentPosts = BlogPost::published()
            ->where('id', '!=', $post->id)
            ->with('category')
            ->latest('published_at')
            ->take(5)
            ->get();

        return view('frontend.pages.blog-detail', compact('post', 'recentPosts'));
    }
}
