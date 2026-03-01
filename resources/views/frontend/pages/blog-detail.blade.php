@extends('frontend.layouts.master')

@section('page-title')
{{ $post->meta_title ?? $post->title }} | استضافة كلاودسوفت
@endsection

@php
    $seo = $post->getSeoMetaTags();
    $articleUrl = $seo['canonical'] ?? route('frontend.blog.show', $post->slug);
    $ogImage = !empty($post->og_image) ? (function_exists('blog_image_url') ? blog_image_url($post->og_image) : asset('storage/' . ltrim($post->og_image, '/'))) : (($post->featured_image && function_exists('blog_image_url')) ? blog_image_url($post->featured_image) : asset('frontend/assets/images/logo.png'));
    $twImage = !empty($post->twitter_image) ? (function_exists('blog_image_url') ? blog_image_url($post->twitter_image) : asset('storage/' . ltrim($post->twitter_image, '/'))) : $ogImage;
@endphp

@section('meta-description')
{{ Str::limit(strip_tags($seo['description'] ?? $post->excerpt ?? ''), 160) }}
@endsection

@section('canonical')
{{ $articleUrl }}
@endsection

@section('meta-og')
    <meta property="og:type" content="{{ $seo['og:type'] ?? 'article' }}">
    <meta property="og:url" content="{{ $articleUrl }}">
    <meta property="og:title" content="{{ $seo['og:title'] ?? $post->title }}">
    <meta property="og:description" content="{{ Str::limit(strip_tags($seo['og:description'] ?? $post->excerpt ?? ''), 200) }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:locale" content="{{ $seo['og:locale'] ?? 'ar_AR' }}">
    @if($post->published_at)
    <meta property="article:published_time" content="{{ $post->published_at->toIso8601String() }}">
    @endif
    @if($post->updated_at)
    <meta property="article:modified_time" content="{{ $post->updated_at->toIso8601String() }}">
    @endif
@endsection

@section('meta-twitter')
    <meta name="twitter:card" content="{{ $seo['twitter:card'] ?? 'summary_large_image' }}">
    <meta name="twitter:title" content="{{ $seo['twitter:title'] ?? $post->title }}">
    <meta name="twitter:description" content="{{ Str::limit(strip_tags($seo['twitter:description'] ?? $post->excerpt ?? ''), 200) }}">
    <meta name="twitter:image" content="{{ $twImage }}">
@endsection

@push('head')
@php
    $schema = $post->getSchemaJsonLd();
    $schema['url'] = $articleUrl;
    $schema['mainEntityOfPage'] = ['@type' => 'WebPage', '@id' => $articleUrl];
    if (!empty($schema['image'])) {
        $schema['image'] = ($post->featured_image && function_exists('blog_image_url')) ? blog_image_url($post->featured_image) : asset('frontend/assets/images/logo.png');
    }
    if (isset($schema['datePublished'])) {
        $schema['datePublished'] = $schema['datePublished'] instanceof \DateTimeInterface ? $schema['datePublished']->format('c') : $schema['datePublished'];
    }
    if (isset($schema['dateModified'])) {
        $schema['dateModified'] = $schema['dateModified'] instanceof \DateTimeInterface ? $schema['dateModified']->format('c') : $schema['dateModified'];
    }
    $schema['@type'] = $schema['@type'] ?? 'Article';
@endphp
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@section('content')
    <section class="blog-detail-hero">
        <div class="blog-detail-hero-img">
            @if($post->featured_image && function_exists('blog_image_url'))
                <img src="{{ blog_image_url($post->featured_image) }}" alt="{{ $post->featured_image_alt ?? $post->title }}" width="1200" height="400" loading="eager" style="object-fit:cover;width:100%;height:100%;">
            @else
                <img src="{{ asset('frontend/assets/images/course-webdev.svg') }}" alt="{{ $post->title }}" width="1200" height="400" loading="eager" style="object-fit:cover;width:100%;height:100%;">
            @endif
            <div class="blog-detail-hero-overlay"></div>
        </div>
    </section>

    <section class="section-padding" style="padding-top: 0; margin-top: -80px; position: relative; z-index: 10;">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="glass-panel blog-detail-content animate-on-scroll">
                        <div class="breadcrumb-custom" style="justify-content: flex-start; margin-bottom: 20px;">
                            <a href="{{ url('/') }}">الرئيسية</a><span>/</span><a href="{{ route('frontend.blog') }}">المدونة</a><span>/</span><span>{{ $post->category?->name ?? 'مقال' }}</span>
                        </div>
                        <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:15px;">
                            @if($post->category)
                            <span class="bd-category"><i class="fas fa-folder"></i> {{ $post->category->name }}</span>
                            @endif
                            <span class="bd-date"><i class="fas fa-calendar-alt"></i> {{ $post->published_at?->translatedFormat('d F Y') ?? $post->created_at->format('Y-m-d') }}</span>
                            @if($post->reading_time)
                            <span class="bd-date"><i class="fas fa-clock"></i> {{ $post->reading_time }} دقيقة قراءة</span>
                            @endif
                        </div>
                        <h1 class="bd-title">{{ $post->title }}</h1>
                        @if($post->author)
                        <div class="bd-author-bar">
                            <div class="bd-author-info">
                                <img src="{{ asset('frontend/assets/images/logo.png') }}" alt="{{ $post->author->name }}" width="45" height="45" loading="lazy">
                                <div><strong>{{ $post->author->name }}</strong><span>{{ $post->author->email }}</span></div>
                            </div>
                        </div>
                        @endif
                        <div class="bd-article">
                            @if($post->excerpt)
                            <p class="bd-intro">{{ $post->excerpt }}</p>
                            @endif
                            <div class="bd-content-html">
                                {!! $post->content !!}
                            </div>
                        </div>
                        @if($post->tags->count() > 0)
                        <div class="bd-tags">
                            <span class="bd-tag-label"><i class="fas fa-tags"></i> الوسوم:</span>
                            @foreach($post->tags as $tag)
                            <a href="{{ route('frontend.blog') }}?tag={{ $tag->slug }}" class="bd-tag">{{ $tag->name }}</a>
                            @endforeach
                        </div>
                        @endif
                        <div class="text-center mt-4">
                            <a href="{{ route('frontend.blog') }}" class="btn-primary-custom"><i class="fas fa-arrow-right"></i> العودة للمدونة</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="glass-panel animate-on-scroll" style="padding:25px; text-align:center; margin-bottom:20px;">
                        <img src="{{ asset('frontend/assets/images/trainer.svg') }}" alt="استضافة كلاودسوفت" style="width:90px;height:90px;border-radius:50%;border:3px solid var(--clr-primary);object-fit:cover;margin-bottom:12px;">
                        <h5 style="font-weight:700;margin-bottom:3px;">فريق استضافة كلاودسوفت</h5>
                        <p style="font-size:0.82rem;color:var(--clr-primary);font-weight:600;margin-bottom:10px;">دعم فني واستضافة سحابية</p>
                        <a href="{{ route('frontend.about') }}" class="btn-outline-custom" style="width:100%;justify-content:center;padding:8px;font-size:0.88rem;"><i class="fas fa-building"></i> حول الشركة</a>
                    </div>
                    @if($recentPosts->count() > 0)
                    <div class="glass-panel animate-on-scroll" style="padding:20px; margin-bottom:20px;">
                        <h6 style="font-weight:700;margin-bottom:15px;"><i class="fas fa-fire" style="color:var(--clr-primary);"></i> مقالات حديثة</h6>
                        @foreach($recentPosts as $recent)
                        <a href="{{ route('frontend.blog.show', $recent->slug) }}" class="bd-recent-post" style="{{ $loop->last ? 'margin-bottom:0;' : '' }}">
                            @if($recent->featured_image && function_exists('blog_image_url'))
                                <img src="{{ blog_image_url($recent->featured_image) }}" alt="{{ $recent->title }}">
                            @else
                                <img src="{{ asset('frontend/assets/images/course-python.svg') }}" alt="{{ $recent->title }}">
                            @endif
                            <div>
                                <h6 style="font-weight:700;font-size:0.85rem;">{{ Str::limit($recent->title, 40) }}</h6>
                                <span style="font-size:0.75rem;color:var(--clr-text-muted);"><i class="fas fa-calendar-alt"></i> {{ $recent->published_at?->translatedFormat('d F Y') ?? $recent->created_at->format('Y-m-d') }}</span>
                            </div>
                        </a>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
