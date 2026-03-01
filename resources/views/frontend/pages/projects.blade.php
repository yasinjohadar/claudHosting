@extends('frontend.layouts.master')

@section('page-title')
المشاريع | استضافة كلاودسوفت
@endsection

@section('meta-description')
مشاريع استضافة كلاودسوفت — تعرف على أبرز الأعمال في الاستضافة، تطوير المواقع، وإدارة الخوادم. مشاريع حقيقية نفتخر بها.
@endsection

@section('content')
    <!-- ============ PAGE BANNER (نفس About) ============ -->
    <section class="page-banner page-banner-about">
        <div class="page-banner-overlay"></div>
        <div class="container position-relative">
            <div class="page-banner-content animate-on-scroll">
                <div class="page-banner-icon"><i class="fas fa-folder-open"></i></div>
                <h1 class="page-banner-title">المشاريع <span>وأعمالنا</span></h1>
                <p class="page-banner-desc">تعرف على أبرز المشاريع التي نفذناها في مجالات الاستضافة، تطوير المواقع، وإدارة الخوادم.</p>
                <nav class="page-banner-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ url('/') }}">الرئيسية</a>
                    <span class="page-banner-sep">/</span>
                    <span>المشاريع</span>
                </nav>
            </div>
        </div>
        <div class="page-banner-shape"></div>
    </section>

    <!-- ============ PROJECTS GRID ============ -->
    <section class="section-padding" id="projects" style="background: var(--clr-bg-secondary);">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="glass-panel skill-card animate-on-scroll animate-delay-1" style="text-decoration:none;color:inherit;display:block;height:100%;">
                        <div class="skill-icon"><i class="fas fa-globe"></i></div>
                        <h5>مشروع استضافة متجر إلكتروني</h5>
                        <p>نقل وإعداد استضافة سحابية لمتجر إلكتروني مع ضمان استمرارية العمل ودعم SSL ونسخ احتياطي يومي.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="glass-panel skill-card animate-on-scroll animate-delay-2" style="text-decoration:none;color:inherit;display:block;height:100%;">
                        <div class="skill-icon"><i class="fas fa-server"></i></div>
                        <h5>ترحيل خوادم شركة محلية</h5>
                        <p>ترحيل خدمات شركة من سيرفرات محلية إلى بنية سحابية مع تقليل التوقف إلى أقل من ساعة.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="glass-panel skill-card animate-on-scroll animate-delay-3" style="text-decoration:none;color:inherit;display:block;height:100%;">
                        <div class="skill-icon"><i class="fas fa-shield-alt"></i></div>
                        <h5>تأمين وتدقيق موقع حكومي</h5>
                        <p>مراجعة أمنية وتطبيق أفضل الممارسات لموقع حكومي يشمل حماية من الاختراقات ونسخ احتياطية آمنة.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="glass-panel skill-card animate-on-scroll animate-delay-4" style="text-decoration:none;color:inherit;display:block;height:100%;">
                        <div class="skill-icon"><i class="fas fa-cloud"></i></div>
                        <h5>بنية سحابية لشركة ناشئة</h5>
                        <p>تصميم وتنفيذ بنية سحابية قابلة للتوسع لشركة ناشئة مع إدارة الحاويات و CI/CD.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="glass-panel skill-card animate-on-scroll animate-delay-1" style="text-decoration:none;color:inherit;display:block;height:100%;">
                        <div class="skill-icon"><i class="fas fa-database"></i></div>
                        <h5>تحسين أداء قاعدة بيانات</h5>
                        <p>تحسين استعلامات وقاعدة بيانات لتطبيق ويب مما أدى إلى تقليل زمن التحميل بنسبة 60%.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="glass-panel skill-card animate-on-scroll animate-delay-2" style="text-decoration:none;color:inherit;display:block;height:100%;">
                        <div class="skill-icon"><i class="fas fa-mobile-alt"></i></div>
                        <h5>دعم API لتطبيق جوال</h5>
                        <p>إعداد واستضافة واجهات API آمنة وسريعة لتطبيق جوال يخدم آلاف المستخدمين يومياً.</p>
                    </div>
                </div>
            </div>
            <div class="text-center mt-5 animate-on-scroll">
                <a href="{{ url('/') }}" class="btn-primary-custom">
                    <i class="fas fa-home"></i> العودة للرئيسية
                </a>
            </div>
        </div>
    </section>
@endsection
