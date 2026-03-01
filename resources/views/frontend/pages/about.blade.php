@extends('frontend.layouts.master')

@section('page-title')
حول استضافة كلاودسوفت | CloudSoft Hosting
@endsection

@section('content')
    <!-- ============ PAGE BANNER (حول الاستضافة) ============ -->
    <section class="page-banner page-banner-about">
        <div class="page-banner-overlay"></div>
        <div class="container position-relative">
            <div class="page-banner-content animate-on-scroll">
                <div class="page-banner-icon"><i class="fas fa-cloud"></i></div>
                <h1 class="page-banner-title">حول <span>استضافة كلاودسوفت</span></h1>
                <p class="page-banner-desc">منصة استضافة مواقع سحابية تقدم أداءً عالياً، أماناً متقدماً، ودعماً فنياً يهتم بكل تفاصيل مشروعك.</p>
                <nav class="page-banner-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ url('/') }}">الرئيسية</a>
                    <span class="page-banner-sep">/</span>
                    <span>حول الاستضافة</span>
                </nav>
            </div>
        </div>
        <div class="page-banner-shape"></div>
    </section>

    <!-- ============ ABOUT INTRO (ClaudSoft) ============ -->
    <section class="section-padding">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-5">
                    <div class="about-img-wrapper animate-on-scroll">
                        <img src="{{ asset('frontend/assets/images/hero-servers.svg') }}" alt="استضافة كلاودسوفت" class="w-100" width="400" height="400" loading="lazy">
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="animate-on-scroll">
                        <span class="section-badge" style="display:inline-block; margin-bottom:15px;">من نحن؟</span>
                        <h2 style="font-weight:800; font-size:2rem; margin-bottom:20px;">استضافة كلاودسوفت CloudSoft Hosting</h2>
                        <p style="font-size:1.05rem; line-height:2; color:var(--clr-text-secondary);">
                            استضافة كلاودسوفت هي منصة استضافة مواقع سحابية تم إطلاقها لتوفير بيئة آمنة وسريعة للمشاريع
                            العربية، مع تركيز خاص على الاستقرار وسهولة الإدارة. نعتمد بنية تحتية سحابية حديثة مع تقنيات
                            التكرار والنسخ الاحتياطي المستمر.
                        </p>
                        <p style="font-size:1.05rem; line-height:2; color:var(--clr-text-secondary);">
                            هدفنا أن نمنح أصحاب المواقع والمتاجر تجربة استضافة خالية من التعقيد؛ نعتني نحن بالخوادم،
                            الأمان، والنسخ الاحتياطي، لتتفرغ أنت لبناء مشروعك ونموّ عملك. نقدّم باقات مرنة تناسب
                            المشاريع الصغيرة والمتوسطة والشركات، مع إمكانية تخصيص الحلول عند الحاجة.
                        </p>

                        <!-- Quick Facts -->
                        <div class="row g-3 mt-3">
                            <div class="col-sm-6">
                                <div class="glass-panel" style="padding:18px; text-align:center;">
                                    <i class="fas fa-server"
                                        style="font-size:1.5rem; color:var(--clr-primary); margin-bottom:8px; display:block;"></i>
                                    <strong>+200 موقع مستضاف</strong>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="glass-panel" style="padding:18px; text-align:center;">
                                    <i class="fas fa-users"
                                        style="font-size:1.5rem; color:var(--clr-primary); margin-bottom:8px; display:block;"></i>
                                    <strong>+500 عميل نشط</strong>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="glass-panel" style="padding:18px; text-align:center;">
                                    <i class="fas fa-cloud"
                                        style="font-size:1.5rem; color:var(--clr-primary); margin-bottom:8px; display:block;"></i>
                                    <strong>بنية سحابية موزعة</strong>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="glass-panel" style="padding:18px; text-align:center;">
                                    <i class="fas fa-shield-alt"
                                        style="font-size:1.5rem; color:var(--clr-primary); margin-bottom:8px; display:block;"></i>
                                    <strong>حماية متقدمة ونسخ احتياطي</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============ مهاراتي التفصيلية (أشرطة التقدم) ============ -->
    <section class="section-padding" style="background: var(--clr-bg-secondary);">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge">بنيتنا التقنية</span>
                <h2>البنية التحتية والتقنيات المستخدمة</h2>
                <p>نستخدم أحدث التقنيات في الخوادم، الشبكات، وقواعد البيانات لضمان أفضل أداء وأعلى مستوى من الأمان لخدمات الاستضافة</p>
            </div>
            <div class="skills-detailed animate-on-scroll">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="glass-panel skills-category">
                            <h4 class="skills-category-title">
                                <i class="fas fa-server"></i>
                                أنظمة التشغيل ولوحات التحكم
                            </h4>
                            <p class="mb-3" style="color:var(--clr-text-secondary); font-size:0.95rem;">
                                نعتمد على أنظمة مستقرة ولوحات تحكم معروفة لنوفر إدارة سهلة للخوادم والمواقع.
                            </p>
                            <ul class="list-unstyled mb-0">
                                <li class="d-flex align-items-center mb-2">
                                    <i class="fab fa-linux me-2" style="color:var(--clr-primary);"></i>
                                    <span>Linux (Ubuntu / AlmaLinux)</span>
                                </li>
                                <li class="d-flex align-items-center mb-2">
                                    <i class="fas fa-tachometer-alt me-2" style="color:var(--clr-primary);"></i>
                                    <span>cPanel / WHM</span>
                                </li>
                                <li class="d-flex align-items-center mb-2">
                                    <i class="fas fa-columns me-2" style="color:var(--clr-primary);"></i>
                                    <span>Plesk Panel</span>
                                </li>
                                <li class="d-flex align-items-center mb-2">
                                    <i class="fas fa-exchange-alt me-2" style="color:var(--clr-primary);"></i>
                                    <span>NGINX و Apache Web Server</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="glass-panel skills-category">
                            <h4 class="skills-category-title">
                                <i class="fas fa-cubes"></i>
                                الحاويات والبنية السحابية
                            </h4>
                            <p class="mb-3" style="color:var(--clr-text-secondary); font-size:0.95rem;">
                                بنية تحتية سحابية حديثة مبنية على الحاويات وبيئات VPS لضمان المرونة وقابلية التوسع.
                            </p>
                            <ul class="list-unstyled mb-0">
                                <li class="d-flex align-items-center mb-2">
                                    <i class="fab fa-docker me-2" style="color:var(--clr-primary);"></i>
                                    <span>Docker للحاويات</span>
                                </li>
                                <li class="d-flex align-items-center mb-2">
                                    <i class="fas fa-project-diagram me-2" style="color:var(--clr-primary);"></i>
                                    <span>Kubernetes لتنسيق الحاويات</span>
                                </li>
                                <li class="d-flex align-items-center mb-2">
                                    <i class="fas fa-cloud-meatball me-2" style="color:var(--clr-primary);"></i>
                                    <span>خوادم Cloud VPS / KVM</span>
                                </li>
                                <li class="d-flex align-items-center mb-2">
                                    <i class="fas fa-network-wired me-2" style="color:var(--clr-primary);"></i>
                                    <span>CDN و Load Balancing</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="glass-panel skills-category">
                            <h4 class="skills-category-title">
                                <i class="fas fa-database"></i>
                                قواعد البيانات وأنظمة التخزين
                            </h4>
                            <p class="mb-3" style="color:var(--clr-text-secondary); font-size:0.95rem;">
                                حلول تخزين وقواعد بيانات مصممة لتحمل الضغط وتقديم أداء عالٍ لتطبيقات الويب والمتاجر.
                            </p>
                            <ul class="list-unstyled mb-0">
                                <li class="d-flex align-items-center mb-2">
                                    <i class="fas fa-database me-2" style="color:var(--clr-primary);"></i>
                                    <span>MySQL / MariaDB</span>
                                </li>
                                <li class="d-flex align-items-center mb-2">
                                    <i class="fas fa-database me-2" style="color:var(--clr-primary);"></i>
                                    <span>PostgreSQL</span>
                                </li>
                                <li class="d-flex align-items-center mb-2">
                                    <i class="fas fa-bolt me-2" style="color:var(--clr-primary);"></i>
                                    <span>Redis Cache لتسريع المواقع</span>
                                </li>
                                <li class="d-flex align-items-center mb-2">
                                    <i class="fas fa-cloud-download-alt me-2" style="color:var(--clr-primary);"></i>
                                    <span>Object Storage ونسخ احتياطي خارجي</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="glass-panel skills-category">
                            <h4 class="skills-category-title">
                                <i class="fas fa-shield-alt"></i>
                                الأمان والمراقبة
                            </h4>
                            <p class="mb-3" style="color:var(--clr-text-secondary); font-size:0.95rem;">
                                طبقات متعددة من الحماية والمراقبة المستمرة لضمان استقرار خدمات الاستضافة وسلامة بياناتك.
                            </p>
                            <ul class="list-unstyled mb-0">
                                <li class="d-flex align-items-center mb-2">
                                    <i class="fas fa-fire-alt me-2" style="color:var(--clr-primary);"></i>
                                    <span>جدران نارية و Web Application Firewall</span>
                                </li>
                                <li class="d-flex align-items-center mb-2">
                                    <i class="fas fa-chart-line me-2" style="color:var(--clr-primary);"></i>
                                    <span>أنظمة مراقبة وتنبيه آنية</span>
                                </li>
                                <li class="d-flex align-items-center mb-2">
                                    <i class="fas fa-history me-2" style="color:var(--clr-primary);"></i>
                                    <span>نسخ احتياطي تلقائي على فترات منتظمة</span>
                                </li>
                                <li class="d-flex align-items-center mb-2">
                                    <i class="fas fa-lock me-2" style="color:var(--clr-primary);"></i>
                                    <span>شهادات SSL وتشفير الاتصالات</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============ SKILLS DETAILED ============ -->
    <section class="section-padding" id="specialties">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge">خدماتنا</span>
                <h2>ما الذي نقدمه في استضافة كلاودسوفت؟</h2>
                <p>مجموعة من خدمات الاستضافة السحابية المصممة لتغطية احتياجات المواقع الشخصية، المتاجر الإلكترونية، وتطبيقات الشركات</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="glass-panel animate-on-scroll animate-delay-1" style="padding:30px; height:100%;">
                        <div style="display:flex; align-items:center; gap:15px; margin-bottom:20px;">
                            <div
                                style="width:55px; height:55px; border-radius:var(--radius-md); background:linear-gradient(135deg, var(--clr-primary), var(--clr-primary-dark)); display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.4rem; flex-shrink:0;">
                                <i class="fas fa-code"></i></div>
                            <h5 style="font-weight:700; margin:0;">استضافة المواقع والبريد الإلكتروني</h5>
                        </div>
                            <p style="color:var(--clr-text-secondary); font-size:0.95rem;">
                                استضافة سريعة وآمنة للمواقع والبريد الإلكتروني مع شهادات SSL مجانية، نسخ احتياطي يومي،
                                ولوحة تحكم عربية سهلة الاستخدام.
                            </p>
                        <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:15px;">
                            <span
                                style="background:var(--clr-surface); padding:4px 12px; border-radius:50px; font-size:0.78rem; color:var(--clr-text-secondary);">cPanel</span>
                            <span
                                style="background:var(--clr-surface); padding:4px 12px; border-radius:50px; font-size:0.78rem; color:var(--clr-text-secondary);">Email Hosting</span>
                            <span
                                style="background:var(--clr-surface); padding:4px 12px; border-radius:50px; font-size:0.78rem; color:var(--clr-text-secondary);">SSL</span>
                            <span
                                style="background:var(--clr-surface); padding:4px 12px; border-radius:50px; font-size:0.78rem; color:var(--clr-text-secondary);">Backups</span>
                        </div>
                        <a href="{{ route('frontend.packages') }}" class="btn-outline-custom mt-3" style="display:inline-flex; padding:8px 18px; font-size:0.88rem;"><i class="fas fa-arrow-left"></i> استعرض الباقات</a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="glass-panel animate-on-scroll animate-delay-2" style="padding:30px; height:100%;">
                        <div style="display:flex; align-items:center; gap:15px; margin-bottom:20px;">
                            <div
                                style="width:55px; height:55px; border-radius:var(--radius-md); background:linear-gradient(135deg, var(--clr-primary), var(--clr-primary-dark)); display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.4rem; flex-shrink:0;">
                                <i class="fas fa-server"></i></div>
                            <h5 style="font-weight:700; margin:0;">إدارة الخوادم والبنية التحتية</h5>
                        </div>
                            <p style="color:var(--clr-text-secondary); font-size:0.95rem;">
                                إدارة وإعداد الخوادم السحابية و VPS مع مراقبة مستمرة، موازنة أحمال، وتحديثات أمان دورية
                                لضمان أعلى مستوى من الاعتمادية.
                            </p>
                        <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:15px;">
                            <span
                                style="background:var(--clr-surface); padding:4px 12px; border-radius:50px; font-size:0.78rem; color:var(--clr-text-secondary);">Linux</span>
                            <span
                                style="background:var(--clr-surface); padding:4px 12px; border-radius:50px; font-size:0.78rem; color:var(--clr-text-secondary);">Docker</span>
                            <span
                                style="background:var(--clr-surface); padding:4px 12px; border-radius:50px; font-size:0.78rem; color:var(--clr-text-secondary);">NGINX</span>
                            <span
                                style="background:var(--clr-surface); padding:4px 12px; border-radius:50px; font-size:0.78rem; color:var(--clr-text-secondary);">Monitoring</span>
                        </div>
                        <a href="{{ route('frontend.service-detail-servers') }}" class="btn-outline-custom mt-3" style="display:inline-flex; padding:8px 18px; font-size:0.88rem;"><i class="fas fa-arrow-left"></i> اعرف المزيد</a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="glass-panel animate-on-scroll animate-delay-3" style="padding:30px; height:100%;">
                        <div style="display:flex; align-items:center; gap:15px; margin-bottom:20px;">
                            <div
                                style="width:55px; height:55px; border-radius:var(--radius-md); background:linear-gradient(135deg, var(--clr-primary), var(--clr-primary-dark)); display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.4rem; flex-shrink:0;">
                                <i class="fas fa-mobile-alt"></i></div>
                            <h5 style="font-weight:700; margin:0;">خدمات القيمة المضافة</h5>
                        </div>
                            <p style="color:var(--clr-text-secondary); font-size:0.95rem;">
                                خدمات إضافية مثل إدارة النطاقات، شهادات الحماية المتقدمة، التكامل مع منصات الدفع، وخدمات
                                CDN لتسريع تحميل المواقع حول العالم.
                            </p>
                        <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:15px;">
                            <span
                                style="background:var(--clr-surface); padding:4px 12px; border-radius:50px; font-size:0.78rem; color:var(--clr-text-secondary);">Domains</span>
                            <span
                                style="background:var(--clr-surface); padding:4px 12px; border-radius:50px; font-size:0.78rem; color:var(--clr-text-secondary);">CDN</span>
                            <span
                                style="background:var(--clr-surface); padding:4px 12px; border-radius:50px; font-size:0.78rem; color:var(--clr-text-secondary);">WAF</span>
                            <span
                                style="background:var(--clr-surface); padding:4px 12px; border-radius:50px; font-size:0.78rem; color:var(--clr-text-secondary);">Payment Integrations</span>
                        </div>
                        <a href="{{ route('frontend.service-detail') }}" class="btn-outline-custom mt-3" style="display:inline-flex; padding:8px 18px; font-size:0.88rem;"><i class="fas fa-arrow-left"></i> اعرف المزيد</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============ CTA ============ -->
    <section class="cta-section">
        <div class="container animate-on-scroll">
            <h2>هل تبحث عن استضافة موثوقة لموقعك؟</h2>
            <p>اخبرنا عن نوع مشروعك وسنقترح عليك أفضل باقة استضافة تناسب احتياجاتك وميزانيتك</p>
            <a href="{{ route('frontend.contact') }}" class="btn-light-custom">
                <i class="fas fa-envelope"></i> تواصل معنا
            </a>
        </div>
    </section>
@endsection
