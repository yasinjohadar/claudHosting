@extends('frontend.layouts.master')

@section('page-title')
تطوير تطبيقات الويب | استضافة كلاودسوفت
@endsection

@section('meta-description')
تطوير تطبيقات الويب — تصميم وتطوير مواقع وتطبيقات ويب حديثة ومتجاوبة بأحدث التقنيات: React، Laravel، Node.js و TypeScript. واجهات احترافية، أداء عالٍ، ودعم SEO وأمان.
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
                <div class="page-banner-icon"><i class="fas fa-globe"></i></div>
                <h1 class="page-banner-title">تطوير تطبيقات <span>الويب</span></h1>
                <p class="page-banner-desc">تصميم وتطوير مواقع وتطبيقات ويب حديثة ومتجاوبة واحترافية بأحدث التقنيات والمعايير</p>
                <nav class="page-banner-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ url('/') }}">الرئيسية</a>
                    <span class="page-banner-sep">/</span>
                    <a href="{{ route('frontend.about') }}#specialties">التخصصات</a>
                    <span class="page-banner-sep">/</span>
                    <span>تطوير تطبيقات الويب</span>
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
                        <h2 class="service-detail-heading">تطوير ويب متكامل من الفكرة إلى الإطلاق</h2>
                        <p class="service-detail-lead">
                            نقدّم في كلاودسوفت حلولاً متكاملة لتطوير تطبيقات الويب — من التصميم والواجهات الأمامية إلى الـ API وقواعد البيانات والنشر على خوادم موثوقة. سواء موقعاً تعريفياً، متجراً إلكترونياً، أو منصة ويب معقدة، نبني مشروعك بأحدث التقنيات مع تركيز على الأداء والأمان وتجربة المستخدم.
                        </p>
                        <p class="service-detail-text">
                            نستخدم React و Vue و Laravel و Node.js و TypeScript لضمان كود قابل للصيانة والتوسع. جميع المشاريع متجاوبة بالكامل، محسّنة لمحركات البحث (SEO)، ومجهّزة بشهادات SSL وبيئة استضافة آمنة.
                        </p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="glass-panel service-detail-feature-list animate-on-scroll">
                        <h4 class="service-detail-feature-list-title"><i class="fas fa-check-circle"></i> أبرز ما يميز الخدمة</h4>
                        <ul class="service-detail-feature-list-ul">
                            <li><i class="fas fa-chevron-left"></i> واجهات حديثة ومتجاوبة (Responsive)</li>
                            <li><i class="fas fa-chevron-left"></i> أداء عالٍ وسرعة تحميل محسّنة</li>
                            <li><i class="fas fa-chevron-left"></i> دعم محركات البحث (SEO)</li>
                            <li><i class="fas fa-chevron-left"></i> أمان وحماية للبيانات</li>
                            <li><i class="fas fa-chevron-left"></i> صيانة ودعم فني بعد التسليم</li>
                            <li><i class="fas fa-chevron-left"></i> كود نظيف وقابل للتطوير</li>
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
                <p>مراحل ومنتجات واضحة نلتزم بها في كل مشروع ويب</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-palette"></i></div>
                        <h5>تصميم الواجهات (UI/UX)</h5>
                        <p>تصميم واجهات مستخدم جذابة وسهلة الاستخدام مع مراعاة تجربة المستخدم والألوان والهوية البصرية.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-code"></i></div>
                        <h5>التطوير الأمامي (Frontend)</h5>
                        <p>برمجة الواجهة باستخدام HTML5, CSS3, JavaScript و إطارات عمل مثل React أو Vue لتفاعلية عالية.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-server"></i></div>
                        <h5>التطوير الخلفي (Backend)</h5>
                        <p>بناء الخوادم وواجهات الـ API وقواعد البيانات لتشغيل التطبيق وإدارة المحتوى والبيانات.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-mobile-alt"></i></div>
                        <h5>التجاوب مع الجوال</h5>
                        <p>ضمان عرض مثالي على الهواتف والأجهزة اللوحية مع اختبارات على أحجام شاشات مختلفة.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-rocket"></i></div>
                        <h5>النشر والاستضافة</h5>
                        <p>رفع المشروع على سيرفر موثوق وإعداد النطاق وشهادة SSL مع إرشادات الصيانة.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-headset"></i></div>
                        <h5>الدعم والتدريب</h5>
                        <p>تدريبك على إدارة المحتوى وصيانة الموقع مع دعم فني لفترة محددة بعد التسليم.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('frontend.partials.webapp-tools-catalog')

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
                            'url' => route('frontend.service-detail-mobile'),
                            'icon' => 'fas fa-mobile-alt',
                            'title' => 'تطبيقات الجوال',
                            'desc' => 'تطوير تطبيقات أندرويد و iOS',
                            'accent' => '#2E9AD0',
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
            <h2>هل تحتاج تطوير موقع أو تطبيق ويب لمشروعك؟</h2>
            <p>تواصل معنا الآن ونناقش متطلباتك ونقدّم لك عرضاً مناسباً لاحتياجاتك</p>
            <a href="{{ route('frontend.contact') }}" class="btn-light-custom">
                <i class="fas fa-paper-plane"></i> تواصل معنا
            </a>
        </div>
    </section>
@endsection
