<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogTagController extends Controller
{
    /**
     * Display a listing of tags.
     */
    public function index(Request $request)
    {
        $query = BlogTag::withCount('posts');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('sort') && $request->sort === 'popular') {
            $query->orderBy('posts_count', 'desc');
        } else {
            $query->orderBy('name', 'asc');
        }

        $tags = $query->paginate(20);

        return view('admin.blog.tags.index', compact('tags'));
    }

    /**
     * Show the form for creating a new tag.
     */
    public function create()
    {
        return view('admin.blog.tags.create');
    }

    /**
     * Store a newly created tag in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate(array_merge([
            'name' => 'required|string|max:100|unique:blog_tags,name',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
        ], $this->seoValidationRules()));

        try {
            $validated['slug'] = Str::slug($validated['name']);

            $counter = 1;
            $originalSlug = $validated['slug'];
            while (BlogTag::where('slug', $validated['slug'])->exists()) {
                $validated['slug'] = $originalSlug.'-'.$counter++;
            }

            $validated['posts_count'] = 0;
            $validated = $this->prepareSeoPayload($request, $validated);

            BlogTag::create($validated);

            return redirect()->route('admin.blog.tags.index')
                           ->with('success', 'تم إنشاء الوسم بنجاح');

        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'حدث خطأ أثناء إنشاء الوسم: '.$e->getMessage());
        }
    }

    /**
     * Display the specified tag.
     */
    public function show(BlogTag $tag)
    {
        $tag->load('posts');

        return view('admin.blog.tags.show', compact('tag'));
    }

    /**
     * Show the form for editing the specified tag.
     */
    public function edit(BlogTag $tag)
    {
        return view('admin.blog.tags.edit', compact('tag'));
    }

    /**
     * Update the specified tag in storage.
     */
    public function update(Request $request, BlogTag $tag)
    {
        $validated = $request->validate(array_merge([
            'name' => 'required|string|max:100|unique:blog_tags,name,'.$tag->id,
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
        ], $this->seoValidationRules()));

        try {
            if ($validated['name'] !== $tag->name) {
                $validated['slug'] = Str::slug($validated['name']);

                $counter = 1;
                $originalSlug = $validated['slug'];
                while (BlogTag::where('slug', $validated['slug'])->where('id', '!=', $tag->id)->exists()) {
                    $validated['slug'] = $originalSlug.'-'.$counter++;
                }
            }

            $validated = $this->prepareSeoPayload($request, $validated, $tag);
            $tag->update($validated);

            return redirect()->route('admin.blog.tags.index')
                           ->with('success', 'تم تحديث الوسم بنجاح');

        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'حدث خطأ أثناء تحديث الوسم: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified tag from storage.
     */
    public function destroy(BlogTag $tag)
    {
        try {
            $tag->posts()->detach();
            $this->deleteOgImage($tag->og_image);
            $tag->delete();

            return redirect()->route('admin.blog.tags.index')
                           ->with('success', 'تم حذف الوسم بنجاح');

        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ أثناء حذف الوسم: '.$e->getMessage());
        }
    }

    /**
     * Update posts count for all tags
     */
    public function updatePostsCount()
    {
        try {
            $tags = BlogTag::all();
            foreach ($tags as $tag) {
                $tag->posts_count = $tag->posts()->count();
                $tag->save();
            }

            return back()->with('success', 'تم تحديث عدد المقالات لجميع الوسوم');

        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ أثناء التحديث: '.$e->getMessage());
        }
    }

    /**
     * @return array<string, string>
     */
    protected function seoValidationRules(): array
    {
        return [
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'canonical_url' => 'nullable|url|max:500',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string',
            'og_image' => 'nullable|image|mimes:webp,jpg,jpeg,png|max:4096',
            'robots_meta' => 'nullable|in:index,follow,noindex,follow,index,nofollow,noindex,nofollow',
            'is_indexable' => 'boolean',
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function prepareSeoPayload(Request $request, array $validated, ?BlogTag $tag = null): array
    {
        $validated['is_indexable'] = $request->boolean('is_indexable', true);

        if ($request->hasFile('og_image')) {
            $this->deleteOgImage($tag?->og_image);
            $validated['og_image'] = $request->file('og_image')->store('blog/seo', 'public');
        } else {
            unset($validated['og_image']);
        }

        return $validated;
    }

    protected function deleteOgImage(?string $path): void
    {
        if (empty($path) || filter_var($path, FILTER_VALIDATE_URL)) {
            return;
        }

        $path = ltrim(str_replace('storage/', '', $path), '/');
        Storage::disk('public')->delete($path);
    }
}
