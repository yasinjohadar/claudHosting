@extends('frontend.layouts.master')

@section('page-title')
تطبيقات الجوال | Dart & Flutter | استضافة كلاودسوفت
@endsection

@section('meta-description')
تطوير تطبيقات الجوال — بناء تطبيقات أندرويد و iOS بـ Dart و Flutter. كود واحد لمنصتين، واجهات Material و Cupertino، أداء قريب من الأصلي، ونشر على المتاجر.
@endsection

@section('content')
    <!-- ============ SERVICE BANNER ============ -->
    <section class="page-banner page-banner-service">
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
    <section class="section-padding">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <div class="service-detail-intro animate-on-scroll">
                        <span class="section-badge">نظرة عامة</span>
                        <h2 class="service-detail-heading">ماذا نقدم في هذه الخدمة؟</h2>
                        <p class="service-detail-lead">
                            أقدم لك حلولاً متكاملة لتطوير تطبيقات الجوال لنظامي أندرويد و iOS باستخدام إطار عمل Flutter ولغة Dart. نكتب الكود مرة واحدة وننشره على المنصتين مع الحفاظ على أداء عالٍ وواجهات أصلية (Material و Cupertino)، مما يوفر وقتك وتكلفة المشروع.
                        </p>
                        <p class="service-detail-text">
                            نعتمد على Dart و Flutter لبناء تطبيقات سريعة وقابلة للصيانة، مع دعم إدارة الحالة (State Management) مثل GetX أو Provider، والتكامل مع Firebase وواجهات REST API. يشمل العمل التصميم، التطوير، الاختبار، والنشر على متجر Google Play ومتجر Apple App Store.
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

    <!-- ============ TECHNOLOGIES ============ -->
    <section class="section-padding">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge">التقنيات</span>
                <h2>تقنيات نعتمد عليها</h2>
                <p>أدوات وإطارات عمل حديثة لتطوير تطبيقات الجوال</p>
            </div>
            <div class="glass-panel service-tech-wrap animate-on-scroll">
                <div class="row g-3">
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="service-tech-item">Dart</div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="service-tech-item">Flutter</div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="service-tech-item">GetX / Provider</div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="service-tech-item">Firebase</div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="service-tech-item">REST API</div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="service-tech-item">Git / GitHub</div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="service-tech-item">Android Studio</div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="service-tech-item">VS Code</div>
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
                    <a href="{{ route('frontend.service-detail-servers') }}" class="service-related-card glass-panel animate-on-scroll">
                        <i class="fas fa-server"></i>
                        <h6>إدارة السيرفرات</h6>
                        <p>إعداد وإدارة الخوادم والاستضافة</p>
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
            <h2>هل تحتاج تطبيق جوال لمشروعك؟</h2>
            <p>تواصل معنا الآن ونناقش متطلباتك ونقدم لك عرضاً tailored لاحتياجاتك</p>
            <a href="{{ route('frontend.contact') }}" class="btn-light-custom">
                <i class="fas fa-paper-plane"></i> تواصل معنا
            </a>
        </div>
    </section>
@endsection
