<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class BlogCategoryController extends Controller
{
    /**
     * Display a listing of categories.
     */
    public function index(Request $request)
    {
        $query = BlogCategory::withCount('posts');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by parent
        if ($request->filled('parent')) {
            if ($request->parent === 'root') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $request->parent);
            }
        }

        $categories = $query->orderBy('order', 'asc')->orderBy('name', 'asc')->paginate(20);
        $parentCategories = BlogCategory::whereNull('parent_id')->orderBy('name')->get();

        return view('admin.blog.categories.index', compact('categories', 'parentCategories'));
    }

    /**
     * Show the form for creating a new category.
     */
    public function create()
    {
        $parentCategories = BlogCategory::whereNull('parent_id')->orderBy('name')->get();
        return view('admin.blog.categories.create', compact('parentCategories'));
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:blog_categories,name',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:blog_categories,id',
            'icon' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:7',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',

            // SEO Fields
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
        ], [
            'name.unique' => 'اسم التصنيف موجود مسبقاً. يرجى اختيار اسم آخر.',
            'name.required' => 'اسم التصنيف مطلوب.',
            'name.max' => 'اسم التصنيف يجب ألا يتجاوز 255 حرفاً.',
        ]);

        try {
            // Generate slug
            $validated['slug'] = Str::slug($validated['name']);

            // Check for unique slug
            $counter = 1;
            $originalSlug = $validated['slug'];
            while (BlogCategory::where('slug', $validated['slug'])->exists()) {
                $validated['slug'] = $originalSlug . '-' . $counter++;
            }

            // Set default order if not provided
            if (!isset($validated['order'])) {
                $maxOrder = BlogCategory::max('order') ?? 0;
                $validated['order'] = $maxOrder + 1;
            }

            // Ensure is_active is always set (0 or 1)
            // If checkbox is checked, value is '1', otherwise it's not sent, so we set it to 0
            $validated['is_active'] = $request->has('is_active') && $request->is_active == '1' ? 1 : 0;

            // Create category
            BlogCategory::create($validated);

            return redirect()->route('admin.blog.categories.index')
                           ->with('success', 'تم إنشاء التصنيف بنجاح');

        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'حدث خطأ أثناء إنشاء التصنيف: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified category.
     */
    public function show(BlogCategory $category)
    {
        $category->load(['posts', 'parent', 'children']);
        return view('admin.blog.categories.show', compact('category'));
    }

    /**
     * Show the form for editing the specified category.
     */
    public function edit(BlogCategory $category)
    {
        $parentCategories = BlogCategory::whereNull('parent_id')
                                       ->where('id', '!=', $category->id)
                                       ->orderBy('name')
                                       ->get();

        return view('admin.blog.categories.edit', compact('category', 'parentCategories'));
    }

    /**
     * Update the specified category in storage.
     */
    public function update(Request $request, BlogCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:blog_categories,name,' . $category->id,
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:blog_categories,id',
            'icon' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:7',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',

            // SEO Fields
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
        ], [
            'name.unique' => 'اسم التصنيف موجود مسبقاً. يرجى اختيار اسم آخر.',
            'name.required' => 'اسم التصنيف مطلوب.',
            'name.max' => 'اسم التصنيف يجب ألا يتجاوز 255 حرفاً.',
        ]);

        // Ensure is_active is always set (0 or 1)
        // If checkbox is checked, value is '1', otherwise it's not sent, so we set it to 0
        $validated['is_active'] = $request->has('is_active') && $request->is_active == '1' ? 1 : 0;

        try {
            // Prevent category from being its own parent
            if (isset($validated['parent_id']) && $validated['parent_id'] == $category->id) {
                return back()->withInput()->with('error', 'لا يمكن للتصنيف أن يكون أباً لنفسه');
            }

            // Update slug if name changed
            if ($validated['name'] !== $category->name) {
                $validated['slug'] = Str::slug($validated['name']);

                // Check for unique slug
                $counter = 1;
                $originalSlug = $validated['slug'];
                while (BlogCategory::where('slug', $validated['slug'])->where('id', '!=', $category->id)->exists()) {
                    $validated['slug'] = $originalSlug . '-' . $counter++;
                }
            }

            // Update category
            $category->update($validated);

            return redirect()->route('admin.blog.categories.index')
                           ->with('success', 'تم تحديث التصنيف بنجاح');

        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'حدث خطأ أثناء تحديث التصنيف: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy(BlogCategory $category)
    {
        try {
            // Check if category has posts
            if ($category->posts()->count() > 0) {
                return back()->with('error', 'لا يمكن حذف التصنيف لأنه يحتوي على مقالات. يرجى نقل المقالات أولاً.');
            }

            // Check if category has children
            if ($category->children()->count() > 0) {
                return back()->with('error', 'لا يمكن حذف التصنيف لأنه يحتوي على تصنيفات فرعية. يرجى حذفها أولاً.');
            }

            $category->delete();

            return redirect()->route('admin.blog.categories.index')
                           ->with('success', 'تم حذف التصنيف بنجاح');

        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ أثناء حذف التصنيف: ' . $e->getMessage());
        }
    }

    /**
     * Toggle category active status
     */
    public function toggleActive(BlogCategory $category)
    {
        $category->is_active = !$category->is_active;
        $category->save();

        return back()->with('success', 'تم تحديث حالة التصنيف');
    }

    /**
     * Reorder categories
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'orders' => 'required|array',
            'orders.*' => 'required|integer|exists:blog_categories,id',
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->orders as $order => $categoryId) {
                BlogCategory::where('id', $categoryId)->update(['order' => $order + 1]);
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'تم تحديث الترتيب بنجاح']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء تحديث الترتيب'], 500);
        }
    }
}
