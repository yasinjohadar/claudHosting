@php
    $delay = $delay ?? (($index ?? 0) % 4) + 1;
    $excerptLimit = $excerptLimit ?? 100;
@endphp

<a href="{{ route('frontend.blog.show', $post->slug) }}"
    class="blog-card glass-panel animate-on-scroll animate-delay-{{ $delay }}">
    <div class="blog-card__media">
        @if ($post->featured_image && function_exists('blog_image_url'))
            <img src="{{ blog_image_url($post->featured_image) }}"
                alt="{{ $post->featured_image_alt ?? $post->title }}"
                width="400" height="200" loading="lazy" decoding="async">
        @else
            <img src="{{ asset('frontend/assets/images/course-webdev.svg') }}"
                alt="{{ $post->title }}"
                width="400" height="200" loading="lazy" decoding="async">
        @endif
        <span class="blog-card__media-shade" aria-hidden="true"></span>
        @if ($post->category)
            <span class="blog-card__category">{{ $post->category->name }}</span>
        @endif
    </div>
    <div class="blog-card__body">
        <div class="blog-card__meta">
            <span class="blog-card__meta-item">
                <i class="far fa-calendar-alt" aria-hidden="true"></i>
                {{ $post->published_at?->translatedFormat('d F Y') ?? $post->created_at->format('Y-m-d') }}
            </span>
        </div>
        <h3 class="blog-card__title">{{ $post->title }}</h3>
        <p class="blog-card__excerpt">{{ Str::limit(strip_tags($post->excerpt), $excerptLimit) }}</p>
        <span class="blog-card__cta">
            اقرأ المزيد
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
        </span>
    </div>
</a>
