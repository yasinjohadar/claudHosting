@php
    // نص النشرة البريدية الخاص بهذه الصفحة (يقرأه كمبوننت newsletter-section)
    $nlTitle = 'تابع عروض الاستضافة أولاً بأول';
    $nlDesc = 'نرسل لك العروض الموسمية وتحديثات الباقات والموارد الجديدة قبل الجميع.';
@endphp

@extends('frontend.layouts.master')

@section('content')
    <!-- ============ PAGE BANNER (نفس About) ============ -->
    <section class="page-banner page-banner-about">
        <div class="page-banner-overlay"></div>
        <div class="container position-relative">
            <div class="page-banner-content animate-on-scroll">
                <div class="page-banner-icon"><i class="fas fa-server"></i></div>
                <h1 class="page-banner-title">الباقات <span>— اختر المناسبة لموقعك</span></h1>
                <p class="page-banner-desc">خطط استضافة مرنة وآمنة تناسب المواقع الشخصية والمتاجر الإلكترونية والشركات. ابدأ اليوم مع دعم فني متواصل.</p>
                <nav class="page-banner-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ url('/') }}">الرئيسية</a>
                    <span class="page-banner-sep">/</span>
                    <span>الباقات</span>
                </nav>
            </div>
        </div>
        <div class="page-banner-shape"></div>
    </section>

    <!-- ============ HOSTING PACKAGES ============ -->
    <section class="section-padding packages-section" id="packages">
        <div class="container">
            @if(isset($products) && $products->isNotEmpty())
            <div class="row g-4 align-items-stretch">
                @foreach($products as $index => $product)
                <div class="col-lg-4 col-md-6 d-flex">
                    @include('frontend.partials.package-card', ['product' => $product, 'index' => $index, 'featureLimit' => 10, 'showAvailability' => true])
                </div>
                @endforeach
            </div>
            <ul class="packages-assurance animate-on-scroll">
                <li>
                    <i class="fas fa-shield-halved" aria-hidden="true"></i>
                    <div>
                        <strong>شهادة SSL مجانية</strong>
                        <span>تشفير كامل لكل نطاق</span>
                    </div>
                </li>
                <li>
                    <i class="fas fa-rotate" aria-hidden="true"></i>
                    <div>
                        <strong>نسخ احتياطي دوري</strong>
                        <span>استعادة سريعة عند الحاجة</span>
                    </div>
                </li>
                <li>
                    <i class="fas fa-headset" aria-hidden="true"></i>
                    <div>
                        <strong>دعم فني 24/7</strong>
                        <span>فريق عربي على مدار الساعة</span>
                    </div>
                </li>
                <li>
                    <i class="fas fa-bolt" aria-hidden="true"></i>
                    <div>
                        <strong>تفعيل فوري</strong>
                        <span>موقعك يعمل خلال دقائق</span>
                    </div>
                </li>
            </ul>
            <div class="text-center mt-5 animate-on-scroll">
                <a href="{{ url('/') }}#packages" class="btn-primary-custom">
                    <i class="fas fa-home"></i> العودة للرئيسية
                </a>
            </div>
            @else
            <div class="text-center py-5 animate-on-scroll">
                <p class="text-muted mb-4">لا توجد باقات معروضة حالياً.</p>
                <a href="{{ url('/') }}" class="btn-primary-custom">
                    <i class="fas fa-home"></i> العودة للرئيسية
                </a>
                <a href="{{ route('frontend.contact') }}" class="btn-outline-custom ms-2">
                    <i class="fas fa-paper-plane"></i> تواصل معنا
                </a>
            </div>
            @endif
        </div>
    </section>
@endsection
