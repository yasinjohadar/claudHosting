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
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="glass-panel testimonial-card animate-on-scroll animate-delay-1">
                        <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                        <p class="quote-text">"دورة تطوير الويب كانت نقطة تحول في مسيرتي المهنية. أسلوب الشرح ممتاز والتطبيقات العملية رائعة. أنصح الجميع بالتسجيل!"</p>
                        <div class="student-info"><div><div class="student-name">أحمد محمد</div><div class="student-role">مطور ويب - سوريا</div></div></div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="glass-panel testimonial-card animate-on-scroll animate-delay-2">
                        <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                        <p class="quote-text">"فريق كلاودسوفت من أفضل مزودي الاستضافة. الدعم سريع، الخوادم مستقرة، والمحتوى التعليمي محدث. استفدت كثيراً من باقة VPS."</p>
                        <div class="student-info"><div><div class="student-name">سارة العلي</div><div class="student-role">مهندسة برمجيات - الأردن</div></div></div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="glass-panel testimonial-card animate-on-scroll animate-delay-3">
                        <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i></div>
                        <p class="quote-text">"تعلمت Flutter من دورة الموبايل وقمت ببناء أول تطبيق لي خلال شهرين فقط! الدعم الفني والمتابعة من المدرب كانت ممتازة."</p>
                        <div class="student-info"><div><div class="student-name">عمر حسان</div><div class="student-role">مطور تطبيقات - العراق</div></div></div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="glass-panel testimonial-card animate-on-scroll animate-delay-1">
                        <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                        <p class="quote-text">"باقة Node.js فتحت لي آفاقاً جديدة. الاستضافة السحابية ساعدتني في تشغيل أول تطبيق بشكل موثوق. شكراً كلاودسوفت!"</p>
                        <div class="student-info"><div><div class="student-name">محمد خالد</div><div class="student-role">مطور Backend - مصر</div></div></div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="glass-panel testimonial-card animate-on-scroll animate-delay-2">
                        <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                        <p class="quote-text">"المحتوى منظم جداً والتمارين متنوعة. تحولت من مبتدئ إلى قادر على بناء مواقع كاملة بفضل دورة تطوير الويب الشاملة."</p>
                        <div class="student-info"><div><div class="student-name">نور الدين</div><div class="student-role">مطور ويب - تونس</div></div></div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="glass-panel testimonial-card animate-on-scroll animate-delay-3">
                        <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i></div>
                        <p class="quote-text">"أفضل استثمار قمت به في تعلم البرمجة. المدرب يرد على الاستفسارات بسرعة ويشرح بأمثلة من الواقع. أنصح بشدة."</p>
                        <div class="student-info"><div><div class="student-name">لينا أحمد</div><div class="student-role">مطورة - لبنان</div></div></div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-5 animate-on-scroll">
                <a href="{{ route('frontend.packages') }}" class="btn-primary-custom"><i class="fas fa-server"></i> تصفّح الباقات</a>
            </div>
        </div>
    </section>
@endsection
