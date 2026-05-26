@extends('frontend.layouts.master')

@section('page-title')
إدارة السيرفرات | استضافة كلاودسوفت
@endsection

@section('meta-description')
إدارة السيرفرات — إعداد وإدارة خوادم Linux، Nginx أو Apache، قواعد البيانات، SSL والنسخ الاحتياطي. استضافة سحابية و VPS مع مراقبة وصيانة دورية.
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/devicon@2.15.1/devicon.min.css" crossorigin="anonymous">
@endpush

@section('content')
    <!-- ============ SERVICE BANNER ============ -->
    <section class="page-banner page-banner-about">
        <div class="page-banner-overlay"></div>
        <div class="container position-relative">
            <div class="page-banner-content animate-on-scroll">
                <div class="page-banner-icon"><i class="fas fa-server"></i></div>
                <h1 class="page-banner-title">إدارة <span>السيرفرات</span></h1>
                <p class="page-banner-desc">إعداد وإدارة الخوادم، الاستضافة، والنشر على Linux والخدمات السحابية لتشغيل مستقر وآمن</p>
                <nav class="page-banner-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ url('/') }}">الرئيسية</a>
                    <span class="page-banner-sep">/</span>
                    <a href="{{ route('frontend.about') }}#specialties">التخصصات</a>
                    <span class="page-banner-sep">/</span>
                    <span>إدارة السيرفرات</span>
                </nav>
            </div>
        </div>
        <div class="page-banner-shape"></div>
    </section>

    <!-- ============ SERVICE INTRO ============ -->
    <section class="section-padding security-intro-section">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <div class="service-detail-intro animate-on-scroll">
                        <span class="section-badge">نظرة عامة</span>
                        <h2 class="service-detail-heading">خوادم موثوقة لتشغيل مواقعك وتطبيقاتك</h2>
                        <p class="service-detail-lead">
                            نقدّم في كلاودسوفت خدمات إعداد وإدارة السيرفرات لتشغيل مواقعك وتطبيقاتك بشكل مستقر وآمن. نعمل على خوادم Linux (Ubuntu و Debian وغيرها)، إعداد Nginx أو Apache، قواعد البيانات، وشهادات SSL، مع مراقبة الأداء والنسخ الاحتياطي.
                        </p>
                        <p class="service-detail-text">
                            يشمل العمل اختيار الاستضافة المناسبة (VPS أو سحابة)، تثبيت البيئة البرمجية (PHP، Node.js، Python حسب المشروع)، ضبط الجدار الناري والأمان، وإرشادك لصيانة السيرفر أو تنفيذ الصيانة نيابة عنك.
                        </p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="glass-panel service-detail-feature-list animate-on-scroll">
                        <h4 class="service-detail-feature-list-title"><i class="fas fa-check-circle"></i> أبرز ما يميز الخدمة</h4>
                        <ul class="service-detail-feature-list-ul">
                            <li><i class="fas fa-chevron-left"></i> إعداد خوادم Linux (Ubuntu / Debian)</li>
                            <li><i class="fas fa-chevron-left"></i> خوادم ويب Nginx أو Apache مع PHP/Node</li>
                            <li><i class="fas fa-chevron-left"></i> إعداد قواعد البيانات وإدارة النسخ الاحتياطي</li>
                            <li><i class="fas fa-chevron-left"></i> SSL و HTTPS وتأمين أساسي للسيرفر</li>
                            <li><i class="fas fa-chevron-left"></i> نطاق و DNS وإرشادات الاستضافة</li>
                            <li><i class="fas fa-chevron-left"></i> مراقبة وصيانة دورية (اختياري)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============ WHAT WE OFFER ============ -->
    <section class="section-padding" style="background: var(--clr-bg-secondary);">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge">تفاصيل الخدمة</span>
                <h2>ما الذي يشمل عليه العمل؟</h2>
                <p>مراحل ومنتجات واضحة في كل مشروع إدارة سيرفرات</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-desktop"></i></div>
                        <h5>إعداد السيرفر (Linux)</h5>
                        <p>تثبيت وتجهيز نظام تشغيل Linux، تحديثات أمنية، وإنشاء مستخدمين وصلاحيات أساسية.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-globe"></i></div>
                        <h5>خادم الويب (Nginx / Apache)</h5>
                        <p>تثبيت وإعداد Nginx أو Apache مع PHP أو Node.js أو Python حسب متطلبات المشروع.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-database"></i></div>
                        <h5>قواعد البيانات</h5>
                        <p>تثبيت MySQL أو PostgreSQL أو MongoDB وضبط النسخ الاحتياطي التلقائي.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-lock"></i></div>
                        <h5>SSL والأمان</h5>
                        <p>تركيب شهادات SSL (Let's Encrypt أو مدفوعة) وضبط الجدار الناري والصلاحيات.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-upload"></i></div>
                        <h5>النشر والنطاق</h5>
                        <p>رفع المشروع على السيرفر، ربط النطاق (Domain) وإعداد سجلات DNS.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-tools"></i></div>
                        <h5>الصيانة والمراقبة</h5>
                        <p>مراقبة الأداء، معالجة الأعطال، وتقديم صيانة دورية أو إرشادات للإدارة الذاتية.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('frontend.partials.servers-tools-catalog')

    <!-- ============ RELATED SERVICES ============ -->
    <section class="section-padding service-related-section" style="background: var(--clr-bg-secondary);">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge">تخصصات أخرى</span>
                <h2>خدمات وتخصصات ذات صلة</h2>
                <p>اطّلع على مجالات أخرى نقدم فيها الاستشارة والتنفيذ</p>
            </div>
            <div class="row g-4">
                @php
                    $relatedServices = [
                        [
                            'url' => route('frontend.service-detail-web'),
                            'icon' => 'fas fa-globe',
                            'title' => 'تطوير تطبيقات الويب',
                            'desc' => 'مواقع وتطبيقات ويب حديثة',
                            'accent' => '#0057B8',
                        ],
                        [
                            'url' => route('frontend.service-detail-security'),
                            'icon' => 'fas fa-shield-alt',
                            'title' => 'أمن المعلومات',
                            'desc' => 'حماية الأنظمة والبيانات',
                            'accent' => '#6366f1',
                        ],
                        [
                            'url' => route('frontend.service-detail-devops'),
                            'icon' => 'fas fa-infinity',
                            'title' => 'DevOps',
                            'desc' => 'CI/CD، حاويات، سحابة',
                            'accent' => '#2496ed',
                        ],
                        [
                            'url' => route('frontend.packages'),
                            'icon' => 'fas fa-box-open',
                            'title' => 'باقات الاستضافة',
                            'desc' => 'خطط استضافة مرنة وآمنة',
                            'accent' => '#10b981',
                        ],
                    ];
                @endphp
                @foreach ($relatedServices as $i => $service)
                    <div class="col-sm-6 col-lg-3">
                        <a href="{{ $service['url'] }}"
                            class="service-related-card animate-on-scroll animate-delay-{{ ($i % 4) + 1 }}"
                            style="--related-accent: {{ $service['accent'] }}">
                            <span class="service-related-card__icon" aria-hidden="true">
                                <i class="{{ $service['icon'] }}"></i>
                            </span>
                            <h6>{{ $service['title'] }}</h6>
                            <p>{{ $service['desc'] }}</p>
                            <span class="service-related-card__link">
                                اعرف المزيد <i class="fas fa-arrow-left"></i>
                            </span>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- ============ CTA ============ -->
    <section class="cta-section">
        <div class="container animate-on-scroll">
            <h2>هل تحتاج إعداد أو إدارة سيرفر لمشروعك؟</h2>
            <p>تواصل معنا الآن ونناقش متطلباتك ونقدّم لك عرضاً مناسباً لاحتياجاتك</p>
            <a href="{{ route('frontend.contact') }}" class="btn-light-custom">
                <i class="fas fa-paper-plane"></i> تواصل معنا
            </a>
        </div>
    </section>
@endsection
