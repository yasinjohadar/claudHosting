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
                @php
                    $specialties = [
                        [
                            'title' => 'استضافة المواقع والبريد الإلكتروني',
                            'icon' => 'fas fa-code',
                            'desc' => 'استضافة سريعة وآمنة للمواقع والبريد الإلكتروني مع شهادات SSL مجانية، نسخ احتياطي يومي، ولوحة تحكم عربية سهلة الاستخدام.',
                            'chips' => ['cPanel', 'Email Hosting', 'SSL', 'Backups'],
                            'url' => route('frontend.packages'),
                            'cta' => 'استعرض الباقات',
                            'modifier' => 'about-specialty-card--hosting',
                            'delay' => '1',
                        ],
                        [
                            'title' => 'إدارة الخوادم والبنية التحتية',
                            'icon' => 'fas fa-server',
                            'desc' => 'إدارة وإعداد الخوادم السحابية و VPS مع مراقبة مستمرة، موازنة أحمال، وتحديثات أمان دورية لضمان أعلى مستوى من الاعتمادية.',
                            'chips' => ['Linux', 'Docker', 'NGINX', 'Monitoring'],
                            'url' => route('frontend.service-detail-servers'),
                            'cta' => 'اعرف المزيد',
                            'modifier' => 'about-specialty-card--infra',
                            'delay' => '2',
                        ],
                        [
                            'title' => 'خدمات القيمة المضافة',
                            'icon' => 'fas fa-puzzle-piece',
                            'desc' => 'خدمات إضافية مثل إدارة النطاقات، شهادات الحماية المتقدمة، التكامل مع منصات الدفع، وخدمات CDN لتسريع تحميل المواقع حول العالم.',
                            'chips' => ['Domains', 'CDN', 'WAF', 'Payment Integrations'],
                            'url' => route('frontend.service-detail-web'),
                            'cta' => 'اعرف المزيد',
                            'modifier' => 'about-specialty-card--value',
                            'delay' => '3',
                        ],
                    ];
                @endphp
                @foreach ($specialties as $card)
                    <div class="col-lg-4 col-md-6">
                        <article class="glass-panel about-specialty-card {{ $card['modifier'] }} animate-on-scroll animate-delay-{{ $card['delay'] }}">
                            <header class="about-specialty-card__head">
                                <span class="about-specialty-card__icon" aria-hidden="true">
                                    <i class="{{ $card['icon'] }}"></i>
                                </span>
                                <h5 class="about-specialty-card__title">{{ $card['title'] }}</h5>
                            </header>
                            <p class="about-specialty-card__desc">{{ $card['desc'] }}</p>
                            <div class="about-specialty-card__chips">
                                @foreach ($card['chips'] as $chip)
                                    <span class="about-specialty-card__chip">{{ $chip }}</span>
                                @endforeach
                            </div>
                            <a href="{{ $card['url'] }}" class="about-specialty-card__cta btn-outline-custom">
                                <i class="fas fa-arrow-left"></i> {{ $card['cta'] }}
                            </a>
                        </article>
                    </div>
                @endforeach
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
