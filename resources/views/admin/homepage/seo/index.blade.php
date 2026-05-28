@extends('admin.layouts.master')

@section('page-title')
إعدادات SEO
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">إعدادات SEO — الصفحات العامة</h4>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item active" aria-current="page">SEO</li>
                    </ol>
                </nav>
            </div>
            <a href="{{ url('/') }}" target="_blank" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-external-link-alt"></i> معاينة الموقع
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <form action="{{ route('admin.homepage.seo.update') }}" method="post" enctype="multipart/form-data" id="seoSettingsForm">
            @csrf
            @method('PUT')

            <div class="row g-4">
                <div class="col-lg-3">
                    <div class="card custom-card sticky-top" style="top: 90px;">
                        <div class="card-header">
                            <div class="card-title mb-0">الصفحات</div>
                        </div>
                        <div class="card-body p-0">
                            <div class="nav flex-column nav-pills seo-page-nav" id="seoPageTabs" role="tablist">
                                @foreach ($pages as $routeName => $label)
                                    <button class="nav-link text-start {{ $loop->first ? 'active' : '' }}"
                                        id="tab-btn-{{ md5($routeName) }}"
                                        data-bs-toggle="pill"
                                        data-bs-target="#tab-pane-{{ md5($routeName) }}"
                                        type="button"
                                        role="tab">
                                        {{ $label }}
                                        <small class="d-block text-muted">{{ $routeName }}</small>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-9">
                    <div class="tab-content" id="seoPageTabsContent">
                        @foreach ($pages as $routeName => $label)
                            @php
                                $cfg = $configs[$routeName] ?? [];
                                $paneId = 'tab-pane-' . md5($routeName);
                            @endphp
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="{{ $paneId }}" role="tabpanel">
                                <div class="card custom-card">
                                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                                        <div class="card-title mb-0">{{ $label }}</div>
                                        <button type="submit"
                                            formaction="{{ route('admin.homepage.seo.reset') }}"
                                            formmethod="post"
                                            name="route"
                                            value="{{ $routeName }}"
                                            class="btn btn-sm btn-outline-warning"
                                            onclick="return confirm('استعادة القيم الافتراضية لهذه الصفحة؟');">
                                            <i class="fas fa-undo"></i> استعادة الافتراضي
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">عنوان الصفحة (Title)</label>
                                                <input type="text"
                                                    name="pages[{{ $routeName }}][meta_title]"
                                                    class="form-control seo-count-title"
                                                    maxlength="70"
                                                    value="{{ old("pages.{$routeName}.meta_title", $cfg['meta_title'] ?? '') }}"
                                                    data-preview-id="{{ md5($routeName) }}">
                                                <small class="text-muted seo-char-count" data-max="70">0 / 70</small>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Robots</label>
                                                <select name="pages[{{ $routeName }}][robots]" class="form-select">
                                                    @foreach (['index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow'] as $robots)
                                                        <option value="{{ $robots }}" @selected(old("pages.{$routeName}.robots", $cfg['robots'] ?? 'index,follow') === $robots)>{{ $robots }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">الوصف (Meta Description)</label>
                                                <textarea name="pages[{{ $routeName }}][meta_description]"
                                                    class="form-control seo-count-desc"
                                                    rows="3"
                                                    maxlength="320"
                                                    data-preview-id="{{ md5($routeName) }}">{{ old("pages.{$routeName}.meta_description", $cfg['meta_description'] ?? '') }}</textarea>
                                                <small class="text-muted seo-char-count" data-max="160">0 / 160 (موصى به)</small>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">كلمات مفتاحية (اختياري)</label>
                                                <input type="text"
                                                    name="pages[{{ $routeName }}][meta_keywords]"
                                                    class="form-control"
                                                    value="{{ old("pages.{$routeName}.meta_keywords", $cfg['meta_keywords'] ?? '') }}">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Canonical (اختياري — اتركه فارغاً للرابط الحالي)</label>
                                                <input type="url"
                                                    name="pages[{{ $routeName }}][canonical]"
                                                    class="form-control"
                                                    placeholder="{{ config('app.url') }}"
                                                    value="{{ old("pages.{$routeName}.canonical", $cfg['canonical'] ?? '') }}">
                                            </div>
                                        </div>

                                        <hr class="my-4">

                                        <h6 class="mb-3"><i class="fab fa-facebook text-primary"></i> Open Graph</h6>
                                        <div class="row g-3 mb-4">
                                            <div class="col-md-6">
                                                <label class="form-label">OG Title</label>
                                                <input type="text" name="pages[{{ $routeName }}][og_title]" class="form-control"
                                                    value="{{ old("pages.{$routeName}.og_title", $cfg['og_title'] ?? '') }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">OG Type</label>
                                                <input type="text" name="pages[{{ $routeName }}][og_type]" class="form-control"
                                                    value="{{ old("pages.{$routeName}.og_type", $cfg['og_type'] ?? 'website') }}">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">OG Description</label>
                                                <textarea name="pages[{{ $routeName }}][og_description]" class="form-control" rows="2">{{ old("pages.{$routeName}.og_description", $cfg['og_description'] ?? '') }}</textarea>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">صورة OG (1200×630 موصى بها)</label>
                                                <input type="file" name="og_image_{{ $routeName }}" class="form-control" accept="image/*">
                                                @if (!empty($cfg['og_image_url']))
                                                    <div class="mt-2">
                                                        <img src="{{ $cfg['og_image_url'] }}" alt="" class="img-thumbnail" style="max-height: 120px;">
                                                    </div>
                                                    <div class="form-check mt-2">
                                                        <input class="form-check-input" type="checkbox" name="remove_og_image_{{ $routeName }}" value="1" id="remove_og_{{ md5($routeName) }}">
                                                        <label class="form-check-label" for="remove_og_{{ md5($routeName) }}">حذف الصورة الحالية</label>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <h6 class="mb-3"><i class="fab fa-twitter text-info"></i> Twitter Card</h6>
                                        <div class="row g-3 mb-4">
                                            <div class="col-md-6">
                                                <label class="form-label">Twitter Title</label>
                                                <input type="text" name="pages[{{ $routeName }}][twitter_title]" class="form-control"
                                                    value="{{ old("pages.{$routeName}.twitter_title", $cfg['twitter_title'] ?? '') }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Twitter Card</label>
                                                <input type="text" name="pages[{{ $routeName }}][twitter_card]" class="form-control"
                                                    value="{{ old("pages.{$routeName}.twitter_card", $cfg['twitter_card'] ?? 'summary_large_image') }}">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Twitter Description</label>
                                                <textarea name="pages[{{ $routeName }}][twitter_description]" class="form-control" rows="2">{{ old("pages.{$routeName}.twitter_description", $cfg['twitter_description'] ?? '') }}</textarea>
                                            </div>
                                        </div>

                                        <h6 class="mb-3"><i class="fas fa-eye"></i> معاينة</h6>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="seo-preview-google p-3 rounded border">
                                                    <div class="seo-preview-google__url text-success small">{{ config('app.url') }}</div>
                                                    <div class="seo-preview-google__title fw-bold text-primary seo-preview-title-{{ md5($routeName) }}">
                                                        {{ $cfg['meta_title'] ?? 'عنوان الصفحة' }}
                                                    </div>
                                                    <div class="seo-preview-google__desc small text-muted seo-preview-desc-{{ md5($routeName) }}">
                                                        {{ Str::limit($cfg['meta_description'] ?? '', 160) }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="seo-preview-social p-3 rounded border">
                                                    @if (!empty($cfg['og_image_url']))
                                                        <img src="{{ $cfg['og_image_url'] }}" alt="" class="w-100 rounded mb-2" style="max-height: 140px; object-fit: cover;">
                                                    @endif
                                                    <div class="small text-muted">{{ config('seo.organization.name') }}</div>
                                                    <div class="fw-bold seo-preview-title-{{ md5($routeName) }}">{{ $cfg['og_title'] ?? $cfg['meta_title'] ?? '' }}</div>
                                                    <div class="small seo-preview-desc-{{ md5($routeName) }}">{{ Str::limit($cfg['og_description'] ?? $cfg['meta_description'] ?? '', 100) }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> حفظ جميع إعدادات SEO
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
.seo-page-nav .nav-link { border-radius: 0; border-bottom: 1px solid var(--bs-border-color); padding: 0.85rem 1rem; }
.seo-page-nav .nav-link.active { background: rgba(var(--bs-primary-rgb), 0.08); border-inline-start: 3px solid var(--bs-primary); }
.seo-preview-google__title { font-size: 1.1rem; line-height: 1.3; }
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    function updateCount(el) {
        var wrap = el.closest('.col-md-6, .col-12');
        if (!wrap) return;
        var counter = wrap.querySelector('.seo-char-count');
        if (!counter) return;
        var max = parseInt(counter.getAttribute('data-max'), 10) || 160;
        var len = el.value.length;
        counter.textContent = len + ' / ' + max + (max === 160 ? ' (موصى به)' : '');
        counter.classList.toggle('text-danger', len > max);
    }

    document.querySelectorAll('.seo-count-title, .seo-count-desc').forEach(function (el) {
        updateCount(el);
        el.addEventListener('input', function () {
            updateCount(el);
            var id = el.getAttribute('data-preview-id');
            if (!id) return;
            var isTitle = el.classList.contains('seo-count-title');
            document.querySelectorAll('.seo-preview-title-' + id).forEach(function (node) {
                if (isTitle) node.textContent = el.value || 'عنوان الصفحة';
            });
            if (!isTitle) {
                document.querySelectorAll('.seo-preview-desc-' + id).forEach(function (node) {
                    node.textContent = el.value.substring(0, 160) || '';
                });
            }
        });
    });
});
</script>
@endsection
