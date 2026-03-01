<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogTag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogTagController extends Controller
{
    /**
     * Display a listing of tags.
     */
    public function index(Request $request)
    {
        $query = BlogTag::withCount('posts');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sort by posts count
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
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:blog_tags,name',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',

            // SEO Fields
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
        ], [
            'name.unique' => 'اسم الوسم موجود مسبقاً. يرجى اختيار اسم آخر.',
            'name.required' => 'اسم الوسم مطلوب.',
            'name.max' => 'اسم الوسم يجب ألا يتجاوز 100 حرف.',
        ]);

        try {
            // Generate slug
            $validated['slug'] = Str::slug($validated['name']);

            // Check for unique slug
            $counter = 1;
            $originalSlug = $validated['slug'];
            while (BlogTag::where('slug', $validated['slug'])->exists()) {
                $validated['slug'] = $originalSlug . '-' . $counter++;
            }

            // Initialize posts count
            $validated['posts_count'] = 0;

            // Create tag
            BlogTag::create($validated);

            return redirect()->route('admin.blog.tags.index')
                           ->with('success', 'تم إنشاء الوسم بنجاح');

        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'حدث خطأ أثناء إنشاء الوسم: ' . $e->getMessage());
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
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:blog_tags,name,' . $tag->id,
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',

            // SEO Fields
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
        ], [
            'name.unique' => 'اسم الوسم موجود مسبقاً. يرجى اختيار اسم آخر.',
            'name.required' => 'اسم الوسم مطلوب.',
            'name.max' => 'اسم الوسم يجب ألا يتجاوز 100 حرف.',
        ]);

        try {
            // Update slug if name changed
            if ($validated['name'] !== $tag->name) {
                $validated['slug'] = Str::slug($validated['name']);

                // Check for unique slug
                $counter = 1;
                $originalSlug = $validated['slug'];
                while (BlogTag::where('slug', $validated['slug'])->where('id', '!=', $tag->id)->exists()) {
                    $validated['slug'] = $originalSlug . '-' . $counter++;
                }
            }

            // Update tag
            $tag->update($validated);

            return redirect()->route('admin.blog.tags.index')
                           ->with('success', 'تم تحديث الوسم بنجاح');

        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'حدث خطأ أثناء تحديث الوسم: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified tag from storage.
     */
    public function destroy(BlogTag $tag)
    {
        try {
            // Detach all posts before deleting
            $tag->posts()->detach();

            // Delete tag
            $tag->delete();

            return redirect()->route('admin.blog.tags.index')
                           ->with('success', 'تم حذف الوسم بنجاح');

        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ أثناء حذف الوسم: ' . $e->getMessage());
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
            return back()->with('error', 'حدث خطأ أثناء التحديث: ' . $e->getMessage());
        }
    }
}
