@php
    // نص النشرة البريدية الخاص بهذه الصفحة (يقرأه كمبوننت newsletter-section)
    $nlTitle = 'أعجبك المقال؟';
    $nlDesc = 'اشترك لتصلك مقالات مثله في الاستضافة وإدارة السيرفرات مباشرة في بريدك.';
@endphp

@extends('frontend.layouts.master')

@php
    $heroImage =
        $post->featured_image && function_exists('blog_image_url')
            ? blog_image_url($post->featured_image)
            : asset('frontend/assets/images/blog-hero-bg.svg');
    $heroAlt = $post->featured_image_alt ?? $post->title;
    $postDate = $post->published_at ?? $post->created_at;
    $shareUrl = urlencode(url()->current());
    $shareTitle = urlencode($post->title);
@endphp

@section('content')
    <div class="bd-progress" aria-hidden="true"><span class="bd-progress__bar" id="bdProgressBar"></span></div>

    <header class="bd-hero">
        <div class="bd-hero__media">
            <img src="{{ $heroImage }}" alt="{{ $heroAlt }}" width="1920" height="1080" loading="eager" fetchpriority="high">
        </div>
        <span class="bd-hero__scrim" aria-hidden="true"></span>

        <div class="container bd-hero__inner">
            <nav class="bd-hero__crumbs" aria-label="مسار التصفح">
                <a href="{{ url('/') }}">الرئيسية</a>
                <i class="fas fa-chevron-left" aria-hidden="true"></i>
                <a href="{{ route('frontend.blog') }}">المدونة</a>
                @if ($post->category)
                    <i class="fas fa-chevron-left" aria-hidden="true"></i>
                    <a href="{{ $post->category->url }}">{{ $post->category->name }}</a>
                @endif
            </nav>

            @if ($post->category)
                <a href="{{ $post->category->url }}" class="bd-hero__category">
                    <i class="fas fa-folder-open" aria-hidden="true"></i> {{ $post->category->name }}
                </a>
            @endif

            <h1 class="bd-hero__title">{{ $post->title }}</h1>

            @if ($post->excerpt)
                <p class="bd-hero__excerpt">{{ Str::limit(strip_tags($post->excerpt), 160) }}</p>
            @endif

            <div class="bd-hero__meta">
                @if ($post->author)
                    <span class="bd-hero__author">
                        <img src="{{ asset('frontend/assets/images/brand-avatar.svg') }}" alt="" width="36" height="36" loading="lazy">
                        {{ $post->author->name }}
                    </span>
                    <span class="bd-hero__dot" aria-hidden="true"></span>
                @endif
                <span class="bd-hero__meta-item">
                    <i class="far fa-calendar-alt" aria-hidden="true"></i>
                    <time datetime="{{ $postDate?->toDateString() }}">{{ $postDate?->translatedFormat('d F Y') }}</time>
                </span>
                @if ($post->reading_time)
                    <span class="bd-hero__dot" aria-hidden="true"></span>
                    <span class="bd-hero__meta-item">
                        <i class="far fa-clock" aria-hidden="true"></i> {{ $post->reading_time }} دقيقة قراءة
                    </span>
                @endif
            </div>
        </div>
    </header>

    <section class="bd-main">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-8">
                    <article class="glass-panel blog-detail-content">
                        <div class="bd-article">
                            <div class="bd-content-html">
                                {!! $post->content !!}
                            </div>
                        </div>

                        @if ($post->tags->count() > 0)
                            <div class="bd-tags">
                                <span class="bd-tag-label"><i class="fas fa-tags" aria-hidden="true"></i> الوسوم:</span>
                                @foreach ($post->tags as $tag)
                                    <a href="{{ $tag->url }}" class="bd-tag">{{ $tag->name }}</a>
                                @endforeach
                            </div>
                        @endif

                        <div class="bd-share">
                            <span><i class="fas fa-share-alt" aria-hidden="true"></i> شارك المقال:</span>
                            <div class="bd-share-icons">
                                <a class="bd-share-btn bd-share-btn--wa" href="https://wa.me/?text={{ $shareTitle }}%20{{ $shareUrl }}"
                                    target="_blank" rel="noopener noreferrer" aria-label="مشاركة عبر واتساب"><i class="fab fa-whatsapp" aria-hidden="true"></i></a>
                                <a class="bd-share-btn bd-share-btn--tw" href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareTitle }}"
                                    target="_blank" rel="noopener noreferrer" aria-label="مشاركة عبر تويتر"><i class="fab fa-twitter" aria-hidden="true"></i></a>
                                <a class="bd-share-btn bd-share-btn--fb" href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}"
                                    target="_blank" rel="noopener noreferrer" aria-label="مشاركة عبر فيسبوك"><i class="fab fa-facebook-f" aria-hidden="true"></i></a>
                                <a class="bd-share-btn bd-share-btn--li" href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}"
                                    target="_blank" rel="noopener noreferrer" aria-label="مشاركة عبر لينكدإن"><i class="fab fa-linkedin-in" aria-hidden="true"></i></a>
                                <button type="button" class="bd-share-btn bd-share-btn--copy" id="bdCopyLink" aria-label="نسخ رابط المقال"><i class="fas fa-link" aria-hidden="true"></i></button>
                            </div>
                        </div>

                        <div class="bd-back">
                            <a href="{{ route('frontend.blog') }}" class="btn-outline-custom">
                                <i class="fas fa-arrow-right" aria-hidden="true"></i> العودة للمدونة
                            </a>
                        </div>
                    </article>
                </div>

                <aside class="col-lg-4">
                    <div class="bd-sidebar">
                        <div class="glass-panel bd-side-card bd-side-card--brand animate-on-scroll">
                            <img class="bd-side-avatar" src="{{ asset('frontend/assets/images/brand-avatar.svg') }}"
                                alt="استضافة كلاودسوفت" width="90" height="90" loading="lazy">
                            <h5 class="bd-side-brand-name">فريق استضافة كلاودسوفت</h5>
                            <p class="bd-side-brand-role">دعم فني واستضافة سحابية</p>
                            <a href="{{ route('frontend.about') }}" class="btn-outline-custom bd-side-btn">
                                <i class="fas fa-building" aria-hidden="true"></i> حول الشركة
                            </a>
                        </div>

                        @if ($recentPosts->count() > 0)
                            <div class="glass-panel bd-side-card animate-on-scroll">
                                <h6 class="bd-side-title"><i class="fas fa-fire" aria-hidden="true"></i> مقالات حديثة</h6>
                                @foreach ($recentPosts as $recent)
                                    <a href="{{ route('frontend.blog.show', $recent->slug) }}" class="bd-recent-post">
                                        @if ($recent->featured_image && function_exists('blog_image_url'))
                                            <img src="{{ blog_image_url($recent->featured_image) }}" alt="" width="70" height="50" loading="lazy">
                                        @else
                                            <img src="{{ asset('frontend/assets/images/blog-hero-bg.svg') }}" alt="" width="70" height="50" loading="lazy">
                                        @endif
                                        <div>
                                            <h6 class="bd-recent-title">{{ Str::limit($recent->title, 46) }}</h6>
                                            <span class="bd-recent-date">
                                                <i class="far fa-calendar-alt" aria-hidden="true"></i>
                                                {{ $recent->published_at?->translatedFormat('d F Y') ?? $recent->created_at->format('Y-m-d') }}
                                            </span>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        <div class="glass-panel bd-side-card bd-side-cta animate-on-scroll">
                            <i class="fas fa-server bd-side-cta__icon" aria-hidden="true"></i>
                            <h6 class="bd-side-cta__title">جاهز لإطلاق موقعك؟</h6>
                            <p class="bd-side-cta__text">باقات استضافة سحابية سريعة وآمنة مع دعم فني عربي على مدار الساعة.</p>
                            <a href="{{ route('frontend.packages') }}" class="btn-primary-custom bd-side-btn">
                                <i class="fas fa-rocket" aria-hidden="true"></i> تصفح الباقات
                            </a>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        (function () {
            var bar = document.getElementById('bdProgressBar');
            var article = document.querySelector('.bd-article');
            if (bar && article) {
                var update = function () {
                    var start = article.offsetTop;
                    var total = article.offsetHeight - window.innerHeight;
                    var done = total > 0 ? (window.scrollY - start) / total : 1;
                    bar.style.width = Math.min(100, Math.max(0, done * 100)) + '%';
                };
                window.addEventListener('scroll', update, { passive: true });
                window.addEventListener('resize', update);
                update();
            }

            var copyBtn = document.getElementById('bdCopyLink');
            if (copyBtn && navigator.clipboard) {
                copyBtn.addEventListener('click', function () {
                    navigator.clipboard.writeText(window.location.href).then(function () {
                        copyBtn.classList.add('is-copied');
                        copyBtn.innerHTML = '<i class="fas fa-check"></i>';
                        setTimeout(function () {
                            copyBtn.classList.remove('is-copied');
                            copyBtn.innerHTML = '<i class="fas fa-link"></i>';
                        }, 1800);
                    });
                });
            }
        })();
    </script>
@endsection
