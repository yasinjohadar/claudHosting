@extends('frontend.layouts.master')

@section('page-title')
الباقات | استضافة كلاودسوفت
@endsection

@section('meta-description')
باقات استضافة كلاودسوفت — خطط مرنة للمواقع الشخصية والمتاجر والشركات. استضافة سريعة وآمنة مع دعم فني متواصل ولوحة تحكم عربية. اختر باقتك وابدأ اليوم.
@endsection

@section('content')
    <!-- ============ PAGE HERO ============ -->
    <section class="section-padding" style="padding-top: 8rem;">
        <div class="container">
            <div class="section-header animate-on-scroll text-center">
                <span class="section-badge">باقات الاستضافة</span>
                <h1>اختر الباقة المناسبة لموقعك</h1>
                <p class="mx-auto" style="max-width: 600px;">خطط استضافة مرنة وآمنة تناسب المواقع الشخصية والمتاجر الإلكترونية والشركات. ابدأ اليوم مع دعم فني متواصل.</p>
            </div>
        </div>
    </section>

    <!-- ============ HOSTING PACKAGES ============ -->
    <section class="section-padding" id="packages" style="background: var(--clr-bg-secondary);">
        <div class="container">
            @if(isset($products) && $products->isNotEmpty())
            <div class="row g-4">
                @foreach($products as $index => $product)
                <div class="col-lg-4 col-md-6">
                    <a href="{{ route('frontend.package-detail', $product->id) }}" class="glass-panel course-card animate-on-scroll animate-delay-{{ ($index % 3) + 1 }}" style="text-decoration:none;color:inherit;cursor:pointer;height:100%;display:block;">
                        <div class="course-img-wrapper">
                            <i class="fas fa-server fa-3x text-white"></i>
                            <span class="course-badge">{{ $product->group_name ?? 'باقة' }}</span>
                        </div>
                        <div class="course-body">
                            <h5>{{ $product->name }}</h5>
                            <p>{{ Str::limit(strip_tags($product->description ?? ''), 120) ?: 'باقة استضافة مناسبة لاحتياجاتك.' }}</p>
                            <ul class="course-features">
                                <li><i class="fas fa-check"></i> {{ $product->type_name ?? $product->type }}</li>
                                <li><i class="fas fa-check"></i> دورة فوترة: {{ $product->billingcycle ?? 'شهري' }}</li>
                                <li><i class="fas fa-check"></i> {{ $product->availability_status }}</li>
                            </ul>
                        </div>
                        <div class="course-footer">
                            <span class="price">{{ $product->price }} $ / {{ $product->billingcycle ? 'شهرياً' : '' }}</span>
                        </div>
                    </a>
                </div>
                @endforeach
            </div>
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
