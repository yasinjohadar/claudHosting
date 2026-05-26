@extends('frontend.layouts.master')

@section('page-title')
المدونة | استضافة كلاودسوفت
@endsection

@section('meta-description')
المدونة — مقالات تقنية وتعليمية في الاستضافة، البرمجة، إدارة الخوادم، والأمان. نصائح ودروس من فريق استضافة كلاودسوفت لمساعدتك في مشاريعك.
@endsection

@section('content')
    <!-- ============ PAGE BANNER (نفس About) ============ -->
    <section class="page-banner page-banner-about">
        <div class="page-banner-overlay"></div>
        <div class="container position-relative">
            <div class="page-banner-content animate-on-scroll">
                <div class="page-banner-icon"><i class="fas fa-newspaper"></i></div>
                <h1 class="page-banner-title">المدونة <span>— آخر التدوينات</span></h1>
                <p class="page-banner-desc">مقالات تقنية وتعليمية في عالم البرمجة، الاستضافة، والتكنولوجيا.</p>
                <nav class="page-banner-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ url('/') }}">الرئيسية</a>
                    <span class="page-banner-sep">/</span>
                    <span>المدونة</span>
                </nav>
            </div>
        </div>
        <div class="page-banner-shape"></div>
    </section>

    <!-- ============ BLOG LIST ============ -->
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
                <p class="text-muted">لا توجد تدوينات منشورة حالياً.</p>
                <a href="{{ url('/') }}#blog" class="btn-primary-custom mt-3"><i class="fas fa-home"></i> العودة للرئيسية</a>
            </div>
            @endif
            <div class="text-center mt-4 animate-on-scroll">
                <a href="{{ url('/') }}#blog" class="btn-outline-custom">
                    <i class="fas fa-home"></i> العودة للرئيسية
                </a>
            </div>
        </div>
    </section>
@endsection
