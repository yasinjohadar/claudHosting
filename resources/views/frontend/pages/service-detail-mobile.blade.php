@extends('frontend.layouts.master')

@section('page-title')
تطبيقات الجوال | Dart & Flutter | استضافة كلاودسوفت
@endsection

@section('meta-description')
تطوير تطبيقات الجوال — بناء تطبيقات أندرويد و iOS بـ Dart و Flutter. كود واحد لمنصتين، واجهات Material و Cupertino، أداء قريب من الأصلي، ونشر على المتاجر.
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
                <div class="page-banner-icon"><i class="fas fa-mobile-alt"></i></div>
                <h1 class="page-banner-title">تطبيقات <span>الجوال</span></h1>
                <p class="page-banner-desc">تطوير تطبيقات أندرويد و iOS باستخدام Dart و Flutter — كود واحد، منصتان، تجربة احترافية</p>
                <nav class="page-banner-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ url('/') }}">الرئيسية</a>
                    <span class="page-banner-sep">/</span>
                    <a href="{{ route('frontend.about') }}#specialties">التخصصات</a>
                    <span class="page-banner-sep">/</span>
                    <span>تطبيقات الجوال</span>
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
                        <h2 class="service-detail-heading">تطبيقات جوال احترافية من الفكرة إلى المتجر</h2>
                        <p class="service-detail-lead">
                            نقدّم في كلاودسوفت حلولاً متكاملة لتطوير تطبيقات الجوال لنظامي أندرويد و iOS باستخدام Flutter و Dart. نكتب الكود مرة واحدة وننشره على المنصتين مع الحفاظ على أداء عالٍ وواجهات أصلية (Material و Cupertino)، مما يوفر وقتك وتكلفة المشروع.
                        </p>
                        <p class="service-detail-text">
                            نعتمد على GetX و Provider لإدارة الحالة، والتكامل مع Firebase و REST API. يشمل العمل التصميم، التطوير، الاختبار على الأجهزة، والنشر على Google Play و App Store مع دعم فني بعد التسليم.
                        </p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="glass-panel service-detail-feature-list animate-on-scroll">
                        <h4 class="service-detail-feature-list-title"><i class="fas fa-check-circle"></i> أبرز ما يميز الخدمة</h4>
                        <ul class="service-detail-feature-list-ul">
                            <li><i class="fas fa-chevron-left"></i> منصة واحدة لأندرويد و iOS (Cross-Platform)</li>
                            <li><i class="fas fa-chevron-left"></i> أداء قريب من التطبيقات الأصلية (Native)</li>
                            <li><i class="fas fa-chevron-left"></i> واجهات Material و Cupertino جاهزة</li>
                            <li><i class="fas fa-chevron-left"></i> كود نظيف وقابل لإعادة الاستخدام</li>
                            <li><i class="fas fa-chevron-left"></i> نشر على متاجر التطبيقات مع إرشاداتك</li>
                            <li><i class="fas fa-chevron-left"></i> دعم فني وصيانة بعد التسليم</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============ WHAT WE OFFER (تفاصيل الخدمة) ============ -->
    <section class="section-padding" style="background: var(--clr-bg-secondary);">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge">تفاصيل الخدمة</span>
                <h2>ما الذي يشمل عليه العمل؟</h2>
                <p>مراحل ومنتجات واضحة نلتزم بها في كل مشروع تطبيق جوال</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-palette"></i></div>
                        <h5>تصميم واجهات الجوال (UI/UX)</h5>
                        <p>تصميم شاشات التطبيق مع مراعاة معايير أندرويد و iOS وتجربة المستخدم والألوان والهوية البصرية.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-code"></i></div>
                        <h5>التطوير بـ Flutter و Dart</h5>
                        <p>برمجة التطبيق باستخدام Flutter و Dart مع Widgets جاهزة وإدارة حالة منظمة (GetX / Provider).</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-database"></i></div>
                        <h5>التكامل مع الخلفية والـ API</h5>
                        <p>ربط التطبيق بـ REST API أو Firebase (مصادقة، قاعدة بيانات، إشعارات) وإدارة البيانات محلياً.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-mobile-alt"></i></div>
                        <h5>الاختبار على الأجهزة</h5>
                        <p>اختبار التطبيق على أحجام شاشات مختلفة وأجهزة أندرويد و iOS قبل النشر.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-rocket"></i></div>
                        <h5>النشر على المتاجر</h5>
                        <p>إعداد الحزم (APK/AAB لأندرويد و IPA لـ iOS) وإرشادك لنشر التطبيق على Google Play و App Store.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-headset"></i></div>
                        <h5>الدعم والتدريب</h5>
                        <p>تدريبك على تعديل المحتوى وصيانة التطبيق مع دعم فني لفترة محددة بعد التسليم.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('frontend.partials.mobile-tools-catalog')

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
                            'url' => route('frontend.service-detail-servers'),
                            'icon' => 'fas fa-server',
                            'title' => 'إدارة السيرفرات',
                            'desc' => 'إعداد وإدارة الخوادم',
                            'accent' => '#10b981',
                        ],
                        [
                            'url' => route('frontend.service-detail-devops'),
                            'icon' => 'fas fa-infinity',
                            'title' => 'DevOps',
                            'desc' => 'CI/CD، حاويات، سحابة',
                            'accent' => '#2496ed',
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
            <h2>هل تحتاج تطبيق جوال لمشروعك؟</h2>
            <p>تواصل معنا الآن ونناقش متطلباتك ونقدّم لك عرضاً مناسباً لاحتياجاتك</p>
            <a href="{{ route('frontend.contact') }}" class="btn-light-custom">
                <i class="fas fa-paper-plane"></i> تواصل معنا
            </a>
        </div>
    </section>
@endsection
