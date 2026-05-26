@extends('frontend.layouts.master')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/devicon@2.15.1/devicon.min.css" crossorigin="anonymous">
@endpush

@section('page-title')
حول استضافة كلاودسوفت | CloudSoft Hosting
@endsection

@section('meta-description')
من نحن — منصة استضافة كلاودسوفت: بيئة سحابية آمنة وسريعة للمشاريع العربية، بنية تحتية حديثة، تكرار ونسخ احتياطي مستمر، ودعم فني يهتم بكل تفاصيل مشروعك.
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

    @include('frontend.partials.about-intro-section')

    @include('frontend.partials.about-infrastructure-section')

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
                        <a href="{{ route('frontend.service-detail-web') }}" class="btn-outline-custom mt-3" style="display:inline-flex; padding:8px 18px; font-size:0.88rem;"><i class="fas fa-arrow-left"></i> اعرف المزيد</a>
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
