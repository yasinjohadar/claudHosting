@php
    /** @var \App\Models\BlogPost|null $post */
    $post = $post ?? null;
    $previewId = 'blog-seo-' . ($post?->id ?? 'new');
    $ogPreview = $post && $post->og_image && function_exists('blog_image_url') ? blog_image_url($post->og_image) : ($post && $post->featured_image && function_exists('blog_image_url') ? blog_image_url($post->featured_image) : asset('frontend/assets/images/logo.png'));
@endphp

<div class="card custom-card mb-4" id="blogSeoPanel">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="card-title mb-0">إعدادات SEO المتقدمة</div>
        <button type="button" class="btn btn-sm btn-outline-primary" id="blogSeoAutofill">
            <i class="bi bi-magic"></i> تعبئة من العنوان والمقتطف
        </button>
    </div>
    <div class="card-body">
        <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#seo-tab-basic" type="button">أساسي</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#seo-tab-og" type="button">Open Graph</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#seo-tab-twitter" type="button">Twitter</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#seo-tab-schema" type="button">Schema</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#seo-tab-preview" type="button">معاينة</button></li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="seo-tab-basic">
                <div class="mb-3">
                    <label class="form-label">عنوان SEO (Meta Title)</label>
                    <input type="text" name="meta_title" id="meta_title" class="form-control blog-seo-title"
                           maxlength="70" value="{{ old('meta_title', $post->meta_title ?? '') }}">
                    <small class="text-muted"><span class="blog-seo-title-count">0</span>/70</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">وصف SEO (Meta Description)</label>
                    <textarea name="meta_description" id="meta_description" rows="3" class="form-control blog-seo-desc" maxlength="500">{{ old('meta_description', $post->meta_description ?? '') }}</textarea>
                    <small class="text-muted"><span class="blog-seo-desc-count">0</span>/160 موصى به</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">الكلمات المفتاحية</label>
                    <input type="text" name="meta_keywords" id="meta_keywords" class="form-control"
                           value="{{ old('meta_keywords', $post->meta_keywords ?? '') }}">
                    <small class="text-muted">افصل بفاصلة — أو تُستمد من الوسوم تلقائياً</small>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">الكلمة المفتاحية الرئيسية</label>
                        <input type="text" name="focus_keyword" class="form-control"
                               value="{{ old('focus_keyword', $post->focus_keyword ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">مرادفات الكلمة المفتاحية</label>
                        <input type="text" name="focus_keyword_synonyms" class="form-control"
                               value="{{ old('focus_keyword_synonyms', $post->focus_keyword_synonyms ?? '') }}">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label">رابط Canonical</label>
                    <input type="url" name="canonical_url" id="canonical_url" class="form-control"
                           placeholder="{{ $post ? route('frontend.blog.show', $post->slug) : 'يُولَّد تلقائياً بعد الحفظ' }}"
                           value="{{ old('canonical_url', $post->canonical_url ?? '') }}">
                </div>
                <div class="mt-3">
                    <label class="form-label">Robots (متقدم)</label>
                    <select name="robots_meta" class="form-select">
                        @foreach (['index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow'] as $robots)
                            <option value="{{ $robots }}" @selected(old('robots_meta', $post->robots_meta ?? 'index,follow') === $robots)>{{ $robots }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">يُحدَّث أيضاً من «قابل للفهرسة» و«قابل للمتابعة» في الشريط الجانبي</small>
                </div>
            </div>

            <div class="tab-pane fade" id="seo-tab-og">
                <div class="mb-3">
                    <label class="form-label">عنوان OG</label>
                    <input type="text" name="og_title" id="og_title" class="form-control"
                           value="{{ old('og_title', $post->og_title ?? '') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">وصف OG</label>
                    <textarea name="og_description" id="og_description" rows="2" class="form-control">{{ old('og_description', $post->og_description ?? '') }}</textarea>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">نوع OG</label>
                        <select name="og_type" class="form-select">
                            @foreach (['article', 'website', 'blog'] as $t)
                                <option value="{{ $t }}" @selected(old('og_type', $post->og_type ?? 'article') === $t)>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Locale</label>
                        <input type="text" name="og_locale" class="form-control"
                               value="{{ old('og_locale', $post->og_locale ?? 'ar_AR') }}">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">صورة OG</label>
                    @if ($post && $post->og_image)
                        <div class="mb-2">
                            <img src="{{ blog_image_url($post->og_image) }}" alt="" class="img-fluid rounded" style="max-height:120px">
                        </div>
                    @endif
                    <input type="file" name="og_image" class="form-control" accept="image/*">
                    <small class="text-muted">إن تُركت فارغة تُستخدم الصورة البارزة</small>
                </div>
            </div>

            <div class="tab-pane fade" id="seo-tab-twitter">
                <div class="mb-3">
                    <label class="form-label">نوع البطاقة</label>
                    <select name="twitter_card" class="form-select">
                        <option value="summary_large_image" @selected(old('twitter_card', $post->twitter_card ?? 'summary_large_image') === 'summary_large_image')>summary_large_image</option>
                        <option value="summary" @selected(old('twitter_card', $post->twitter_card ?? '') === 'summary')>summary</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">عنوان Twitter</label>
                    <input type="text" name="twitter_title" id="twitter_title" class="form-control"
                           value="{{ old('twitter_title', $post->twitter_title ?? '') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">وصف Twitter</label>
                    <textarea name="twitter_description" id="twitter_description" rows="2" class="form-control">{{ old('twitter_description', $post->twitter_description ?? '') }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">صورة Twitter</label>
                    @if ($post && $post->twitter_image)
                        <div class="mb-2">
                            <img src="{{ blog_image_url($post->twitter_image) }}" alt="" class="img-fluid rounded" style="max-height:120px">
                        </div>
                    @endif
                    <input type="file" name="twitter_image" class="form-control" accept="image/*">
                </div>
                <div class="mb-0">
                    <label class="form-label">@creator (اختياري)</label>
                    <input type="text" name="twitter_creator" class="form-control" placeholder="@cloudsoft"
                           value="{{ old('twitter_creator', $post->twitter_creator ?? '') }}">
                </div>
            </div>

            <div class="tab-pane fade" id="seo-tab-schema">
                <div class="mb-3">
                    <label class="form-label">نوع Schema</label>
                    <select name="schema_type" class="form-select">
                        @foreach (['BlogPosting', 'Article', 'NewsArticle', 'TechArticle'] as $stype)
                            <option value="{{ $stype }}" @selected(old('schema_type', $post->schema_type ?? 'BlogPosting') === $stype)>{{ $stype }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Headline (Schema)</label>
                    <input type="text" name="schema_headline" id="schema_headline" class="form-control"
                           value="{{ old('schema_headline', $post->schema_headline ?? '') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">وصف Schema</label>
                    <textarea name="schema_description" id="schema_description" rows="2" class="form-control">{{ old('schema_description', $post->schema_description ?? '') }}</textarea>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">اسم المؤلف (Schema)</label>
                        <input type="text" name="schema_author_name" class="form-control"
                               value="{{ old('schema_author_name', $post->schema_author_name ?? auth()->user()?->name) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">رابط المؤلف</label>
                        <input type="url" name="schema_author_url" class="form-control"
                               value="{{ old('schema_author_url', $post->schema_author_url ?? '') }}">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label">صورة Schema (اختياري)</label>
                    <input type="file" name="schema_image" class="form-control" accept="image/*">
                </div>
            </div>

            <div class="tab-pane fade" id="seo-tab-preview">
                <p class="text-muted small mb-3">معاينة تقريبية — القيم الفعلية تُحسب ديناميكياً عند النشر</p>
                <div class="border rounded p-3 mb-3 bg-light">
                    <div class="text-primary small mb-1" id="preview-url">{{ $post ? route('frontend.blog.show', $post->slug) : url('/blog/...') }}</div>
                    <div class="fw-semibold text-truncate" id="preview-title">{{ old('meta_title', $post->meta_title ?? 'عنوان المقال') }}</div>
                    <div class="text-muted small" id="preview-desc">{{ Str::limit(old('meta_description', $post->meta_description ?? 'وصف المقال'), 160) }}</div>
                </div>
                <div class="border rounded overflow-hidden" style="max-width: 500px;">
                    <img src="{{ $ogPreview }}" alt="" class="w-100" id="preview-og-image" style="max-height: 200px; object-fit: cover;">
                    <div class="p-2 small">
                        <div class="fw-bold text-truncate" id="preview-og-title">{{ old('og_title', $post->og_title ?? '') }}</div>
                        <div class="text-muted text-truncate" id="preview-og-desc">{{ old('og_description', $post->og_description ?? '') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    function len(el, counterSel, max) {
        if (!el) return;
        var c = document.querySelector(counterSel);
        if (c) c.textContent = Math.min(el.value.length, max || 999);
    }
    function bindCounters() {
        var title = document.getElementById('meta_title');
        var desc = document.getElementById('meta_description');
        if (title) {
            title.addEventListener('input', function () {
                len(title, '.blog-seo-title-count', 70);
                document.getElementById('preview-title') && (document.getElementById('preview-title').textContent = title.value || document.getElementById('title')?.value || '');
                document.getElementById('preview-og-title') && (document.getElementById('preview-og-title').textContent = document.getElementById('og_title')?.value || title.value);
            });
            len(title, '.blog-seo-title-count', 70);
        }
        if (desc) {
            desc.addEventListener('input', function () {
                len(desc, '.blog-seo-desc-count', 160);
                document.getElementById('preview-desc') && (document.getElementById('preview-desc').textContent = desc.value.substring(0, 160));
                document.getElementById('preview-og-desc') && (document.getElementById('preview-og-desc').textContent = document.getElementById('og_description')?.value || desc.value);
            });
            len(desc, '.blog-seo-desc-count', 160);
        }
    }
    document.getElementById('blogSeoAutofill')?.addEventListener('click', function () {
        var t = document.getElementById('title')?.value || '';
        var e = document.getElementById('excerpt')?.value || document.querySelector('[name="excerpt"]')?.value || '';
        ['meta_title', 'og_title', 'twitter_title', 'schema_headline'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el && !el.value) el.value = t;
        });
        ['meta_description', 'og_description', 'twitter_description', 'schema_description'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el && !el.value) el.value = e;
        });
        bindCounters();
        document.getElementById('meta_title')?.dispatchEvent(new Event('input'));
        document.getElementById('meta_description')?.dispatchEvent(new Event('input'));
    });
    document.addEventListener('DOMContentLoaded', bindCounters);
})();
</script>
@endpush
@endonce
