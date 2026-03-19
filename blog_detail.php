@extends('frontend.layouts.master')

@section('title', ($post->meta_title ?? $post->title) . ' | ياسين جوخدار')
@section('description', $post->meta_description ?? $post->excerpt ?? Str::limit(strip_tags($post->content), 160))

@section('content')
    <!-- BLOG POST HERO IMAGE -->
    <section class="blog-detail-hero">
        <div class="blog-detail-hero-img">
            <img src="{{ $post->featured_image ? route('blog.image', ['filename' => basename($post->featured_image)]) : $fa . '/images/course-webdev.svg' }}" alt="{{ $post->featured_image_alt ?? $post->title }}" width="1200" height="400" loading="eager">
            <div class="blog-detail-hero-overlay"></div>
        </div>
    </section>

    <!-- BLOG POST CONTENT -->
    <section class="section-padding" style="padding-top: 0; margin-top: -80px; position: relative; z-index: 10;">
        <div class="container">
            <div class="row g-4">
                <!-- Main Content -->
                <div class="col-lg-8">
                    <div class="glass-panel blog-detail-content">
                        <!-- Breadcrumb -->
                        <div class="breadcrumb-custom" style="justify-content: flex-start; margin-bottom: 20px;">
                            <a href="{{ route('home') }}">الرئيسية</a><span>/</span><a href="{{ route('blog') }}">المدونة</a><span>/</span><span>{{ $post->category?->name ?? '—' }}</span>
                        </div>

                        <!-- Category & Date -->
                        <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:15px;">
                            <span class="bd-category"><i class="fas fa-folder"></i> {{ $post->category?->name ?? '—' }}</span>
                            <span class="bd-date"><i class="fas fa-calendar-alt"></i> {{ $post->published_at?->translatedFormat('d F Y') }}</span>
                            <span class="bd-date"><i class="fas fa-clock"></i> {{ $post->reading_time ?? '—' }} دقيقة قراءة</span>
                        </div>

                        <!-- Title -->
                        <h1 class="bd-title">{{ $post->title }}</h1>

                        <!-- Author & Stats -->
                        <div class="bd-author-bar">
                            <div class="bd-author-info">
                                <img src="{{ $fa }}/images/logo.svg" alt="{{ $post->author?->name ?? 'ياسين جوخدار' }}" width="45" height="45" loading="lazy">
                                <div>
                                    <strong>{{ $post->author?->name ?? 'ياسين جوخدار' }}</strong>
                                    <span>مطور ويب ومدرب تقني</span>
                                </div>
                            </div>
                            <div class="bd-post-stats">
                                <span><i class="fas fa-eye"></i> {{ number_format($post->views_count) }}</span>
                                <span><i class="fas fa-comments"></i> {{ $post->comments_count }}</span>
                            </div>
                        </div>

                        <!-- Article Body -->
                        <div class="bd-article">
                            <div class="bd-content-html">
                                {!! $post->content !!}
                            </div>
                        </div>

                        <!-- Tags -->
                        @if($post->tags->isNotEmpty())
                        <div class="bd-tags">
                            <span class="bd-tag-label"><i class="fas fa-tags"></i> الوسوم:</span>
                            @foreach($post->tags as $tag)
                            <a href="{{ route('blog') }}?tag={{ $tag->slug }}" class="bd-tag">{{ $tag->name }}</a>
                            @endforeach
                        </div>
                        @endif

                        <!-- Share -->
                        @php $shareUrl = url()->current(); $shareTitle = urlencode($post->title); @endphp
                        <div class="bd-share">
                            <span><i class="fas fa-share-alt"></i> شارك المقال:</span>
                            <div class="bd-share-icons">
                                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}" target="_blank" rel="noopener" class="bd-share-btn" style="background:#1877F2;"><i class="fab fa-facebook-f"></i></a>
                                <a href="https://twitter.com/intent/tweet?url={{ urlencode($shareUrl) }}&text={{ $shareTitle }}" target="_blank" rel="noopener" class="bd-share-btn" style="background:#1DA1F2;"><i class="fab fa-twitter"></i></a>
                                <a href="https://wa.me/?text={{ $shareTitle }}%20{{ urlencode($shareUrl) }}" target="_blank" rel="noopener" class="bd-share-btn" style="background:#25D366;"><i class="fab fa-whatsapp"></i></a>
                                <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ urlencode($shareUrl) }}&title={{ $shareTitle }}" target="_blank" rel="noopener" class="bd-share-btn" style="background:#0077B5;"><i class="fab fa-linkedin-in"></i></a>
                                <a href="https://t.me/share/url?url={{ urlencode($shareUrl) }}&text={{ $shareTitle }}" target="_blank" rel="noopener" class="bd-share-btn" style="background:#0088cc;"><i class="fab fa-telegram-plane"></i></a>
                            </div>
                        </div>

                        <!-- Prev / Next -->
                        <div class="bd-nav-posts">
                            @if($prevPost ?? null)
                            <a href="{{ route('blog.show', $prevPost->slug) }}" class="bd-nav-post bd-nav-prev">
                                <span class="bd-nav-label"><i class="fas fa-arrow-right"></i> المقال السابق</span>
                                <span class="bd-nav-title">{{ Str::limit($prevPost->title, 35) }}</span>
                            </a>
                            @else
                            <span class="bd-nav-post bd-nav-prev" style="pointer-events:none;opacity:0.6;"><span class="bd-nav-label">المقال السابق</span><span class="bd-nav-title">—</span></span>
                            @endif
                            @if($nextPost ?? null)
                            <a href="{{ route('blog.show', $nextPost->slug) }}" class="bd-nav-post bd-nav-next">
                                <span class="bd-nav-label">المقال التالي <i class="fas fa-arrow-left"></i></span>
                                <span class="bd-nav-title">{{ Str::limit($nextPost->title, 35) }}</span>
                            </a>
                            @else
                            <span class="bd-nav-post bd-nav-next" style="pointer-events:none;opacity:0.6;"><span class="bd-nav-label">المقال التالي</span><span class="bd-nav-title">—</span></span>
                            @endif
                        </div>

                        <!-- Comments Section -->
                        <div class="bd-comments">
                            <h4 class="bd-comments-title"><i class="fas fa-comments" style="color:var(--clr-primary);"></i> التعليقات ({{ $post->comments_count }})</h4>

                            <!-- Comment 1 -->
                            <div class="bd-comment">
                                <div class="bd-comment-avatar" style="background:linear-gradient(135deg,var(--clr-primary),var(--clr-primary-dark));">أ</div>
                                <div class="bd-comment-body">
                                    <div class="bd-comment-head">
                                        <strong>أحمد سليمان</strong>
                                        <span>منذ 3 أيام</span>
                                    </div>
                                    <p>مقال رائع ومفيد جداً! شكراً للمدرب ياسين على هذا المحتوى القيّم 🙏</p>
                                    <button class="bd-reply-btn"><i class="fas fa-reply"></i> رد</button>
                                </div>
                            </div>

                            <!-- Add Comment Form -->
                            <div class="bd-add-comment">
                                <h5><i class="fas fa-pen" style="color:var(--clr-primary);"></i> أضف تعليقك</h5>
                                <form>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <input type="text" class="form-control" placeholder="الاسم الكامل"
                                                style="background:var(--clr-surface);border:1px solid var(--clr-border);color:var(--clr-text);padding:12px 16px;border-radius:var(--radius-md);font-family:var(--font-family);">
                                        </div>
                                        <div class="col-md-6">
                                            <input type="email" class="form-control" placeholder="البريد الإلكتروني"
                                                style="background:var(--clr-surface);border:1px solid var(--clr-border);color:var(--clr-text);padding:12px 16px;border-radius:var(--radius-md);font-family:var(--font-family);">
                                        </div>
                                        <div class="col-12">
                                            <textarea class="form-control" rows="4" placeholder="اكتب تعليقك هنا..."
                                                style="background:var(--clr-surface);border:1px solid var(--clr-border);color:var(--clr-text);padding:12px 16px;border-radius:var(--radius-md);font-family:var(--font-family);"></textarea>
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" class="btn-primary-custom"><i
                                                    class="fas fa-paper-plane"></i> إرسال التعليق</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SIDEBAR -->
                <div class="col-lg-4">
                    <!-- Author Card -->
                    <div class="glass-panel animate-on-scroll"
                        style="padding:25px; text-align:center; margin-bottom:20px;">
                        <img src="{{ $fa }}/images/trainer.svg" alt="ياسين جوخدار"
                            style="width:90px;height:90px;border-radius:50%;border:3px solid var(--clr-primary);object-fit:cover;margin-bottom:12px;">
                        <h5 style="font-weight:700;margin-bottom:3px;">ياسين جوخدار</h5>
                        <p style="font-size:0.82rem;color:var(--clr-primary);font-weight:600;margin-bottom:10px;">مطور
                            ويب ومدرب تقني</p>
                        <p style="font-size:0.88rem;color:var(--clr-text-secondary);margin-bottom:15px;">مدرب ومطور
                            برمجيات بخبرة +10 سنوات. شغوف بنقل المعرفة وتبسيط المفاهيم البرمجية.</p>
                        <a href="about.html" class="btn-outline-custom"
                            style="width:100%;justify-content:center;padding:8px;font-size:0.88rem;">
                            <i class="fas fa-user"></i> عرض الملف الشخصي
                        </a>
                    </div>

                    <!-- Search -->
                    <div class="glass-panel animate-on-scroll" style="padding:20px; margin-bottom:20px;">
                        <h6 style="font-weight:700;margin-bottom:12px;"><i class="fas fa-search"
                                style="color:var(--clr-primary);"></i> بحث في المدونة</h6>
                        <div style="position:relative;">
                            <input type="text" class="form-control" placeholder="ابحث عن مقال..."
                                style="background:var(--clr-surface);border:1px solid var(--clr-border);color:var(--clr-text);padding:10px 16px 10px 40px;border-radius:var(--radius-md);font-family:var(--font-family);font-size:0.9rem;">
                            <i class="fas fa-search"
                                style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--clr-text-muted);font-size:0.85rem;"></i>
                        </div>
                    </div>

                    <!-- Categories -->
                    <div class="glass-panel animate-on-scroll" style="padding:20px; margin-bottom:20px;">
                        <h6 style="font-weight:700;margin-bottom:15px;"><i class="fas fa-th-list" style="color:var(--clr-primary);"></i> التصنيفات</h6>
                        <div class="bd-sidebar-cats">
                            @forelse($categories ?? [] as $cat)
                            <a href="{{ route('blog') }}?category={{ $cat->slug }}" class="bd-cat-item"><span>{{ $cat->name }}</span><span class="bd-cat-count">{{ $cat->published_posts_count ?? 0 }}</span></a>
                            @empty
                            <p class="text-muted small mb-0">لا توجد تصنيفات</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- Recent Posts -->
                    <div class="glass-panel animate-on-scroll" style="padding:20px; margin-bottom:20px;">
                        <h6 style="font-weight:700;margin-bottom:15px;"><i class="fas fa-fire" style="color:var(--clr-primary);"></i> مقالات حديثة</h6>
                        @forelse($recentPosts ?? [] as $recent)
                        <a href="{{ route('blog.show', $recent->slug) }}" class="bd-recent-post">
                            <img src="{{ $recent->featured_image ? route('blog.image', ['filename' => basename($recent->featured_image)]) : $fa . '/images/course-webdev.svg' }}" alt="{{ $recent->title }}">
                            <div>
                                <h6 style="font-weight:700;font-size:0.85rem;margin-bottom:3px;">{{ Str::limit($recent->title, 40) }}</h6>
                                <span style="font-size:0.75rem;color:var(--clr-text-muted);"><i class="fas fa-calendar-alt"></i> {{ $recent->published_at?->translatedFormat('d F Y') }}</span>
                            </div>
                        </a>
                        @empty
                        <p class="text-muted small mb-0">لا توجد مقالات أخرى</p>
                        @endforelse
                    </div>

                    <!-- Tags Cloud -->
                    <div class="glass-panel animate-on-scroll" style="padding:20px; margin-bottom:20px;">
                        <h6 style="font-weight:700;margin-bottom:15px;"><i class="fas fa-tags"
                                style="color:var(--clr-primary);"></i> الوسوم</h6>
                        <div style="display:flex;flex-wrap:wrap;gap:8px;">
                            <a href="#" class="bd-tag">HTML</a>
                            <a href="#" class="bd-tag">CSS</a>
                            <a href="#" class="bd-tag">JavaScript</a>
                            <a href="#" class="bd-tag">React</a>
                            <a href="#" class="bd-tag">Node.js</a>
                            <a href="#" class="bd-tag">بايثون</a>
                            <a href="#" class="bd-tag">Flutter</a>
                            <a href="#" class="bd-tag">Docker</a>
                            <a href="#" class="bd-tag">Git</a>
                            <a href="#" class="bd-tag">AI</a>
                            <a href="#" class="bd-tag">WordPress</a>
                        </div>
                    </div>

                    <!-- Newsletter -->
                    <div class="glass-panel newsletter-sidebar-card animate-on-scroll">
                        <i class="fas fa-envelope-open-text newsletter-sidebar-icon"></i>
                        <h6 class="newsletter-sidebar-title">اشترك في النشرة البريدية</h6>
                        <p class="newsletter-sidebar-desc">احصل على أحدث المقالات في بريدك</p>
                        @include('frontend.partials.newsletter-form', ['source' => 'blog-detail', 'variant' => 'sidebar'])
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection