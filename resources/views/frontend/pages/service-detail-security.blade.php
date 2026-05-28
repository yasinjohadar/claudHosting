@extends('frontend.layouts.master')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/devicon@2.15.1/devicon.min.css" crossorigin="anonymous">
@endpush

@section('content')
    <section class="page-banner page-banner-about page-banner-service">
        <div class="page-banner-overlay"></div>
        <div class="container position-relative">
            <div class="page-banner-content animate-on-scroll">
                <div class="page-banner-icon"><i class="fas fa-shield-halved"></i></div>
                <h1 class="page-banner-title">أمن المعلومات <span>والسايبر سيكيورتي</span></h1>
                <p class="page-banner-desc">
                    حماية متعددة الطبقات لمواقعك وخوادمك وتطبيقاتك — من الجدار الناري إلى التشفير إلى المراقبة والاستجابة للحوادث
                </p>
                <nav class="page-banner-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ url('/') }}">الرئيسية</a>
                    <span class="page-banner-sep">/</span>
                    <a href="{{ route('frontend.about') }}#specialties">التخصصات</a>
                    <span class="page-banner-sep">/</span>
                    <span>الأمن السيبراني</span>
                </nav>
            </div>
        </div>
        <div class="page-banner-shape"></div>
    </section>

    <section class="section-padding security-intro-section">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <div class="service-detail-intro animate-on-scroll">
                        <span class="section-badge">نظرة عامة</span>
                        <h2 class="service-detail-heading">أمن سيبراني مدمج في الاستضافة</h2>
                        <p class="service-detail-lead">
                            في <strong>استضافة كلاودسوفت</strong> لا نعتبر الأمان ميزة إضافية — بل جزء أساسي من البنية التحتية.
                            نعمل بأدوات ومعايير عالمية (OWASP، CIS، تشفير TLS، WAF، مراقبة مستمرة) لحماية بيانات عملائنا
                            وضمان تشغيل مواقعهم ومتاجرهم بثقة.
                        </p>
                        <p class="service-detail-text">
                            نُطبّق طبقات حماية على مستوى الشبكة والحافة (Cloudflare)، الخادم (جدار ناري، Fail2ban، تحديثات أمنية)،
                            التطبيق (مراجعة كود، تأمين API، رؤوس HTTP أمنية)، والنسخ الاحتياطي المشفر. كما نُقدّم تدقيقاً أمنياً
                            وتوصيات عملية للمشاريع التي تحتاج تقييماً أعمق.
                        </p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="glass-panel service-detail-feature-list animate-on-scroll">
                        <h4 class="service-detail-feature-list-title"><i class="fas fa-shield-alt"></i> ماذا نُقدّم لك؟</h4>
                        <ul class="service-detail-feature-list-ul">
                            <li><i class="fas fa-chevron-left"></i> WAF وحماية DDoS عبر Cloudflare</li>
                            <li><i class="fas fa-chevron-left"></i> SSL/TLS مجاني ومُجدَّد (Let's Encrypt)</li>
                            <li><i class="fas fa-chevron-left"></i> فحص ثغرات وتدقيق OWASP للتطبيقات</li>
                            <li><i class="fas fa-chevron-left"></i> مراقبة وتنبيهات (Prometheus / Grafana)</li>
                            <li><i class="fas fa-chevron-left"></i> تأمين API، JWT، وتحديد معدل الطلبات</li>
                            <li><i class="fas fa-chevron-left"></i> نسخ احتياطي مشفر وخطة استعادة</li>
                            <li><i class="fas fa-chevron-left"></i> أمان حاويات Docker / Kubernetes (Trivy)</li>
                            <li><i class="fas fa-chevron-left"></i> تقرير أمني وتوصيات قابلة للتنفيذ</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-padding security-layers-section" style="background: var(--clr-bg-secondary);">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge">طبقات الحماية</span>
                <h2>نموذج الدفاع المتعدد الطبقات</h2>
                <p>كل طبقة تُعالج نوعاً مختلفاً من التهديدات — معاً تُشكّل حزمة أمن متكاملة</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-cloud"></i></div>
                        <h5>الحافة (Edge)</h5>
                        <p>Cloudflare WAF، CDN، DNS آمن، وتصفية حركة مشبوهة قبل الوصول للخادم.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-server"></i></div>
                        <h5>الخادم</h5>
                        <p>جدار ناري، Fail2ban، صلاحيات SSH، تحديثات أمنية، وعزل الخدمات.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-laptop-code"></i></div>
                        <h5>التطبيق</h5>
                        <p>OWASP، تأمين الجلسات، API، رؤوس أمان، وفحص التبعيات البرمجية.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-database"></i></div>
                        <h5>البيانات</h5>
                        <p>تشفير النقل والتخزين، نسخ احتياطي مشفر، وصلاحيات قواعد البيانات.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-padding">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge">تفاصيل الخدمة</span>
                <h2>ما الذي يشمل عليه العمل؟</h2>
                <p>خدمات أمنية ننفّذها ضمن الاستضافة أو كمشروع تدقيق مستقل</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-search"></i></div>
                        <h5>تقييم الثغرات (VAPT)</h5>
                        <p>فحص تطبيقات وخوادم بأدوات OWASP ZAP و Nikto و OpenVAS مع تقرير CVSS وخطة معالجة.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-lock"></i></div>
                        <h5>SSL/TLS و HTTPS</h5>
                        <p>تركيب Let's Encrypt، ضبط TLS 1.2+، HSTS، وإعادة توجيه HTTP إلى HTTPS.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-cloud"></i></div>
                        <h5>إعداد WAF و Cloudflare</h5>
                        <p>قواعد جدار ناري، حماية DDoS، تحدي Bot، وضبط SSL على الحافة.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-code"></i></div>
                        <h5>تأمين التطبيقات و API</h5>
                        <p>مراجعة ضد OWASP Top 10، JWT/OAuth، Rate Limiting، ومنع SQLi و XSS.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-chart-line"></i></div>
                        <h5>مراقبة وتنبيهات</h5>
                        <p>Prometheus و Grafana وسجلات مركزية للكشف المبكر عن محاولات اختراق أو أعطال.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="glass-panel service-offer-card animate-on-scroll">
                        <div class="service-offer-icon"><i class="fas fa-file-shield"></i></div>
                        <h5>تقرير وامتثال</h5>
                        <p>توثيق الوضع الأمني، توصيات قابلة للتنفيذ، ومتابعة بعد المعالجة (اختياري).</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('frontend.partials.security-tools-catalog')

    @include('frontend.partials.security-standards-grid')

    <section class="section-padding service-related-section">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge">تخصصات أخرى</span>
                <h2>خدمات ذات صلة</h2>
                <p>خدمات استضافة وتشغيل تكمّل حزمة الأمن السيبراني</p>
            </div>
            <div class="row g-4">
                @php
                    $relatedServices = [
                        [
                            'url' => route('frontend.service-detail-servers'),
                            'icon' => 'fas fa-server',
                            'title' => 'إدارة السيرفرات',
                            'desc' => 'خوادم مُؤمَّنة ومراقبة',
                            'accent' => '#0057B8',
                        ],
                        [
                            'url' => route('frontend.service-detail-devops'),
                            'icon' => 'fab fa-docker',
                            'title' => 'DevOps',
                            'desc' => 'CI/CD آمن وحاويات',
                            'accent' => '#2496ed',
                        ],
                        [
                            'url' => route('frontend.domain-search'),
                            'icon' => 'fas fa-globe',
                            'title' => 'بحث النطاقات',
                            'desc' => 'نطاقات مع DNS آمن',
                            'accent' => '#2E9AD0',
                        ],
                        [
                            'url' => route('frontend.packages'),
                            'icon' => 'fas fa-box-open',
                            'title' => 'باقات الاستضافة',
                            'desc' => 'استضافة مع حماية مدمجة',
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

    <section class="cta-section">
        <div class="container animate-on-scroll">
            <h2>هل تحتاج تدقيقاً أمنياً أو تأمين بنيتك؟</h2>
            <p>تواصل معنا — نحدّد نطاق العمل ونُعدّ خطة حماية تناسب موقعك أو مشروعك السحابي</p>
            <a href="{{ route('frontend.contact') }}" class="btn-light-custom">
                <i class="fas fa-paper-plane"></i> تواصل معنا
            </a>
        </div>
    </section>
@endsection
