@php
    $filledCoreCount = collect([
        old('site_name', $settings['site_name'] ?? ''),
        old('footer_description', $settings['footer_description'] ?? ''),
        old('copyright_text', $settings['copyright_text'] ?? ''),
    ])->filter(fn ($value) => filled($value))->count();

    $filledContactCount = collect([
        old('contact_email', $settings['contact_email'] ?? ''),
        old('contact_phone', $settings['contact_phone'] ?? ''),
        old('contact_whatsapp', $settings['contact_whatsapp'] ?? ''),
        old('contact_address', $settings['contact_address'] ?? ''),
        old('contact_work_hours', $settings['contact_work_hours'] ?? ''),
        old('contact_form_action', $settings['contact_form_action'] ?? ''),
    ])->filter(fn ($value) => filled($value))->count();

    $filledSocialCount = collect([
        old('social_facebook', $settings['social_facebook'] ?? ''),
        old('social_youtube', $settings['social_youtube'] ?? ''),
        old('social_instagram', $settings['social_instagram'] ?? ''),
        old('social_linkedin', $settings['social_linkedin'] ?? ''),
        old('social_github', $settings['social_github'] ?? ''),
        old('social_telegram', $settings['social_telegram'] ?? ''),
    ])->filter(fn ($value) => filled($value))->count();
@endphp

@extends('admin.layouts.master')

@section('page-title')
إعدادات الموقع
@stop

@push('styles')
@include('admin.partials.domain-ui-styles')
<style>
.site-settings-hero {
    background:
        radial-gradient(circle at top left, rgba(var(--bs-info-rgb), 0.18), transparent 34%),
        radial-gradient(circle at top right, rgba(var(--bs-primary-rgb), 0.18), transparent 28%),
        linear-gradient(135deg, #ffffff 0%, #f7f9ff 100%);
    border: 1px solid rgba(var(--bs-primary-rgb), 0.08);
    border-radius: 1.25rem;
    padding: 1.5rem;
    margin: 1.5rem 0;
    box-shadow: 0 18px 45px rgba(15, 23, 42, 0.06);
}
.site-settings-hero__eyebrow {
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
.site-settings-hero__title {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}
.site-settings-hero__text {
    max-width: 720px;
    color: #6b7280;
    margin-bottom: 0;
}
.site-settings-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}
.site-settings-kpi {
    background: #fff;
    border-radius: 1rem;
    border: 1px solid rgba(15, 23, 42, 0.06);
    box-shadow: 0 10px 25px rgba(15, 23, 42, 0.05);
    padding: 1rem 1.1rem;
    display: flex;
    gap: 0.85rem;
    align-items: center;
}
.site-settings-kpi__icon {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 0.9rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
}
.site-settings-kpi--primary .site-settings-kpi__icon { background: rgba(var(--bs-primary-rgb), 0.12); color: var(--bs-primary); }
.site-settings-kpi--success .site-settings-kpi__icon { background: rgba(var(--bs-success-rgb), 0.12); color: var(--bs-success); }
.site-settings-kpi--warning .site-settings-kpi__icon { background: rgba(var(--bs-warning-rgb), 0.15); color: #b36b00; }
.site-settings-kpi--info .site-settings-kpi__icon { background: rgba(var(--bs-info-rgb), 0.15); color: #0c7bb3; }
.site-settings-kpi__label {
    color: #6b7280;
    font-size: 0.83rem;
    margin-bottom: 0.2rem;
}
.site-settings-kpi__value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.1;
}
.site-settings-shell {
    background: #fff;
    border: 1px solid rgba(15, 23, 42, 0.06);
    border-radius: 1.2rem;
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.05);
    overflow: hidden;
}
.site-settings-shell__header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid rgba(15, 23, 42, 0.06);
    background: linear-gradient(180deg, rgba(var(--bs-primary-rgb), 0.03), rgba(255,255,255,0));
}
.site-settings-shell__title {
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: 0.2rem;
}
.site-settings-shell__text {
    color: #6b7280;
    font-size: 0.87rem;
    margin-bottom: 0;
}
.site-settings-tabs {
    padding: 0 1rem;
    margin: 0;
    border-bottom: 1px solid rgba(15, 23, 42, 0.06);
    background: #fff;
}
.site-settings-tabs .nav-link {
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
.site-settings-tabs .nav-link.active {
    color: var(--bs-primary);
    background: transparent;
}
.site-settings-tabs .nav-link.active::after {
    content: "";
    position: absolute;
    inset-inline: 0;
    bottom: -1px;
    height: 3px;
    border-radius: 999px 999px 0 0;
    background: var(--bs-primary);
}
.site-settings-content {
    padding: 1.25rem;
    background: linear-gradient(180deg, rgba(var(--bs-primary-rgb), 0.02), rgba(255,255,255,0));
}
.site-settings-section-card {
    border: 1px solid rgba(15, 23, 42, 0.06);
    border-radius: 1rem;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
    overflow: hidden;
}
.site-settings-section-card + .site-settings-section-card {
    margin-top: 1rem;
}
.site-settings-section-card .card-header {
    background: #fff;
    border-bottom: 1px solid rgba(15, 23, 42, 0.06);
    padding: 1rem 1.1rem;
}
.site-settings-section-card .card-body {
    padding: 1.15rem;
}
.site-settings-tip {
    border-radius: 0.9rem;
    background: rgba(var(--bs-primary-rgb), 0.05);
    border: 1px dashed rgba(var(--bs-primary-rgb), 0.18);
    padding: 0.85rem 1rem;
    font-size: 0.86rem;
    color: #475569;
}
.site-settings-sidebar {
    display: grid;
    gap: 1rem;
}
.site-settings-action-card,
.site-settings-google-card,
.site-settings-mini-card {
    border: 1px solid rgba(15, 23, 42, 0.06);
    border-radius: 1rem;
    background: #fff;
    box-shadow: 0 12px 32px rgba(15, 23, 42, 0.05);
}
.site-settings-action-card .card-body,
.site-settings-google-card .card-body,
.site-settings-mini-card .card-body {
    padding: 1rem;
}
.site-settings-google-preview {
    background: linear-gradient(180deg, #fcfcfd 0%, #f8fafc 100%);
    border-radius: 0.85rem;
    border: 1px solid rgba(15, 23, 42, 0.05);
    padding: 1rem;
}
.site-settings-google-preview__label {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    color: #6b7280;
    font-size: 0.78rem;
    margin-bottom: 0.8rem;
}
.site-settings-google-preview__url {
    color: #137333;
    font-size: 0.8rem;
    margin-bottom: 0.25rem;
    word-break: break-all;
}
.site-settings-google-preview__title {
    color: #1a0dab;
    font-size: 1.05rem;
    line-height: 1.35;
    margin-bottom: 0.35rem;
}
.site-settings-google-preview__desc {
    color: #4d5156;
    font-size: 0.85rem;
    line-height: 1.45;
}
.site-settings-variable {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #334155;
    font-size: 0.78rem;
    padding: 0.35rem 0.6rem;
}
.site-settings-social-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
}
.site-settings-social-card {
    border: 1px solid rgba(15, 23, 42, 0.06);
    border-radius: 0.9rem;
    padding: 0.9rem;
    background: #fbfdff;
}
.site-settings-social-card__label {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    font-weight: 600;
    margin-bottom: 0.7rem;
}
.seo-char-hint.text-danger {
    color: var(--bs-danger) !important;
}
@media (max-width: 1199.98px) {
    .site-settings-kpi-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (max-width: 991.98px) {
    .site-settings-sidebar-wrap {
        order: -1;
    }
    .site-settings-social-grid,
    .site-settings-kpi-grid {
        grid-template-columns: 1fr;
    }
    .site-settings-tabs {
        overflow-x: auto;
        flex-wrap: nowrap;
    }
}
</style>
@endpush

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="site-settings-hero">
            <div class="d-md-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <span class="site-settings-hero__eyebrow">
                        <i class="bi bi-gear-wide-connected"></i> إعدادات الموقع
                    </span>
                    <h1 class="site-settings-hero__title">مركز التحكم في الهوية، التواصل، وSEO</h1>
                    <p class="site-settings-hero__text">
                        اضبط معلومات الموقع الأساسية وروابط التواصل وقوالب الصفحة الرئيسية من مكان واحد، مع معاينة سريعة وواجهة متناسقة مثل بقية صفحات اللوحة.
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ url('/') }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm">
                        <i class="fas fa-external-link-alt me-1"></i> معاينة الموقع
                    </a>
                    <a href="{{ route('admin.homepage.seo.index') }}" class="btn btn-light btn-sm">
                        <i class="bi bi-search me-1"></i> SEO المتقدم
                    </a>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="site-settings-kpi-grid">
            <div class="site-settings-kpi site-settings-kpi--primary">
                <span class="site-settings-kpi__icon"><i class="bi bi-building"></i></span>
                <div>
                    <div class="site-settings-kpi__label">بيانات الهوية</div>
                    <div class="site-settings-kpi__value">{{ $filledCoreCount }}/3</div>
                </div>
            </div>
            <div class="site-settings-kpi site-settings-kpi--success">
                <span class="site-settings-kpi__icon"><i class="bi bi-telephone"></i></span>
                <div>
                    <div class="site-settings-kpi__label">بيانات التواصل</div>
                    <div class="site-settings-kpi__value">{{ $filledContactCount }}/6</div>
                </div>
            </div>
            <div class="site-settings-kpi site-settings-kpi--warning">
                <span class="site-settings-kpi__icon"><i class="bi bi-share"></i></span>
                <div>
                    <div class="site-settings-kpi__label">روابط السوشيال</div>
                    <div class="site-settings-kpi__value">{{ $filledSocialCount }}/6</div>
                </div>
            </div>
            <div class="site-settings-kpi site-settings-kpi--info">
                <span class="site-settings-kpi__icon"><i class="bi bi-google"></i></span>
                <div>
                    <div class="site-settings-kpi__label">جاهزية الصفحة الرئيسية</div>
                    <div class="site-settings-kpi__value">{{ filled($homepagePreview['meta_title'] ?? '') && filled($homepagePreview['meta_description'] ?? '') ? 'مكتمل' : 'ناقص' }}</div>
                </div>
            </div>
        </div>

        <form action="{{ route('admin.settings.update') }}" method="post" id="siteSettingsForm">
            @csrf
            @method('PUT')

            <div class="row g-4">
                <div class="col-xl-8 col-lg-7">
                    <div class="site-settings-shell">
                        <div class="site-settings-shell__header">
                            <h2 class="site-settings-shell__title">إعدادات الموقع العامة</h2>
                            <p class="site-settings-shell__text">تنقل بين الأقسام بسهولة، وكل تبويب مرتب بصريًا مثل بقية لوحات الإدارة.</p>
                        </div>

                        <ul class="nav nav-tabs site-settings-tabs" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-site-general" type="button">
                                    <i class="bi bi-building"></i> عام
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-site-contact" type="button">
                                    <i class="bi bi-telephone"></i> التواصل
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-site-seo" type="button">
                                    <i class="bi bi-search"></i> SEO الرئيسية
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-site-social" type="button">
                                    <i class="bi bi-share"></i> السوشيال
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content site-settings-content">
                            <div class="tab-pane fade show active" id="tab-site-general">
                                <div class="card site-settings-section-card">
                                    <div class="card-header">
                                        <span class="card-title mb-0">الهوية العامة للموقع</span>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label">اسم الموقع</label>
                                                <input type="text" name="site_name" class="form-control form-control-lg"
                                                    value="{{ old('site_name', $settings['site_name'] ?? '') }}" placeholder="ClaudSoft Hosting">
                                                <div class="form-text">يُستخدم في الهيدر والفوتر وSEO عبر <code>{site_name}</code></div>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">وصف الفوتر</label>
                                                <textarea name="footer_description" class="form-control" rows="4"
                                                    placeholder="نص وصف الشركة في الفوتر">{{ old('footer_description', $settings['footer_description'] ?? '') }}</textarea>
                                                <div class="form-text">يُستخدم في SEO عبر <code>{site_description}</code></div>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">نص حقوق النشر</label>
                                                <input type="text" name="copyright_text" class="form-control"
                                                    value="{{ old('copyright_text', $settings['copyright_text'] ?? '') }}"
                                                    placeholder="جميع الحقوق محفوظة © {{ date('Y') }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="tab-site-contact">
                                <div class="card site-settings-section-card">
                                    <div class="card-header">
                                        <span class="card-title mb-0">معلومات التواصل</span>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">البريد الإلكتروني</label>
                                                <input type="email" name="contact_email" class="form-control" dir="ltr"
                                                    value="{{ old('contact_email', $settings['contact_email'] ?? '') }}" placeholder="info@example.com">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">رقم الهاتف</label>
                                                <input type="text" name="contact_phone" class="form-control" dir="ltr"
                                                    value="{{ old('contact_phone', $settings['contact_phone'] ?? '') }}" placeholder="+963 XXX XXX XXX">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">واتساب</label>
                                                <input type="text" name="contact_whatsapp" class="form-control" dir="ltr"
                                                    value="{{ old('contact_whatsapp', $settings['contact_whatsapp'] ?? '') }}" placeholder="+963 XXX XXX XXX">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">العنوان / الموقع</label>
                                                <input type="text" name="contact_address" class="form-control"
                                                    value="{{ old('contact_address', $settings['contact_address'] ?? '') }}" placeholder="سوريا">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">ساعات العمل</label>
                                                <input type="text" name="contact_work_hours" class="form-control"
                                                    value="{{ old('contact_work_hours', $settings['contact_work_hours'] ?? '') }}"
                                                    placeholder="السبت - الخميس: 9:00 ص - 6:00 م">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card site-settings-section-card">
                                    <div class="card-header">
                                        <span class="card-title mb-0">نموذج التواصل</span>
                                    </div>
                                    <div class="card-body">
                                        <label class="form-label">رابط إرسال النموذج</label>
                                        <input type="url" name="contact_form_action" class="form-control" dir="ltr"
                                            value="{{ old('contact_form_action', $settings['contact_form_action'] ?? '') }}"
                                            placeholder="https://formspree.io/f/YOUR_FORM_ID">
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="tab-site-seo">
                                <div class="card site-settings-section-card">
                                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                                        <div>
                                            <span class="card-title mb-0">SEO الصفحة الرئيسية</span>
                                            <small class="d-block text-muted">قوالب ذكية تعتمد على بيانات الموقع والتواصل</small>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="settingsHomepageSeoAutofill">
                                            <i class="bi bi-magic"></i> تعبئة تلقائية
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <div class="site-settings-tip mb-3">
                                            استخدم المتغيرات الديناميكية مثل <code>{site_name}</code> و<code>{site_description}</code> ليتم تحديث العنوان والوصف تلقائيًا عند تغيير إعدادات الموقع.
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label d-flex justify-content-between">
                                                    <span>عنوان الصفحة (Title)</span>
                                                    <small class="text-muted seo-char-hint" data-for="settings_homepage_meta_title" data-max="70">0/70</small>
                                                </label>
                                                <input type="text" name="homepage[meta_title]" id="settings_homepage_meta_title"
                                                    class="form-control settings-homepage-seo-field seo-count-field" maxlength="120"
                                                    value="{{ old('homepage.meta_title', $homepageSeo['meta_title'] ?? '') }}">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label d-flex justify-content-between">
                                                    <span>الوصف (Meta Description)</span>
                                                    <small class="text-muted seo-char-hint" data-for="settings_homepage_meta_description" data-max="160">0/160</small>
                                                </label>
                                                <textarea name="homepage[meta_description]" id="settings_homepage_meta_description" rows="3"
                                                    class="form-control settings-homepage-seo-field seo-count-field" maxlength="320">{{ old('homepage.meta_description', $homepageSeo['meta_description'] ?? '') }}</textarea>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">كلمات مفتاحية</label>
                                                <input type="text" name="homepage[meta_keywords]" class="form-control"
                                                    value="{{ old('homepage.meta_keywords', $homepageSeo['meta_keywords'] ?? '') }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">OG Title</label>
                                                <input type="text" name="homepage[og_title]" id="settings_homepage_og_title"
                                                    class="form-control settings-homepage-seo-field"
                                                    value="{{ old('homepage.og_title', $homepageSeo['og_title'] ?? '') }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Robots</label>
                                                <select name="homepage[robots]" class="form-select">
                                                    @foreach (['index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow'] as $robots)
                                                        <option value="{{ $robots }}" @selected(old('homepage.robots', $homepageSeo['robots'] ?? 'index,follow') === $robots)>{{ $robots }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">OG Description</label>
                                                <textarea name="homepage[og_description]" id="settings_homepage_og_description" rows="2"
                                                    class="form-control settings-homepage-seo-field">{{ old('homepage.og_description', $homepageSeo['og_description'] ?? '') }}</textarea>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">H1 احتياطي (عند تعطيل الهيرو)</label>
                                                <input type="text" name="homepage_fallback_h1" id="settings_homepage_fallback_h1"
                                                    class="form-control settings-homepage-seo-field"
                                                    value="{{ old('homepage_fallback_h1', $homepageFallbackH1 ?? '') }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="tab-site-social">
                                <div class="card site-settings-section-card">
                                    <div class="card-header">
                                        <span class="card-title mb-0">روابط التواصل الاجتماعي</span>
                                    </div>
                                    <div class="card-body">
                                        <div class="site-settings-social-grid">
                                            @foreach([
                                                'social_facebook' => ['label' => 'فيسبوك', 'icon' => 'bi-facebook'],
                                                'social_youtube' => ['label' => 'يوتيوب', 'icon' => 'bi-youtube'],
                                                'social_instagram' => ['label' => 'انستغرام', 'icon' => 'bi-instagram'],
                                                'social_linkedin' => ['label' => 'لينكد إن', 'icon' => 'bi-linkedin'],
                                                'social_github' => ['label' => 'جيت هاب', 'icon' => 'bi-github'],
                                                'social_telegram' => ['label' => 'تليجرام', 'icon' => 'bi-telegram'],
                                            ] as $key => $meta)
                                            <div class="site-settings-social-card">
                                                <label class="site-settings-social-card__label" for="{{ $key }}">
                                                    <i class="bi {{ $meta['icon'] }}"></i> {{ $meta['label'] }}
                                                </label>
                                                <input type="url" id="{{ $key }}" name="{{ $key }}" class="form-control" dir="ltr"
                                                    value="{{ old($key, $settings[$key] ?? '') }}" placeholder="https://">
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-lg-5 site-settings-sidebar-wrap">
                    <div class="site-settings-sidebar sticky-top" style="top: 90px;">
                        <div class="card site-settings-action-card">
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save me-1"></i> حفظ الإعدادات
                                    </button>
                                    <a href="{{ route('admin.dashboard') }}" class="btn btn-light">إلغاء</a>
                                </div>
                            </div>
                        </div>

                        <div class="card site-settings-google-card">
                            <div class="card-body">
                                <div class="site-settings-google-preview">
                                    <div class="site-settings-google-preview__label">
                                        <i class="bi bi-google"></i> معاينة نتيجة البحث
                                    </div>
                                    <div class="site-settings-google-preview__url" id="settings_homepage_preview_url">{{ config('app.url') }}</div>
                                    <div class="site-settings-google-preview__title" id="settings_homepage_preview_title">{{ $homepagePreview['meta_title'] ?? '' }}</div>
                                    <div class="site-settings-google-preview__desc" id="settings_homepage_preview_desc">{{ $homepagePreview['meta_description'] ?? '' }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="card site-settings-mini-card">
                            <div class="card-body">
                                <h6 class="mb-3">متغيرات SEO</h6>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach (['{site_name}', '{site_description}', '{email}', '{phone}', '{address}'] as $var)
                                        <span class="site-settings-variable font-monospace">{{ $var }}</span>
                                    @endforeach
                                </div>
                                <p class="text-muted small mb-0 mt-3">تتغيّر تلقائيًا حسب بيانات الموقع الحالية.</p>
                            </div>
                        </div>

                        <div class="card site-settings-mini-card">
                            <div class="card-body">
                                <h6 class="mb-3">روابط سريعة</h6>
                                <div class="d-grid gap-2">
                                    <a href="{{ route('admin.homepage.seo.index') }}" class="btn btn-outline-primary text-start">
                                        <i class="bi bi-sliders me-1"></i> لوحة SEO المتقدمة
                                    </a>
                                    <a href="{{ route('admin.homepage.hero.index') }}" class="btn btn-outline-secondary text-start">
                                        <i class="bi bi-image me-1"></i> إعدادات الهيرو
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const siteName = document.querySelector('[name="site_name"]');
    const siteDesc = document.querySelector('[name="footer_description"]');
    const email = document.querySelector('[name="contact_email"]');
    const phone = document.querySelector('[name="contact_phone"]');
    const address = document.querySelector('[name="contact_address"]');

    function replacePlaceholders(text) {
        const map = {
            '{site_name}': siteName?.value || '',
            '{organization}': siteName?.value || '',
            '{site_description}': (siteDesc?.value || '').substring(0, 160),
            '{email}': email?.value || '',
            '{phone}': phone?.value || '',
            '{address}': address?.value || '',
            '{site_url}': '{{ config('app.url') }}',
            '{legal_name}': siteName?.value || '',
        };
        let out = text || '';
        Object.keys(map).forEach(function (key) {
            out = out.split(key).join(map[key]);
        });
        return out;
    }

    function updateCharHints() {
        document.querySelectorAll('.seo-char-hint').forEach(function (hint) {
            const id = hint.getAttribute('data-for');
            const max = parseInt(hint.getAttribute('data-max'), 10) || 160;
            const field = document.getElementById(id);
            if (!field) return;
            const len = field.value.length;
            hint.textContent = len + '/' + max;
            hint.classList.toggle('text-danger', len > max);
        });
    }

    function updatePreview() {
        const title = document.getElementById('settings_homepage_meta_title');
        const desc = document.getElementById('settings_homepage_meta_description');
        const titleEl = document.getElementById('settings_homepage_preview_title');
        const descEl = document.getElementById('settings_homepage_preview_desc');
        if (titleEl && title) titleEl.textContent = replacePlaceholders(title.value) || 'عنوان الصفحة';
        if (descEl && desc) descEl.textContent = replacePlaceholders(desc.value) || 'وصف الصفحة';
        updateCharHints();
    }

    document.querySelectorAll('.settings-homepage-seo-field, .seo-count-field').forEach(function (el) {
        el.addEventListener('input', updatePreview);
    });
    [siteName, siteDesc, email, phone, address].forEach(function (el) {
        el?.addEventListener('input', updatePreview);
    });

    document.getElementById('settingsHomepageSeoAutofill')?.addEventListener('click', function () {
        const title = document.getElementById('settings_homepage_meta_title');
        const desc = document.getElementById('settings_homepage_meta_description');
        const ogTitle = document.getElementById('settings_homepage_og_title');
        const ogDesc = document.getElementById('settings_homepage_og_description');
        const h1 = document.getElementById('settings_homepage_fallback_h1');
        if (title && !title.value) title.value = '{site_name} | باقات استضافة ودعم فني';
        if (desc && !desc.value) desc.value = '{site_name} — {site_description}';
        if (ogTitle && !ogTitle.value) ogTitle.value = '{site_name} | استضافة مواقع سحابية';
        if (ogDesc && !ogDesc.value) ogDesc.value = '{site_description}';
        if (h1 && !h1.value) h1.value = '{site_name} | استضافة مواقع سحابية';
        updatePreview();
    });

    updatePreview();
});
</script>
@endsection
