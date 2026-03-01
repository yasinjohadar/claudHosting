<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    use SoftDeletes;

    protected $fillable = [
        // Basic Information
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'featured_image_alt',

        // Author & Category
        'author_id',
        'blog_category_id',

        // SEO Fields - Basic
        'meta_title',
        'meta_description',
        'meta_keywords',

        // SEO Fields - Advanced
        'canonical_url',
        'focus_keyword',
        'focus_keyword_synonyms',

        // Open Graph
        'og_title',
        'og_description',
        'og_image',
        'og_type',
        'og_locale',

        // Twitter Card
        'twitter_card',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'twitter_creator',

        // Schema.org
        'schema_type',
        'schema_headline',
        'schema_description',
        'schema_image',
        'schema_published_time',
        'schema_modified_time',
        'schema_author_name',
        'schema_author_url',
        'breadcrumb_schema',

        // Reading & Engagement
        'reading_time',
        'views_count',
        'shares_count',
        'comments_count',

        // Publishing
        'status',
        'published_at',
        'scheduled_at',

        // SEO Settings
        'is_indexable',
        'is_followable',
        'robots_meta',

        // Featured & Priority
        'is_featured',
        'priority',
        'order',

        // Content Quality Scores
        'seo_score',
        'readability_score',
        'keyword_density',

        // Related Content
        'related_posts',

        // Analytics
        'utm_source',
        'utm_medium',
        'utm_campaign',

        // Additional Metadata
        'custom_meta',

        // Language
        'language',
        'translation_group_id',
    ];

    protected $casts = [
        'breadcrumb_schema' => 'array',
        'related_posts' => 'array',
        'custom_meta' => 'array',
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'schema_published_time' => 'datetime',
        'schema_modified_time' => 'datetime',
        'is_indexable' => 'boolean',
        'is_followable' => 'boolean',
        'is_featured' => 'boolean',
        'views_count' => 'integer',
        'shares_count' => 'integer',
        'comments_count' => 'integer',
        'reading_time' => 'integer',
        'seo_score' => 'integer',
        'readability_score' => 'integer',
        'keyword_density' => 'integer',
        'priority' => 'integer',
        'order' => 'integer',
    ];

    // Relationships

    /**
     * Get the author of the blog post
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get the category of the blog post
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    /**
     * Get the tags for the blog post
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class, 'blog_post_tag', 'blog_post_id', 'blog_tag_id')
                    ->withTimestamps();
    }

    // Scopes

    /**
     * Scope to get only published posts
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                    ->where('published_at', '<=', now());
    }

    /**
     * Scope to get featured posts
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to get indexable posts for SEO
     */
    public function scopeIndexable($query)
    {
        return $query->where('is_indexable', true);
    }

    /**
     * Scope to search by keyword
     */
    public function scopeSearch($query, $keyword)
    {
        return $query->where(function($q) use ($keyword) {
            $q->where('title', 'like', "%{$keyword}%")
              ->orWhere('excerpt', 'like', "%{$keyword}%")
              ->orWhere('content', 'like', "%{$keyword}%")
              ->orWhere('meta_keywords', 'like', "%{$keyword}%");
        });
    }

    // Accessors & Mutators

    /**
     * Get the post's URL
     */
    public function getUrlAttribute(): string
    {
        return route('frontend.blog.show', $this->slug);
    }

    /**
     * Get reading time in human readable format
     */
    public function getReadingTimeTextAttribute(): string
    {
        return $this->reading_time ? "{$this->reading_time} دقائق" : 'غير محدد';
    }

    /**
     * Get formatted published date
     */
    public function getPublishedDateAttribute(): string
    {
        return $this->published_at ? $this->published_at->format('Y-m-d') : '';
    }

    /**
     * Generate slug automatically from title
     */
    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = $value;

        if (empty($this->attributes['slug'])) {
            $this->attributes['slug'] = Str::slug($value);
        }
    }

    /**
     * Calculate reading time based on content
     */
    public function calculateReadingTime(): int
    {
        $wordCount = str_word_count(strip_tags($this->content));
        $readingTime = ceil($wordCount / 200); // Average reading speed: 200 words per minute

        $this->reading_time = $readingTime;
        $this->save();

        return $readingTime;
    }

    /**
     * Increment views count
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Generate SEO meta tags as array
     */
    public function getSeoMetaTags(): array
    {
        return [
            'title' => $this->meta_title ?: $this->title,
            'description' => $this->meta_description ?: $this->excerpt,
            'keywords' => $this->meta_keywords,
            'canonical' => $this->canonical_url ?: $this->url,
            'og:title' => $this->og_title ?: $this->title,
            'og:description' => $this->og_description ?: $this->excerpt,
            'og:image' => $this->og_image ?: $this->featured_image,
            'og:type' => $this->og_type,
            'og:locale' => $this->og_locale,
            'twitter:card' => $this->twitter_card,
            'twitter:title' => $this->twitter_title ?: $this->title,
            'twitter:description' => $this->twitter_description ?: $this->excerpt,
            'twitter:image' => $this->twitter_image ?: $this->featured_image,
            'robots' => $this->robots_meta,
        ];
    }

    /**
     * Generate Schema.org JSON-LD
     */
    public function getSchemaJsonLd(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => $this->schema_type,
            'headline' => $this->schema_headline ?: $this->title,
            'description' => $this->schema_description ?: $this->excerpt,
            'image' => $this->schema_image ?: $this->featured_image,
            'datePublished' => $this->schema_published_time ?: $this->published_at,
            'dateModified' => $this->schema_modified_time ?: $this->updated_at,
            'author' => [
                '@type' => 'Person',
                'name' => $this->schema_author_name ?: $this->author?->name,
                'url' => $this->schema_author_url,
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => asset('frontend/assets/images/logo.png'),
                ],
            ],
        ];
    }

    // Boot method for model events

    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug if not provided
        static::creating(function ($post) {
            if (empty($post->slug)) {
                $slug = Str::slug($post->title, '-', 'ar');
                
                // If slug is empty after conversion, use a fallback
                if (empty($slug)) {
                    $slug = 'post-' . time();
                }
                
                // Check for unique slug
                $counter = 1;
                $originalSlug = $slug;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $originalSlug . '-' . $counter++;
                }
                
                $post->slug = $slug;
            }

            // Set schema times
            if (empty($post->schema_published_time) && $post->published_at) {
                $post->schema_published_time = $post->published_at;
            }
        });

        // Update schema modified time
        static::updating(function ($post) {
            $post->schema_modified_time = now();
        });
    }
}
