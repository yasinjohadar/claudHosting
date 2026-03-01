@extends('frontend.layouts.master')

@section('page-title')
أمن المعلومات | استضافة كلاودسوفت
@endsection

@section('meta-description')
أمن المعلومات — حماية الأنظمة والبيانات، تقييم الثغرات، إعداد SSL/HTTPS، وتطبيق أفضل ممارسات OWASP. تدقيق أمني وتوصيات عملية لتفادي الاختراقات.
@endsection

@section('content')
    <!-- ============ SERVICE BANNER ============ -->
    <section class="page-banner page-banner-service">
        <div class="page-banner-overlay"></div>
        <div class="container position-relative">
            <div class="page-banner-content animate-on-scroll">
                <div class="page-banner-icon"><i class="fas fa-shield-alt"></i></div>
                <h1 class="page-banner-title">أمن <span>المعلومات</span></h1>
                <p class="page-banner-desc">حماية الأنظمة والبيانات، تقييم الثغرات، وتطبيق أفضل الممارسات الأمنية لتشغيل آمن وموثوق</p>
                <nav class="page-banner-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ url('/') }}">الرئيسية</a>
                    <span class="page-banner-sep">/</span>
                    <a href="{{ route('frontend.about') }}#specialties">التخصصات</a>
                    <span class="page-banner-sep">/</span>
                    <span>أمن المعلومات</span>
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
                            أقدم استشارات وتنفيذاً في مجال أمن المعلومات لحماية تطبيقاتك وبياناتك وسيرفراتك. نقيّم الثغرات، نطبّق تشفيراً مناسباً، ونُعدّ بيئة تشغيل آمنة وفق أفضل الممارسات (مثل OWASP) مع شهادات SSL وضبط صلاحيات الوصول.
                        </p>
                        <p class="service-detail-text">
                            يشمل العمل مراجعة أمنية للتطبيقات والواجهات البرمجية، إعداد HTTPS وشهادات SSL، تأمين قواعد البيانات والنسخ الاحتياطية، وتقديم توصيات عملية لتفادي الاختراقات وحماية بيانات المستخدمين.
                        </p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="glass-panel service-detail-feature-list animate-on-scroll">
                        <h4 class="service-detail-feature-list-title"><i class="fas fa-check-circle"></i> أبرز ما يميز الخدمة</h4>
                        <ul class="service-detail-feature-list-ul">
                            <li><i class="fas fa-chevron-left"></i> تقييم ثغرات وتدقيق أمني للتطبيقات</li>
                            <li><i class="fas fa-chevron-left"></i> إعداد HTTPS و شهادات SSL/TLS</li>
                            <li><i class="fas fa-chevron-left"></i> تأمين APIs وحماية من هجمات شائعة</li>
                            <li><i class="fas fa-chevron-left"></i> أفضل ممارسات OWASP وتخزين آمن للبيانات</li>
                            <li><i class="fas fa-chevron-left"></i> صلاحيات ومراقبة دخول مناسبة</li>
                            <li><i class="fas fa-chevron-left"></i> تقرير وتوصيات أمنية واضحة</li>
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
                <p>مراحل ومنتجات واضحة في كل مشروع أمن معلومات</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-search"></i></div>
                        <h5>تقييم الثغرات والتدقيق</h5>
                        <p>فحص التطبيقات والسيرفرات للكشف عن ثغرات أمنية وتقديم تقرير مع توصيات معالجة.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-lock"></i></div>
                        <h5>إعداد SSL/HTTPS</h5>
                        <p>تركيب وتجديد شهادات SSL وتفعيل HTTPS لجميع الخدمات مع إعداد آمن.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-code"></i></div>
                        <h5>تأمين التطبيقات والـ API</h5>
                        <p>مراجعة الكود وتأمين واجهات البرمجة ضد حقن SQL و XSS وهجمات أخرى شائعة.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-database"></i></div>
                        <h5>حماية قواعد البيانات</h5>
                        <p>ضبط صلاحيات الوصول، تشفير البيانات الحساسة، ونسخ احتياطية آمنة.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-user-shield"></i></div>
                        <h5>المصادقة والصلاحيات</h5>
                        <p>تصميم نظام مصادقة آمن (مثل JWT أو OAuth) وضبط الصلاحيات حسب الأدوار.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-file-alt"></i></div>
                        <h5>تقرير وتوصيات</h5>
                        <p>تسليم تقرير أمني واضح مع خطة تنفيذ التوصيات ومتابعة اختياري.</p>
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
                <h2>تقنيات ومعايير نعتمد عليها</h2>
                <p>أدوات ومعايير حديثة لضمان أمان الأنظمة</p>
            </div>
            <div class="glass-panel service-tech-wrap animate-on-scroll">
                <div class="row g-3">
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="service-tech-item">SSL / TLS</div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="service-tech-item">OWASP</div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="service-tech-item">JWT / OAuth</div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="service-tech-item">تشفير البيانات</div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="service-tech-item">HTTPS</div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="service-tech-item">جدران نارية</div>
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
                    <a href="{{ route('frontend.service-detail-servers') }}" class="service-related-card glass-panel animate-on-scroll">
                        <i class="fas fa-server"></i>
                        <h6>إدارة السيرفرات</h6>
                        <p>إعداد وإدارة الخوادم والاستضافة</p>
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
            <h2>هل تحتاج تدقيقاً أمنياً أو تأمين مشروعك؟</h2>
            <p>تواصل معنا الآن ونناقش متطلباتك ونقدم لك عرضاً tailored لاحتياجاتك</p>
            <a href="{{ route('frontend.contact') }}" class="btn-light-custom">
                <i class="fas fa-paper-plane"></i> تواصل معنا
            </a>
        </div>
    </section>
@endsection
