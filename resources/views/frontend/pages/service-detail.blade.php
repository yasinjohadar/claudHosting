@extends('frontend.layouts.master')

@section('page-title')
تطوير تطبيقات الويب | استضافة كلاودسوفت
@endsection

@section('content')
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
    <section class="section-padding">
        <div class="container">
            <div class="glass-panel animate-on-scroll p-4">
                <h2 class="mb-3">خدمات القيمة المضافة</h2>
                <p class="text-secondary">خدمات إضافية مثل إدارة النطاقات، شهادات الحماية المتقدمة، التكامل مع منصات الدفع، وخدمات CDN لتسريع تحميل المواقع حول العالم.</p>
                <a href="{{ route('frontend.packages') }}" class="btn-primary-custom mt-3"><i class="fas fa-server"></i> تصفّح الباقات</a>
            </div>
        </div>
    </section>
@endsection
