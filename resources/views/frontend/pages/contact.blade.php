@extends('frontend.layouts.master')

@section('page-title')
تواصل معنا | استضافة كلاودسوفت
@endsection

@section('meta-description')
تواصل مع فريق استضافة كلاودسوفت — للاستفسارات، التسجيل في الباقات، أو طلب استشارة تقنية. نحن هنا لمساعدتك على اختيار الحل المناسب لمشروعك.
@endsection

@section('content')
    <section class="page-banner page-banner-about">
        <div class="page-banner-overlay"></div>
        <div class="container position-relative">
            <div class="page-banner-content animate-on-scroll">
                <div class="page-banner-icon"><i class="fas fa-paper-plane"></i></div>
                <h1 class="page-banner-title">تواصل <span>معنا</span></h1>
                <p class="page-banner-desc">نحن هنا لمساعدتك — للاستفسارات عن الاستضافة، الباقات، أو طلب استشارة تقنية</p>
                <nav class="page-banner-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ url('/') }}">الرئيسية</a>
                    <span class="page-banner-sep">/</span>
                    <span>تواصل معنا</span>
                </nav>
            </div>
        </div>
        <div class="page-banner-shape"></div>
    </section>

    @include('frontend.partials.contact-section')
@endsection
