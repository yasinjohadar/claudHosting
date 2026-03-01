@extends('frontend.layouts.master')

@section('page-title')
تطوير تطبيقات الويب | استضافة كلاودسوفت
@endsection

@section('meta-description')
تطوير تطبيقات الويب — تصميم وتطوير مواقع وتطبيقات ويب حديثة ومتجاوبة بأحدث التقنيات: React، Node.js، TypeScript. واجهات احترافية، أداء عالٍ، ودعم SEO وأمان.
@endsection

@section('content')
    <!-- ============ SERVICE BANNER ============ -->
    <section class="page-banner page-banner-service">
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
    <section class="section-padding">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <div class="service-detail-intro animate-on-scroll">
                        <span class="section-badge">نظرة عامة</span>
                        <h2 class="service-detail-heading">ماذا نقدم في هذه الخدمة؟</h2>
                        <p class="service-detail-lead">
                            أقدم لك حلولاً متكاملة لتطوير تطبيقات الويب من الصفر حتى النشر، مع التركيز على تجربة المستخدم والأداء والأمان. سواء كنت تحتاج موقعاً تعريفياً، متجراً إلكترونياً، أو منصة ويب معقدة — نعمل معاً لتحويل فكرتك إلى واقع رقمي احترافي.
                        </p>
                        <p class="service-detail-text">
                            نستخدم أحدث تقنيات الويب مثل React.js و Node.js و TypeScript لضمان كود قابل للصيانة وقابل للتوسع. جميع المشاريع تكون متجاوبة بالكامل مع مختلف الأجهزة وتتبع أفضل ممارسات SEO والأداء.
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

    <!-- ============ TECHNOLOGIES ============ -->
    <section class="section-padding">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge">التقنيات</span>
                <h2>تقنيات نعتمد عليها</h2>
                <p>أدوات وإطارات عمل حديثة لضمان جودة واحترافية المشروع</p>
            </div>
            <div class="glass-panel service-tech-wrap animate-on-scroll">
                <div class="row g-3">
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="service-tech-item">HTML5 / CSS3</div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="service-tech-item">JavaScript / TypeScript</div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="service-tech-item">React.js</div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="service-tech-item">Vue.js / Next.js</div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="service-tech-item">Node.js / Express</div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="service-tech-item">MongoDB / MySQL</div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="service-tech-item">Bootstrap / Tailwind</div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="service-tech-item">REST API</div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="service-tech-item">Git / GitHub</div>
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
                        <p>تطوير تطبيقات أندرويد و iOS</p>
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
            <h2>هل تحتاج هذه الخدمة لمشروعك؟</h2>
            <p>تواصل معنا الآن ونناقش متطلباتك ونقدم لك عرضاً tailored لاحتياجاتك</p>
            <a href="{{ route('frontend.contact') }}" class="btn-light-custom">
                <i class="fas fa-paper-plane"></i> تواصل معنا
            </a>
        </div>
    </section>
@endsection
