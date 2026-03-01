@extends('frontend.layouts.master')

@section('page-title')
الأمان والحماية | استضافة كلاودسوفت
@endsection

@section('content')
    <section class="page-banner page-banner-service">
        <div class="page-banner-overlay"></div>
        <div class="container position-relative">
            <div class="page-banner-content animate-on-scroll">
                <div class="page-banner-icon"><i class="fas fa-shield-alt"></i></div>
                <h1 class="page-banner-title">الأمان <span>والحماية</span></h1>
                <p class="page-banner-desc">حلول أمان متقدمة لمواقعك وبياناتك</p>
                <nav class="page-banner-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ url('/') }}">الرئيسية</a>
                    <span class="page-banner-sep">/</span>
                    <a href="{{ route('frontend.about') }}#specialties">التخصصات</a>
                    <span class="page-banner-sep">/</span>
                    <span>الأمان والحماية</span>
                </nav>
            </div>
        </div>
        <div class="page-banner-shape"></div>
    </section>
    <section class="section-padding">
        <div class="container">
            <div class="glass-panel animate-on-scroll p-4">
                <p class="text-secondary mb-0">تفاصيل خدمة الأمان والحماية — جدران نارية، SSL، ومراقبة مستمرة.</p>
                <a href="{{ route('frontend.packages') }}" class="btn-primary-custom mt-3"><i class="fas fa-server"></i> الباقات</a>
            </div>
        </div>
    </section>
@endsection
