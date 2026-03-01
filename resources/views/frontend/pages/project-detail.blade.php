@extends('frontend.layouts.master')

@section('page-title')
تفاصيل المشروع | استضافة كلاودسوفت
@endsection

@section('meta-description')
تفاصيل مشروع مستضاف على بنية كلاودسوفت السحابية — موقع شركة استشارات تقنية مع خدمات، مدونة ونماذج تواصل. استضافة موثوقة وأداء عالٍ.
@endsection

@section('content')
    <section class="page-banner page-banner-projects page-banner-project-detail">
        <div class="page-banner-overlay"></div>
        <div class="container position-relative">
            <div class="page-banner-content animate-on-scroll">
                <div class="page-banner-icon"><i class="fas fa-folder-open"></i></div>
                <h1 class="page-banner-title">تفاصيل المشروع</h1>
                <p class="page-banner-desc">مشروع مستضاف على بنية كلاودسوفت السحابية</p>
                <nav class="page-banner-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ url('/') }}">الرئيسية</a>
                    <span class="page-banner-sep">/</span>
                    <a href="{{ route('frontend.projects') }}">المشاريع</a>
                    <span class="page-banner-sep">/</span>
                    <span>المشروع</span>
                </nav>
            </div>
        </div>
        <div class="page-banner-shape"></div>
    </section>
    <section class="section-padding">
        <div class="container">
            <div class="glass-panel animate-on-scroll p-4">
                <h2 class="mb-3">موقع شركة استشارات تقنية</h2>
                <p class="text-secondary">موقع تعريفي لشركة استشارات تقنية يستضيف صفحات الخدمات، المدونة، ونماذج التواصل على استضافة كلاودسوفت السحابية.</p>
                <a href="{{ route('frontend.projects') }}" class="btn-primary-custom mt-3"><i class="fas fa-folder-open"></i> جميع المشاريع</a>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
<script src="{{ asset('frontend/assets/js/project-detail.js') }}"></script>
@endsection
