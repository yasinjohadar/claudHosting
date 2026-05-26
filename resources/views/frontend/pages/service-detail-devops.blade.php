@extends('frontend.layouts.master')

@section('page-title')
DevOps وتشغيل المنصات | استضافة كلاودسوفت
@endsection

@section('meta-description')
DevOps وتشغيل المنصات — CI/CD، حاويات Docker و Kubernetes، بنية كود IaC (Terraform، Ansible)، سحابة AWS/Azure/GCP، ومراقبة Prometheus و Grafana.
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
                <div class="page-banner-icon"><i class="fas fa-infinity"></i></div>
                <h1 class="page-banner-title">DevOps <span>وتشغيل المنصات</span></h1>
                <p class="page-banner-desc">تكامل ونشر مستمر (CI/CD)، حاويات وأوركستريشن، بنية كود (IaC)، سحابة ومراقبة — من البناء حتى التشغيل الآلي</p>
                <nav class="page-banner-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ url('/') }}">الرئيسية</a>
                    <span class="page-banner-sep">/</span>
                    <a href="{{ route('frontend.about') }}#specialties">التخصصات</a>
                    <span class="page-banner-sep">/</span>
                    <span>DevOps</span>
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
                        <h2 class="service-detail-heading">ماذا نقدم في DevOps؟</h2>
                        <p class="service-detail-lead">
                            أقدم حلولاً متكاملة في ثقافة ومنهجية DevOps: أتمتة البناء والاختبار والنشر (CI/CD)، إدارة الحاويات والأوركستريشن (Docker و Kubernetes)، البنية كود (Terraform، Ansible)، والعمل على منصات سحابية (AWS، Azure، GCP) مع مراقبة وسجلات وضمان استقرار وتوفر الخدمات.
                        </p>
                        <p class="service-detail-text">
                            نربط بين التطوير والتشغيل عبر خطوط أنابيب أوتوماتيكية، بيئات قابلة للتكرار، ومراقبة الأداء والسجلات. سواء مشروعك على سيرفرات تقليدية أو سحابة أو كوبرنيتس — نضع معك البنية والعمليات المناسبة وتدريب الفريق إن لزم.
                        </p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="glass-panel service-detail-feature-list animate-on-scroll">
                        <h4 class="service-detail-feature-list-title"><i class="fas fa-check-circle"></i> أبرز ما يميز الخدمة</h4>
                        <ul class="service-detail-feature-list-ul">
                            <li><i class="fas fa-chevron-left"></i> خطوط أنابيب CI/CD (Jenkins، GitLab CI، GitHub Actions)</li>
                            <li><i class="fas fa-chevron-left"></i> حاويات وأوركستريشن (Docker، Kubernetes، Helm)</li>
                            <li><i class="fas fa-chevron-left"></i> بنية كود IaC (Terraform، Ansible)</li>
                            <li><i class="fas fa-chevron-left"></i> نشر على سحابة (AWS، Azure، GCP)</li>
                            <li><i class="fas fa-chevron-left"></i> مراقبة وسجلات (Prometheus، Grafana، ELK)</li>
                            <li><i class="fas fa-chevron-left"></i> أتمتة وإدارة تكوين موحّدة</li>
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
                <p>مراحل ومنتجات واضحة في كل مشروع DevOps</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-sync-alt"></i></div>
                        <h5>CI/CD — تكامل ونشر مستمر</h5>
                        <p>إعداد خطوط أنابيب للبناء والاختبار والنشر التلقائي (Jenkins، GitLab CI، GitHub Actions، Azure DevOps) مع إدارة البيئات والسرّات.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fab fa-docker"></i></div>
                        <h5>حاويات وأوركستريشن</h5>
                        <p>إعداد حاويات (Containerization) بـ Docker، إدارة Clusters بـ Kubernetes (K8s)، Helm و Kustomize، واختياراً Docker Swarm أو منصات مُدارة (EKS، AKS، GKE).</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-code-branch"></i></div>
                        <h5>البنية كود (IaC)</h5>
                        <p>توفير البنية وتجهيز السيرفرات عبر Terraform و Ansible (أو Puppet/Chef) لبيئات قابلة للتكرار وقابلة للإصدار.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-cloud"></i></div>
                        <h5>السحابة والخدمات المُدارة</h5>
                        <p>نشر على AWS أو Azure أو GCP أو DigitalOcean: حسابات، شبكات، تخزين، قواعد بيانات مُدارة، وخدمات serverless حيث يناسب.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-chart-line"></i></div>
                        <h5>المراقبة والسجلات</h5>
                        <p>إعداد Prometheus و Grafana للمقاييس والتنبيهات، و/أو ELK/EFK stack للسجلات، مع لوحات ودمج مع أنظمة الحوادث.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-robot"></i></div>
                        <h5>أتمتة وسكريبتات</h5>
                        <p>سكريبتات Bash/Python للصيانة والنسخ الاحتياطي، إدارة التكوين الموحّد، وربط الأدوات في سير عمل واحد.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('frontend.partials.devops-tools-catalog')

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
            <h2>هل تحتاج بنية DevOps أو أتمتة نشر لمشروعك؟</h2>
            <p>تواصل معنا الآن ونناقش متطلباتك ونقدم لك عرضاً tailored لاحتياجاتك</p>
            <a href="{{ route('frontend.contact') }}" class="btn-light-custom">
                <i class="fas fa-paper-plane"></i> تواصل معنا
            </a>
        </div>
    </section>
@endsection
