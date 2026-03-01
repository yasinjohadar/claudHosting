<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    /**
     * عرض قائمة التدوينات (صفحة المدونة)
     */
    public function index(Request $request)
    {
        $posts = BlogPost::published()
            ->with(['category', 'tags', 'author'])
            ->latest('published_at')
            ->paginate(12);

        return view('frontend.pages.blog', compact('posts'));
    }

    /**
     * عرض تدوينة واحدة بالـ slug
     */
    public function show(string $slug)
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
