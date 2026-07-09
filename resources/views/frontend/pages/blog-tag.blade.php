@extends('frontend.layouts.master')

@section('content')
    <section class="page-banner page-banner-about">
        <div class="page-banner-overlay"></div>
        <div class="container position-relative">
            <div class="page-banner-content animate-on-scroll">
                <div class="page-banner-icon"><i class="fas fa-tags"></i></div>
                <h1 class="page-banner-title">{{ $tag->name }} <span>— وسم</span></h1>
                @if($tag->description)
                    <p class="page-banner-desc">{{ $tag->description }}</p>
                @endif
                <nav class="page-banner-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ url('/') }}">الرئيسية</a>
                    <span class="page-banner-sep">/</span>
                    <a href="{{ route('frontend.blog') }}">المدونة</a>
                    <span class="page-banner-sep">/</span>
                    <span>{{ $tag->name }}</span>
                </nav>
            </div>
        </div>
        <div class="page-banner-shape"></div>
    </section>

    <section class="section-padding" id="blog-list">
        <div class="container">
            @if($posts->count() > 0)
                <div class="row g-4 blog-cards-grid">
                    @foreach ($posts as $index => $post)
                        <div class="col-lg-3 col-md-6">
                            @include('frontend.partials.blog-card', ['post' => $post, 'index' => $index, 'excerptLimit' => 120])
                        </div>
                    @endforeach
                </div>
                <div class="d-flex justify-content-center mt-5">
                    {{ $posts->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <p class="text-muted">لا توجد تدوينات بهذا الوسم حالياً.</p>
                    <a href="{{ route('frontend.blog') }}" class="btn-primary-custom mt-3"><i class="fas fa-newspaper"></i> كل المقالات</a>
                </div>
            @endif
        </div>
    </section>
@endsection
