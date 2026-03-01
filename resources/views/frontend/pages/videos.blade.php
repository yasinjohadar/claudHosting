@extends('frontend.layouts.master')

@section('page-title')
الفيديوهات | استضافة كلاودسوفت
@endsection

@section('meta-description')
فيديوهات تعليمية من استضافة كلاودسوفت — تطوير الويب، البرمجة، الاستضافة وتطبيقات الموبايل. مقاطع عملية من قناتنا على يوتيوب.
@endsection

@section('content')
    <section class="page-banner page-banner-about">
        <div class="page-banner-overlay"></div>
        <div class="container position-relative">
            <div class="page-banner-content animate-on-scroll">
                <div class="page-banner-icon"><i class="fas fa-play-circle"></i></div>
                <h1 class="page-banner-title">فيديوهاتي <span>التعليمية</span></h1>
                <p class="page-banner-desc">مقاطع فيديو تعليمية وعملية من قناتي على يوتيوب في تطوير الويب، البرمجة، وتطبيقات الموبايل</p>
                <nav class="page-banner-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ url('/') }}">الرئيسية</a>
                    <span class="page-banner-sep">/</span>
                    <span>الفيديوهات</span>
                </nav>
            </div>
        </div>
        <div class="page-banner-shape"></div>
    </section>

    <section class="section-padding">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge">القناة</span>
                <h2>مقاطع فيديو تعليمية وعملية</h2>
                <p>فيديوهات من قناتي على يوتيوب في تطوير الويب، البرمجة، وتطبيقات الموبايل</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="glass-panel video-card animate-on-scroll animate-delay-1">
                        <a href="https://youtube.com" target="_blank" rel="noopener noreferrer" class="video-wrapper d-block text-decoration-none">
                            <img src="{{ asset('frontend/assets/images/course-webdev.svg') }}" alt="أساسيات تطوير الويب" width="400" height="200" loading="lazy">
                            <div class="play-btn"><i class="fas fa-play-circle"></i></div>
                        </a>
                        <div class="video-body"><h6>أساسيات تطوير الويب</h6><span><i class="fas fa-eye"></i> 15,000 مشاهدة</span></div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="glass-panel video-card animate-on-scroll animate-delay-2">
                        <a href="https://youtube.com" target="_blank" rel="noopener noreferrer" class="video-wrapper d-block text-decoration-none">
                            <img src="{{ asset('frontend/assets/images/course-python.svg') }}" alt="مقدمة في لغة بايثون" width="400" height="200" loading="lazy">
                            <div class="play-btn"><i class="fas fa-play-circle"></i></div>
                        </a>
                        <div class="video-body"><h6>مقدمة في لغة بايثون</h6><span><i class="fas fa-eye"></i> 12,000 مشاهدة</span></div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="glass-panel video-card animate-on-scroll animate-delay-3">
                        <a href="https://youtube.com" target="_blank" rel="noopener noreferrer" class="video-wrapper d-block text-decoration-none">
                            <img src="{{ asset('frontend/assets/images/course-mobile.svg') }}" alt="بناء تطبيق Flutter" width="400" height="200" loading="lazy">
                            <div class="play-btn"><i class="fas fa-play-circle"></i></div>
                        </a>
                        <div class="video-body"><h6>بناء تطبيق متكامل بـ Flutter</h6><span><i class="fas fa-eye"></i> 8,500 مشاهدة</span></div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="glass-panel video-card animate-on-scroll animate-delay-1">
                        <a href="https://youtube.com" target="_blank" rel="noopener noreferrer" class="video-wrapper d-block text-decoration-none">
                            <img src="{{ asset('frontend/assets/images/course-webdev.svg') }}" alt="React للمبتدئين" width="400" height="200" loading="lazy">
                            <div class="play-btn"><i class="fas fa-play-circle"></i></div>
                        </a>
                        <div class="video-body"><h6>React.js للمبتدئين</h6><span><i class="fas fa-eye"></i> 9,200 مشاهدة</span></div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="glass-panel video-card animate-on-scroll animate-delay-2">
                        <a href="https://youtube.com" target="_blank" rel="noopener noreferrer" class="video-wrapper d-block text-decoration-none">
                            <img src="{{ asset('frontend/assets/images/course-python.svg') }}" alt="الذكاء الاصطناعي" width="400" height="200" loading="lazy">
                            <div class="play-btn"><i class="fas fa-play-circle"></i></div>
                        </a>
                        <div class="video-body"><h6>مقدمة في الذكاء الاصطناعي</h6><span><i class="fas fa-eye"></i> 11,000 مشاهدة</span></div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="glass-panel video-card animate-on-scroll animate-delay-3">
                        <a href="https://youtube.com" target="_blank" rel="noopener noreferrer" class="video-wrapper d-block text-decoration-none">
                            <img src="{{ asset('frontend/assets/images/course-mobile.svg') }}" alt="Node.js و Express" width="400" height="200" loading="lazy">
                            <div class="play-btn"><i class="fas fa-play-circle"></i></div>
                        </a>
                        <div class="video-body"><h6>Node.js و Express من الصفر</h6><span><i class="fas fa-eye"></i> 7,800 مشاهدة</span></div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-5 animate-on-scroll">
                <a href="https://youtube.com" target="_blank" rel="noopener noreferrer" class="btn-primary-custom"><i class="fab fa-youtube"></i> اشترك في القناة</a>
            </div>
        </div>
    </section>
@endsection
