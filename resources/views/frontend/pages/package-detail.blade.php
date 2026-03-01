@extends('frontend.layouts.master')

@section('page-title')
{{ $product->name }} | استضافة كلاودسوفت
@endsection

@section('content')
    <section class="course-detail-hero">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-8 order-2 order-lg-1">
                    <div class="animate-on-scroll">
                        <div class="breadcrumb-custom" style="justify-content: flex-start; margin-bottom: 15px;">
                            <a href="{{ url('/') }}">الرئيسية</a><span>/</span><a href="{{ route('frontend.packages') }}">الباقات</a><span>/</span><span>{{ $product->name }}</span>
                        </div>
                        <h1 class="cd-title">{{ $product->name }}</h1>
                        <p class="text-secondary">{{ Str::limit(strip_tags($product->description ?? ''), 200) ?: 'باقة استضافة مناسبة لاحتياجاتك.' }}</p>
                        @auth
                            <a href="{{ route('frontend.package.order.form', $product->id) }}" class="btn-primary-custom mt-3"><i class="fas fa-shopping-cart"></i> اطلب الباقة</a>
                        @else
                            <a href="{{ route('frontend.package.order.form', $product->id) }}" class="btn-primary-custom mt-3"><i class="fas fa-sign-in-alt"></i> سجّل الدخول لطلب الباقة</a>
                        @endauth
                        <a href="{{ route('frontend.contact') }}?package={{ urlencode($product->name) }}" class="btn-outline-custom mt-3 ms-2"><i class="fas fa-paper-plane"></i> تواصل معنا للاستفسار</a>
                        <a href="{{ route('frontend.packages') }}" class="btn-outline-custom mt-3 ms-2"><i class="fas fa-server"></i> جميع الباقات</a>
                    </div>
                </div>
                <div class="col-lg-4 order-1 order-lg-2">
                    <div class="animate-on-scroll text-center">
                        <i class="fas fa-server fa-5x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-padding" style="background: var(--clr-bg-secondary);">
        <div class="container">
            <div class="glass-panel animate-on-scroll p-4">
                <h2 class="mb-3">تفاصيل الباقة</h2>
                @if($product->description)
                    <div class="mb-4">{!! $product->description !!}</div>
                @endif

                @php
                    $pricing = $product->pricing;
                    $firstCurrency = is_array($pricing) ? reset($pricing) : null;
                    $cycles = [
                        'monthly' => ['label' => 'شهري', 'setup' => 'msetupfee'],
                        'quarterly' => ['label' => 'ربع سنوي', 'setup' => 'qsetupfee'],
                        'semiannually' => ['label' => 'نصف سنوي', 'setup' => 'ssetupfee'],
                        'annually' => ['label' => 'سنوي', 'setup' => 'asetupfee'],
                        'biennially' => ['label' => 'كل سنتين', 'setup' => 'bsetupfee'],
                        'triennially' => ['label' => 'كل ثلاث سنوات', 'setup' => 'tsetupfee'],
                    ];
                @endphp
                @if(is_array($firstCurrency) && !empty($firstCurrency))
                <h3 class="h5 mb-3">جدول التسعير</h3>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>دورة الفوترة</th>
                                <th>السعر</th>
                                <th>رسوم الإعداد</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cycles as $key => $info)
                                @if(!empty($firstCurrency[$key]) && $firstCurrency[$key] !== '-1.00')
                                <tr>
                                    <td>{{ $info['label'] }}</td>
                                    <td>{{ $firstCurrency[$key] }} $</td>
                                    <td>{{ $firstCurrency[$info['setup']] ?? '0' }} $</td>
                                </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                <div class="mt-4">
                    <a href="{{ route('frontend.package.order.form', $product->id) }}" class="btn-primary-custom"><i class="fas fa-shopping-cart"></i> اطلب الباقة</a>
                    <a href="{{ route('frontend.contact') }}?package={{ urlencode($product->name) }}" class="btn-outline-custom ms-2"><i class="fas fa-paper-plane"></i> تواصل معنا للاستفسار</a>
                </div>
            </div>
        </div>
    </section>
@endsection
