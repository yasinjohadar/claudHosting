@php
    $activeTab = request('tab', 'pages');
    $pagesCount = count($pages ?? []);
    $globalOgReady = !empty($global['default_og_image_url'] ?? null) ? 'جاهز' : 'افتراضي';
    $robotsEnabled = (($global['robots']['enabled'] ?? true) && ($global['sitemap']['enabled'] ?? true)) ? 'مفعّل' : 'مخصص';
@endphp

@extends('admin.layouts.master')

@section('page-title')
إعدادات SEO
@stop

@push('styles')
@include('admin.partials.domain-ui-styles')
<style>
.seo-settings-hero {
    background:
        radial-gradient(circle at top left, rgba(var(--bs-success-rgb), 0.16), transparent 30%),
        radial-gradient(circle at top right, rgba(var(--bs-primary-rgb), 0.18), transparent 26%),
        linear-gradient(135deg, #ffffff 0%, #f7f9ff 100%);
    border: 1px solid rgba(var(--bs-primary-rgb), 0.08);
    border-radius: 1.25rem;
    padding: 1.5rem;
    margin: 1.5rem 0;
    box-shadow: 0 18px 45px rgba(15, 23, 42, 0.06);
}
.seo-settings-hero__eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.35rem 0.75rem;
    border-radius: 999px;
    background: rgba(var(--bs-primary-rgb), 0.08);
    color: var(--bs-primary);
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 0.8rem;
}
.seo-settings-hero__title {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}
.seo-settings-hero__text {
    max-width: 760px;
    color: #6b7280;
    margin-bottom: 0;
}
.seo-settings-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}
.seo-settings-kpi {
    background: #fff;
    border-radius: 1rem;
    border: 1px solid rgba(15, 23, 42, 0.06);
    box-shadow: 0 10px 25px rgba(15, 23, 42, 0.05);
    padding: 1rem 1.1rem;
    display: flex;
    gap: 0.85rem;
    align-items: center;
}
.seo-settings-kpi__icon {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 0.9rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
}
.seo-settings-kpi--primary .seo-settings-kpi__icon { background: rgba(var(--bs-primary-rgb), 0.12); color: var(--bs-primary); }
.seo-settings-kpi--success .seo-settings-kpi__icon { background: rgba(var(--bs-success-rgb), 0.12); color: var(--bs-success); }
.seo-settings-kpi--warning .seo-settings-kpi__icon { background: rgba(var(--bs-warning-rgb), 0.15); color: #b36b00; }
.seo-settings-kpi--info .seo-settings-kpi__icon { background: rgba(var(--bs-info-rgb), 0.15); color: #0c7bb3; }
.seo-settings-kpi__label {
    color: #6b7280;
    font-size: 0.83rem;
    margin-bottom: 0.2rem;
}
.seo-settings-kpi__value {
    font-size: 1.22rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.1;
}
.seo-settings-shell {
    background: #fff;
    border: 1px solid rgba(15, 23, 42, 0.06);
    border-radius: 1.2rem;
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.05);
    overflow: hidden;
}
.seo-settings-shell__header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid rgba(15, 23, 42, 0.06);
    background: linear-gradient(180deg, rgba(var(--bs-primary-rgb), 0.03), rgba(255,255,255,0));
}
.seo-settings-shell__title {
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: 0.2rem;
}
.seo-settings-shell__text {
    color: #6b7280;
    font-size: 0.87rem;
    margin-bottom: 0;
}
.seo-settings-tabs {
    padding: 0 1rem;
    margin: 0;
    border-bottom: 1px solid rgba(15, 23, 42, 0.06);
    background: #fff;
}
.seo-settings-tabs .nav-link {
    border: 0;
    border-radius: 0;
    padding: 1rem 1rem 0.85rem;
    color: #6b7280;
    font-weight: 600;
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
}
.seo-settings-tabs .nav-link.active {
    color: var(--bs-primary);
    background: transparent;
}
.seo-settings-tabs .nav-link.active::after {
    content: "";
    position: absolute;
    inset-inline: 0;
    bottom: -1px;
    height: 3px;
    border-radius: 999px 999px 0 0;
    background: var(--bs-primary);
}
.seo-settings-content {
    padding: 1.25rem;
    background: linear-gradient(180deg, rgba(var(--bs-primary-rgb), 0.02), rgba(255,255,255,0));
}
.seo-settings-content .seo-page-nav .nav-link {
    --bs-nav-pills-link-active-color: #0f172a;
    --bs-nav-pills-link-active-bg: rgba(var(--bs-primary-rgb), 0.08);
    border-radius: 0;
    border-bottom: 1px solid var(--bs-border-color);
    padding: 0.85rem 1rem;
    background: #fff;
    color: #0f172a;
    text-align: right;
}
.seo-settings-content .seo-page-nav .nav-link.active {
    background: rgba(var(--bs-primary-rgb), 0.08);
    border-inline-start: 3px solid var(--bs-primary);
    color: var(--bs-primary);
}
.seo-settings-content .seo-page-nav .nav-link small {
    color: #64748b !important;
}
.seo-settings-content .seo-page-nav .nav-link.active small {
    color: #475569 !important;
}
.seo-settings-content .seo-page-nav {
    background: #fff;
}
.seo-settings-content .seo-page-nav .nav-link:hover {
    background: #f8fbff;
}
.seo-preview-google__title {
    font-size: 1.1rem;
    line-height: 1.3;
}
@media (max-width: 1199.98px) {
    .seo-settings-kpi-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (max-width: 991.98px) {
    .seo-settings-kpi-grid {
        grid-template-columns: 1fr;
    }
    .seo-settings-tabs {
        overflow-x: auto;
        flex-wrap: nowrap;
    }
}
</style>
@endpush

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="seo-settings-hero">
            <div class="d-md-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <span class="seo-settings-hero__eyebrow">
                        <i class="bi bi-search"></i> إعدادات SEO
                    </span>
                    <h1 class="seo-settings-hero__title">لوحة تحسين الظهور في محركات البحث</h1>
                    <p class="seo-settings-hero__text">
                        أدر عناوين الصفحات والوصف والـ Open Graph وملفات `robots` و`sitemap` من واجهة أوضح وبهوية بصرية متناسقة مع بقية صفحات الإدارة.
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ url('/') }}" target="_blank" class="btn btn-primary btn-sm">
                        <i class="fas fa-external-link-alt me-1"></i> معاينة الموقع
                    </a>
                    <a href="{{ route('admin.settings.index') }}" class="btn btn-light btn-sm">
                        <i class="bi bi-gear me-1"></i> إعدادات الموقع
                    </a>
                </div>
            </div>
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

        <div class="seo-settings-kpi-grid">
            <div class="seo-settings-kpi seo-settings-kpi--primary">
                <span class="seo-settings-kpi__icon"><i class="bi bi-files"></i></span>
                <div>
                    <div class="seo-settings-kpi__label">الصفحات القابلة للتخصيص</div>
                    <div class="seo-settings-kpi__value">{{ $pagesCount }}</div>
                </div>
            </div>
            <div class="seo-settings-kpi seo-settings-kpi--success">
                <span class="seo-settings-kpi__icon"><i class="bi bi-grid"></i></span>
                <div>
                    <div class="seo-settings-kpi__label">القسم النشط</div>
                    <div class="seo-settings-kpi__value">
                        {{ $activeTab === 'pages' ? 'الصفحات' : ($activeTab === 'general' ? 'عام' : ($activeTab === 'blog' ? 'المدونة' : 'Robots')) }}
                    </div>
                </div>
            </div>
            <div class="seo-settings-kpi seo-settings-kpi--warning">
                <span class="seo-settings-kpi__icon"><i class="bi bi-image"></i></span>
                <div>
                    <div class="seo-settings-kpi__label">صورة OG الافتراضية</div>
                    <div class="seo-settings-kpi__value">{{ $globalOgReady }}</div>
                </div>
            </div>
            <div class="seo-settings-kpi seo-settings-kpi--info">
                <span class="seo-settings-kpi__icon"><i class="bi bi-shield-check"></i></span>
                <div>
                    <div class="seo-settings-kpi__label">Robots / Sitemap</div>
                    <div class="seo-settings-kpi__value">{{ $robotsEnabled }}</div>
                </div>
            </div>
        </div>

        <div class="seo-settings-shell">
            <div class="seo-settings-shell__header">
                <h2 class="seo-settings-shell__title">مركز إدارة الـ SEO</h2>
                <p class="seo-settings-shell__text">اختر القسم المناسب ثم عدّل الحقول من نفس الواجهة مع تنظيم بصري أوضح.</p>
            </div>

            <ul class="nav nav-tabs seo-settings-tabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link {{ $activeTab === 'pages' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#seo-main-pages" type="button">
                        <i class="bi bi-files"></i> الصفحات
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link {{ $activeTab === 'general' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#seo-main-general" type="button">
                        <i class="bi bi-sliders"></i> عام
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link {{ $activeTab === 'blog' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#seo-main-blog" type="button">
                        <i class="bi bi-journal-text"></i> المدونة
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link {{ $activeTab === 'robots' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#seo-main-robots" type="button">
                        <i class="bi bi-diagram-3"></i> Robots & Sitemap
                    </button>
                </li>
            </ul>

            <div class="tab-content seo-settings-content">
                <div class="tab-pane fade {{ $activeTab === 'pages' ? 'show active' : '' }}" id="seo-main-pages">
                    @include('admin.homepage.seo.partials.pages-tab')
                </div>
                <div class="tab-pane fade {{ $activeTab === 'general' ? 'show active' : '' }}" id="seo-main-general">
                    @include('admin.homepage.seo.partials.global-general-tab')
                </div>
                <div class="tab-pane fade {{ $activeTab === 'blog' ? 'show active' : '' }}" id="seo-main-blog">
                    @include('admin.homepage.seo.partials.global-blog-tab')
                </div>
                <div class="tab-pane fade {{ $activeTab === 'robots' ? 'show active' : '' }}" id="seo-main-robots">
                    @include('admin.homepage.seo.partials.global-robots-tab')
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@include('admin.homepage.seo.partials.pages-tab-scripts')
@endsection
