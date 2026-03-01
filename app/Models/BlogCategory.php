<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BlogCategory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'image',
        'parent_id',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
        'og_title',
        'og_description',
        'og_image',
        'schema_data',
        'is_active',
        'is_featured',
        'order',
        'posts_count',
        'is_indexable',
        'robots_meta',
    ];

    protected $casts = [
        'schema_data' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_indexable' => 'boolean',
        'order' => 'integer',
        'posts_count' => 'integer',
    ];

    // Relationships

    /**
     * Get the parent category
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'parent_id');
    }

    /**
     * Get child categories
     */
    public function children(): HasMany
    {
        return $this->hasMany(BlogCategory::class, 'parent_id')
                    ->orderBy('order');
    }

    /**
     * Get all posts in this category
     */
    public function posts(): HasMany
    {
        return $this->hasMany(BlogPost::class, 'blog_category_id');
    }

    /**
     * Get published posts in this category
     */
    public function publishedPosts(): HasMany
    {
        return $this->hasMany(BlogPost::class, 'blog_category_id')
                    ->where('status', 'published')
                    ->where('published_at', '<=', now())
                    ->orderBy('published_at', 'desc');
    }

    // Scopes

    /**
     * Scope to get only active categories
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get featured categories
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to get parent categories only
     */
    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    // Accessors

    /**
     * Get the category's URL
     */
    public function getUrlAttribute(): string
    {
        return route('frontend.blog.category', $this->slug);
    }

    /**
     * Generate slug automatically from name
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;

        if (empty($this->attributes['slug'])) {
            $this->attributes['slug'] = Str::slug($value);
        }
    }

    /**
     * Update posts count
     */
    public function updatePostsCount(): void
    {
        $this->posts_count = $this->publishedPosts()->count();
        $this->save();
    }

    // Boot method

    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug if not provided
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }
}
