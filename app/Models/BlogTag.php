<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class BlogTag extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'meta_title',
        'meta_description',
        'canonical_url',
        'is_active',
        'posts_count',
        'order',
        'is_indexable',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_indexable' => 'boolean',
        'posts_count' => 'integer',
        'order' => 'integer',
    ];

    // Relationships

    /**
     * Get all posts with this tag
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(BlogPost::class, 'blog_post_tag', 'blog_tag_id', 'blog_post_id')
                    ->withTimestamps();
    }

    /**
     * Get published posts with this tag
     */
    public function publishedPosts(): BelongsToMany
    {
        return $this->belongsToMany(BlogPost::class, 'blog_post_tag', 'blog_tag_id', 'blog_post_id')
                    ->where('status', 'published')
                    ->where('published_at', '<=', now())
                    ->orderBy('published_at', 'desc')
                    ->withTimestamps();
    }

    // Scopes

    /**
     * Scope to get only active tags
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get popular tags (with most posts)
     */
    public function scopePopular($query, $limit = 10)
    {
        return $query->where('posts_count', '>', 0)
                    ->orderBy('posts_count', 'desc')
                    ->limit($limit);
    }

    // Accessors

    /**
     * Get the tag's URL
     */
    public function getUrlAttribute(): string
    {
        return route('frontend.blog.tag', $this->slug);
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
        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }
}
