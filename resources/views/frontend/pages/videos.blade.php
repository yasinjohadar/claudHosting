@extends('frontend.layouts.master')

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

    <section class="section-padding" id="videos">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge">القناة</span>
                <h2>مقاطع فيديو تعليمية وعملية</h2>
                <p>فيديوهات من قناتي على يوتيوب في تطوير الويب، البرمجة، وتطبيقات الموبايل</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6 d-flex">
                    <a href="https://youtube.com" target="_blank" rel="noopener noreferrer"
                        class="glass-panel video-card video-card-link animate-on-scroll animate-delay-1 w-100">
                        <div class="video-wrapper">
                            <img src="{{ asset('frontend/assets/images/course-webdev.svg') }}" alt="أساسيات تطوير الويب" width="400" height="200" loading="lazy">
                            <span class="video-badge-yt"><i class="fab fa-youtube"></i> يوتيوب</span>
                            <div class="play-btn" aria-hidden="true"><i class="fas fa-play"></i></div>
                        </div>
                        <div class="video-body">
                            <h6>أساسيات تطوير الويب</h6>
                            <div class="video-meta">
                                <span class="video-views"><i class="fas fa-eye"></i> 15,000 مشاهدة</span>
                                <span class="video-cta">شاهد الآن <i class="fas fa-arrow-left"></i></span>
                            </div>
                        </div>
                    </a>
                </div>
                @foreach([
                    ['delay' => 2, 'img' => 'course-python.svg', 'alt' => 'مقدمة في لغة بايثون', 'title' => 'مقدمة في لغة بايثون', 'views' => '12,000'],
                    ['delay' => 3, 'img' => 'course-mobile.svg', 'alt' => 'بناء تطبيق Flutter', 'title' => 'بناء تطبيق متكامل بـ Flutter', 'views' => '8,500'],
                    ['delay' => 1, 'img' => 'course-webdev.svg', 'alt' => 'React للمبتدئين', 'title' => 'React.js للمبتدئين', 'views' => '9,200'],
                    ['delay' => 2, 'img' => 'course-python.svg', 'alt' => 'الذكاء الاصطناعي', 'title' => 'مقدمة في الذكاء الاصطناعي', 'views' => '11,000'],
                    ['delay' => 3, 'img' => 'course-mobile.svg', 'alt' => 'Node.js و Express', 'title' => 'Node.js و Express من الصفر', 'views' => '7,800'],
                ] as $video)
                <div class="col-lg-4 col-md-6 d-flex">
                    <a href="https://youtube.com" target="_blank" rel="noopener noreferrer"
                        class="glass-panel video-card video-card-link animate-on-scroll animate-delay-{{ $video['delay'] }} w-100">
                        <div class="video-wrapper">
                            <img src="{{ asset('frontend/assets/images/'.$video['img']) }}" alt="{{ $video['alt'] }}" width="400" height="200" loading="lazy">
                            <span class="video-badge-yt"><i class="fab fa-youtube"></i> يوتيوب</span>
                            <div class="play-btn" aria-hidden="true"><i class="fas fa-play"></i></div>
                        </div>
                        <div class="video-body">
                            <h6>{{ $video['title'] }}</h6>
                            <div class="video-meta">
                                <span class="video-views"><i class="fas fa-eye"></i> {{ $video['views'] }} مشاهدة</span>
                                <span class="video-cta">شاهد الآن <i class="fas fa-arrow-left"></i></span>
                            </div>
                        </div>
                    </a>
                </div>
                @endforeach
            </div>
            <div class="text-center mt-5 animate-on-scroll">
                <a href="https://youtube.com" target="_blank" rel="noopener noreferrer" class="btn-primary-custom"><i class="fab fa-youtube"></i> اشترك في القناة</a>
            </div>
        </div>
    </section>
@endsection
