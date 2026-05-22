@extends('frontend.layouts.master')

@section('page-title')
آراء العملاء | استضافة كلاودسوفت
@endsection

@section('meta-description')
آراء عملاء استضافة كلاودسوفت — تجارب حقيقية وتقييمات من أصحاب مواقع ومتاجر اختاروا خدماتنا. اقرأ آراءهم واختر الباقة المناسبة.
@endsection

@section('content')
    <section class="page-banner page-banner-about">
        <div class="page-banner-overlay"></div>
        <div class="container position-relative">
            <div class="page-banner-content animate-on-scroll">
                <div class="page-banner-icon"><i class="fas fa-quote-right"></i></div>
                <h1 class="page-banner-title">آراء <span>عملائنا</span></h1>
                <p class="page-banner-desc">تجارب حقيقية وتقييمات من عملاء اختاروا استضافة كلاودسوفت لمواقعهم ومتاجرهم الإلكترونية</p>
                <nav class="page-banner-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ url('/') }}">الرئيسية</a>
                    <span class="page-banner-sep">/</span>
                    <span>آراء العملاء</span>
                </nav>
            </div>
        </div>
        <div class="page-banner-shape"></div>
    </section>

    <section class="section-padding">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge">تجارب حقيقية</span>
                <h2>ماذا يقول عملاؤنا</h2>
                <p>آراء وتجارب بعض عملائنا حول جودة الاستضافة، سرعة الخوادم، ومستوى الدعم الفني</p>
            </div>
            @php
                $testimonials = [
                    [
                        'name' => 'أحمد محمد',
                        'role' => 'مطور ويب — سوريا',
                        'quote' => 'دورة تطوير الويب كانت نقطة تحول في مسيرتي المهنية. أسلوب الشرح ممتاز والتطبيقات العملية رائعة. أنصح الجميع بالتسجيل!',
                        'stars' => 5,
                    ],
                    [
                        'name' => 'سارة العلي',
                        'role' => 'مهندسة برمجيات — الأردن',
                        'quote' => 'فريق كلاودسوفت من أفضل مزودي الاستضافة. الدعم سريع، الخوادم مستقرة، والمحتوى التعليمي محدث. استفدت كثيراً من باقة VPS.',
                        'stars' => 5,
                    ],
                    [
                        'name' => 'عمر حسان',
                        'role' => 'مطور تطبيقات — العراق',
                        'quote' => 'تعلمت Flutter من دورة الموبايل وقمت ببناء أول تطبيق لي خلال شهرين فقط! الدعم الفني والمتابعة من المدرب كانت ممتازة.',
                        'stars' => 4.5,
                    ],
                    [
                        'name' => 'محمد خالد',
                        'role' => 'مطور Backend — مصر',
                        'quote' => 'باقة Node.js فتحت لي آفاقاً جديدة. الاستضافة السحابية ساعدتني في تشغيل أول تطبيق بشكل موثوق. شكراً كلاودسوفت!',
                        'stars' => 5,
                    ],
                    [
                        'name' => 'نور الدين',
                        'role' => 'مطور ويب — تونس',
                        'quote' => 'المحتوى منظم جداً والتمارين متنوعة. تحولت من مبتدئ إلى قادر على بناء مواقع كاملة بفضل دورة تطوير الويب الشاملة.',
                        'stars' => 5,
                    ],
                    [
                        'name' => 'لينا أحمد',
                        'role' => 'مطورة — لبنان',
                        'quote' => 'أفضل استثمار قمت به في تعلم البرمجة. المدرب يرد على الاستفسارات بسرعة ويشرح بأمثلة من الواقع. أنصح بشدة.',
                        'stars' => 4.5,
                    ],
                ];
            @endphp
            @include('frontend.partials.testimonials-list', ['testimonials' => $testimonials])
            <div class="text-center mt-5 animate-on-scroll">
                <a href="{{ route('frontend.packages') }}" class="btn-primary-custom"><i class="fas fa-server"></i> تصفّح الباقات</a>
            </div>
        </div>
    </section>
@endsection
