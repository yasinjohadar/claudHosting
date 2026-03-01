@extends('frontend.layouts.master')

@section('page-title')
إدارة الخوادم والبنية التحتية | استضافة كلاودسوفت
@endsection

@section('content')
    <section class="page-banner page-banner-service">
        <div class="page-banner-overlay"></div>
        <div class="container position-relative">
            <div class="page-banner-content animate-on-scroll">
                <div class="page-banner-icon"><i class="fas fa-server"></i></div>
                <h1 class="page-banner-title">إدارة الخوادم <span>والبنية التحتية</span></h1>
                <p class="page-banner-desc">إدارة وإعداد الخوادم السحابية و VPS مع مراقبة مستمرة وموازنة أحمال</p>
                <nav class="page-banner-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ url('/') }}">الرئيسية</a>
                    <span class="page-banner-sep">/</span>
                    <a href="{{ route('frontend.about') }}#specialties">التخصصات</a>
                    <span class="page-banner-sep">/</span>
                    <span>إدارة الخوادم</span>
                </nav>
            </div>
        </div>
        <div class="page-banner-shape"></div>
    </section>
    <section class="section-padding">
        <div class="container">
            <div class="glass-panel animate-on-scroll p-4">
                <p class="text-secondary mb-0">إدارة الخوادم السحابية و VPS مع مراقبة مستمرة، موازنة أحمال، وتحديثات أمان دورية.</p>
                <a href="{{ route('frontend.packages') }}" class="btn-primary-custom mt-3"><i class="fas fa-server"></i> الباقات</a>
            </div>
        </div>
    </section>
@endsection
