@extends('frontend.layouts.master')

@section('page-title')
إدارة السيرفرات | استضافة كلاودسوفت
@endsection

@section('meta-description')
إدارة السيرفرات — إعداد وإدارة خوادم Linux، Nginx أو Apache، قواعد البيانات، SSL والنسخ الاحتياطي. استضافة سحابية و VPS مع مراقبة وصيانة دورية.
@endsection

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
    <section class="section-padding">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <div class="service-detail-intro animate-on-scroll">
                        <span class="section-badge">نظرة عامة</span>
                        <h2 class="service-detail-heading">ماذا نقدم في هذه الخدمة؟</h2>
                        <p class="service-detail-lead">
                            أقدم خدمات إعداد وإدارة السيرفرات لتشغيل مواقعك وتطبيقاتك بشكل مستقر وآمن. نعمل على خوادم Linux (Ubuntu, CentOS وغيرها)، إعداد خوادم الويب (Nginx أو Apache)، قواعد البيانات، وشهادات SSL، مع مراقبة الأداء والنسخ الاحتياطي.
                        </p>
                        <p class="service-detail-text">
                            يشمل العمل اختيار الاستضافة المناسبة (VPS أو سحابة)، تثبيت البيئة البرمجية (Node.js، PHP، Python حسب المشروع)، ضبط الجدار الناري والأمان، وإرشادك لصيانة السيرفر أو تنفيذ الصيانة نيابة عنك لفترة محددة.
                        </p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="glass-panel service-detail-feature-list animate-on-scroll">
                        <h4 class="service-detail-feature-list-title"><i class="fas fa-check-circle"></i> أبرز ما يميز الخدمة</h4>
                        <ul class="service-detail-feature-list-ul">
                            <li><i class="fas fa-chevron-left"></i> إعداد خوادم Linux (Ubuntu / CentOS)</li>
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

    <!-- ============ TECHNOLOGIES ============ -->
    <section class="section-padding">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge">التقنيات</span>
                <h2>تقنيات نعتمد عليها</h2>
                <p>أدوات وأنظمة تشغيل لإدارة الخوادم</p>
            </div>
            <div class="glass-panel service-tech-wrap animate-on-scroll">
                <div class="row g-3">
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="service-tech-item">Linux</div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="service-tech-item">Ubuntu</div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="service-tech-item">Nginx</div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="service-tech-item">Apache</div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="service-tech-item">MySQL / PostgreSQL</div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="service-tech-item">Docker</div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="service-tech-item">SSL / Let's Encrypt</div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="service-tech-item">Git</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============ RELATED SERVICES ============ -->
    <section class="section-padding" style="background: var(--clr-bg-secondary);">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge">تخصصات أخرى</span>
                <h2>خدمات وتخصصات ذات صلة</h2>
                <p>اطّلع على مجالات أخرى نقدم فيها الاستشارة والتنفيذ</p>
            </div>
            <div class="row g-4">
                <div class="col-sm-6 col-lg-3">
                    <a href="{{ route('frontend.service-detail') }}" class="service-related-card glass-panel animate-on-scroll">
                        <i class="fas fa-globe"></i>
                        <h6>تطوير تطبيقات الويب</h6>
                        <p>مواقع وتطبيقات ويب بـ React و Node.js</p>
                    </a>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <a href="{{ route('frontend.service-detail-mobile') }}" class="service-related-card glass-panel animate-on-scroll">
                        <i class="fas fa-mobile-alt"></i>
                        <h6>تطبيقات الجوال</h6>
                        <p>تطوير تطبيقات أندرويد و iOS بـ Flutter</p>
                    </a>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <a href="{{ route('frontend.service-detail-security') }}" class="service-related-card glass-panel animate-on-scroll">
                        <i class="fas fa-shield-alt"></i>
                        <h6>أمن المعلومات</h6>
                        <p>حماية الأنظمة والبيانات وتقييم الثغرات</p>
                    </a>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <a href="{{ route('frontend.service-detail') }}" class="service-related-card glass-panel animate-on-scroll">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <h6>استشارات وتدريب</h6>
                        <p>دورات واستشارات تقنية</p>
                    </a>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <a href="{{ route('frontend.service-detail-devops') }}" class="service-related-card glass-panel animate-on-scroll">
                        <i class="fas fa-infinity"></i>
                        <h6>DevOps</h6>
                        <p>CI/CD، حاويات، سحابة ومراقبة</p>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ============ CTA ============ -->
    <section class="cta-section">
        <div class="container animate-on-scroll">
            <h2>هل تحتاج إعداد أو إدارة سيرفر لمشروعك؟</h2>
            <p>تواصل معنا الآن ونناقش متطلباتك ونقدم لك عرضاً tailored لاحتياجاتك</p>
            <a href="{{ route('frontend.contact') }}" class="btn-light-custom">
                <i class="fas fa-paper-plane"></i> تواصل معنا
            </a>
        </div>
    </section>
@endsection
