@extends('frontend.layouts.master')

@section('page-title')
المدونة | استضافة كلاودسوفت
@endsection

@section('content')
    <!-- ============ PAGE HERO ============ -->
    <section class="section-padding" style="padding-top: 8rem;">
        <div class="container">
            <div class="section-header animate-on-scroll text-center">
                <span class="section-badge">المدونة</span>
                <h1>آخر التدوينات</h1>
                <p class="mx-auto" style="max-width: 600px;">مقالات تقنية وتعليمية في عالم البرمجة، الاستضافة، والتكنولوجيا.</p>
            </div>
        </div>
    </section>

    <!-- ============ BLOG LIST ============ -->
    <section class="section-padding" id="blog-list" style="background: var(--clr-bg-secondary);">
        <div class="container">
            @if($posts->count() > 0)
            <div class="row g-4">
                @foreach($posts as $index => $post)
                <div class="col-lg-4 col-md-6">
                    <a href="{{ route('frontend.blog.show', $post->slug) }}" class="glass-panel blog-card animate-on-scroll animate-delay-{{ ($index % 3) + 1 }}" style="text-decoration:none;color:inherit;display:block;height:100%;">
                        <div class="blog-img-wrapper">
                            @if($post->featured_image && function_exists('blog_image_url'))
                                <img src="{{ blog_image_url($post->featured_image) }}" alt="{{ $post->featured_image_alt ?? $post->title }}" width="400" height="180" loading="lazy">
                            @else
                                <img src="{{ asset('frontend/assets/images/course-webdev.svg') }}" alt="{{ $post->title }}" width="400" height="180" loading="lazy">
                            @endif
                        </div>
                        <div class="blog-body">
                            <div class="blog-meta">
                                <span><i class="fas fa-calendar-alt"></i> {{ $post->published_at?->translatedFormat('d F Y') ?? $post->created_at->format('Y-m-d') }}</span>
                                @if($post->category)
                                <span><i class="fas fa-folder"></i> {{ $post->category->name }}</span>
                                @endif
                            </div>
                            <h5>{{ $post->title }}</h5>
                            <p>{{ Str::limit(strip_tags($post->excerpt), 120) }}</p>
                            <span class="read-more">اقرأ المزيد <i class="fas fa-arrow-left"></i></span>
                        </div>
                    </a>
                </div>
                @endforeach
            </div>
            <div class="d-flex justify-content-center mt-5">
                {{ $posts->links() }}
            </div>
            @else
            <div class="text-center py-5">
                <p class="text-muted">لا توجد تدوينات منشورة حالياً.</p>
                <a href="{{ url('/') }}#blog" class="btn-primary-custom mt-3"><i class="fas fa-home"></i> العودة للرئيسية</a>
            </div>
            @endif
            <div class="text-center mt-4 animate-on-scroll">
                <a href="{{ url('/') }}#blog" class="btn-outline-custom">
                    <i class="fas fa-home"></i> العودة للرئيسية
                </a>
            </div>
        </div>
    </section>
@endsection
