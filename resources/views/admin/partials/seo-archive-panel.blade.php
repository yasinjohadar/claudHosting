@php
    /** @var \App\Models\BlogCategory|\App\Models\BlogTag|null $model */
    $model = $model ?? null;
    $archiveType = $archiveType ?? 'category';
    $previewId = 'archive-seo-' . $archiveType . '-' . ($model?->id ?? 'new');
    $defaultCanonical = '';
    if ($model?->slug) {
        $defaultCanonical = $archiveType === 'tag'
            ? route('frontend.blog.tag', $model->slug)
            : route('frontend.blog.category', $model->slug);
    }
    $ogPreview = $model && $model->og_image && function_exists('blog_image_url')
        ? blog_image_url($model->og_image)
        : asset('frontend/assets/images/logo.png');
@endphp

<div class="card custom-card mb-4" id="{{ $previewId }}">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="card-title mb-0">إعدادات SEO</div>
        <button type="button" class="btn btn-sm btn-outline-primary archive-seo-autofill" data-target="{{ $previewId }}">
            <i class="bi bi-magic"></i> تعبئة من الاسم والوصف
        </button>
    </div>
    <div class="card-body">
        <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#{{ $previewId }}-basic" type="button">أساسي</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#{{ $previewId }}-og" type="button">Open Graph</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#{{ $previewId }}-preview" type="button">معاينة</button></li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="{{ $previewId }}-basic">
                <div class="mb-3">
                    <label class="form-label">عنوان SEO (Meta Title)</label>
                    <input type="text" name="meta_title" class="form-control archive-seo-title" maxlength="70"
                           value="{{ old('meta_title', $model->meta_title ?? '') }}">
                    <small class="text-muted"><span class="archive-seo-title-count">0</span>/70</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">وصف SEO (Meta Description)</label>
                    <textarea name="meta_description" rows="3" class="form-control archive-seo-desc" maxlength="500">{{ old('meta_description', $model->meta_description ?? '') }}</textarea>
                    <small class="text-muted"><span class="archive-seo-desc-count">0</span>/160 موصى به</small>
                </div>
                @if ($archiveType === 'category')
                <div class="mb-3">
                    <label class="form-label">الكلمات المفتاحية</label>
                    <input type="text" name="meta_keywords" class="form-control"
                           value="{{ old('meta_keywords', $model->meta_keywords ?? '') }}">
                </div>
                @endif
                <div class="mb-3">
                    <label class="form-label">رابط Canonical</label>
                    <input type="url" name="canonical_url" class="form-control archive-seo-canonical"
                           placeholder="{{ $defaultCanonical ?: 'يُولَّد تلقائياً بعد الحفظ' }}"
                           value="{{ old('canonical_url', $model->canonical_url ?? '') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Robots</label>
                    <select name="robots_meta" class="form-select">
                        @foreach (['index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow'] as $robots)
                            <option value="{{ $robots }}" @selected(old('robots_meta', $model->robots_meta ?? 'index,follow') === $robots)>{{ $robots }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-check">
                    <input type="hidden" name="is_indexable" value="0">
                    <input class="form-check-input" type="checkbox" name="is_indexable" value="1" id="{{ $previewId }}-indexable"
                           {{ old('is_indexable', $model->is_indexable ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="{{ $previewId }}-indexable">قابل للفهرسة في محركات البحث</label>
                </div>
            </div>

            <div class="tab-pane fade" id="{{ $previewId }}-og">
                <div class="mb-3">
                    <label class="form-label">عنوان OG</label>
                    <input type="text" name="og_title" class="form-control archive-seo-og-title"
                           value="{{ old('og_title', $model->og_title ?? '') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">وصف OG</label>
                    <textarea name="og_description" rows="2" class="form-control archive-seo-og-desc">{{ old('og_description', $model->og_description ?? '') }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">صورة OG</label>
                    @if ($model && $model->og_image)
                        <div class="mb-2">
                            <img src="{{ blog_image_url($model->og_image) }}" alt="" class="img-fluid rounded" style="max-height:120px">
                        </div>
                    @endif
                    <input type="file" name="og_image" class="form-control" accept="image/*">
                </div>
            </div>

            <div class="tab-pane fade" id="{{ $previewId }}-preview">
                <p class="text-muted small mb-2">معاينة Google</p>
                <div class="border rounded p-3 bg-light">
                    <div class="text-success small archive-seo-preview-url">{{ $defaultCanonical ?: url('/') }}</div>
                    <div class="text-primary archive-seo-preview-title" style="font-size:1.1rem">{{ old('meta_title', $model->meta_title ?? $model->name ?? 'عنوان الصفحة') }}</div>
                    <div class="text-muted small archive-seo-preview-desc">{{ old('meta_description', $model->meta_description ?? $model->description ?? 'وصف الصفحة') }}</div>
                </div>
                <p class="text-muted small mt-3 mb-2">معاينة Snippet اجتماعي</p>
                <div class="border rounded overflow-hidden" style="max-width:480px">
                    <img src="{{ $ogPreview }}" alt="" class="w-100 archive-seo-preview-image" style="max-height:160px;object-fit:cover">
                    <div class="p-2 bg-white">
                        <div class="fw-semibold small archive-seo-preview-og-title">{{ old('og_title', $model->og_title ?? $model->name ?? '') }}</div>
                        <div class="text-muted small archive-seo-preview-og-desc">{{ old('og_description', $model->og_description ?? '') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    function bindArchiveSeoPanel(root) {
        const nameInput = root.closest('form')?.querySelector('[name="name"]');
        const descInput = root.closest('form')?.querySelector('[name="description"]');
        const titleInput = root.querySelector('.archive-seo-title');
        const descSeo = root.querySelector('.archive-seo-desc');
        const ogTitle = root.querySelector('.archive-seo-og-title');
        const ogDesc = root.querySelector('.archive-seo-og-desc');
        const titleCount = root.querySelector('.archive-seo-title-count');
        const descCount = root.querySelector('.archive-seo-desc-count');

        function updateCounts() {
            if (titleCount && titleInput) titleCount.textContent = titleInput.value.length;
            if (descCount && descSeo) descCount.textContent = descSeo.value.length;
        }

        function updatePreview() {
            const title = titleInput?.value || nameInput?.value || '';
            const desc = descSeo?.value || descInput?.value || '';
            root.querySelectorAll('.archive-seo-preview-title').forEach(el => el.textContent = title || 'عنوان الصفحة');
            root.querySelectorAll('.archive-seo-preview-desc').forEach(el => el.textContent = desc || 'وصف الصفحة');
            root.querySelectorAll('.archive-seo-preview-og-title').forEach(el => el.textContent = ogTitle?.value || title);
            root.querySelectorAll('.archive-seo-preview-og-desc').forEach(el => el.textContent = ogDesc?.value || desc);
        }

        [titleInput, descSeo, ogTitle, ogDesc, nameInput, descInput].forEach(el => {
            el?.addEventListener('input', () => { updateCounts(); updatePreview(); });
        });

        root.querySelector('.archive-seo-autofill')?.addEventListener('click', function () {
            if (nameInput?.value && titleInput && !titleInput.value) titleInput.value = nameInput.value;
            if (descInput?.value && descSeo && !descSeo.value) descSeo.value = descInput.value;
            if (nameInput?.value && ogTitle && !ogTitle.value) ogTitle.value = nameInput.value;
            if (descInput?.value && ogDesc && !ogDesc.value) ogDesc.value = descInput.value;
            updateCounts();
            updatePreview();
        });

        updateCounts();
        updatePreview();
    }

    document.querySelectorAll('[id^="archive-seo-"]').forEach(bindArchiveSeoPanel);
});
</script>
@endpush
@endonce
