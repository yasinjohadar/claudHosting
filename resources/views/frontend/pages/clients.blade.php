@extends('frontend.layouts.master')

@section('page-title')
الشركات والعملاء | استضافة كلاودسوفت
@endsection

@section('content')
    <section class="page-banner page-banner-clients">
        <div class="page-banner-overlay"></div>
        <div class="container position-relative">
            <div class="page-banner-content animate-on-scroll">
                <div class="page-banner-icon"><i class="fas fa-handshake"></i></div>
                <h1 class="page-banner-title">الشركات <span>والعملاء</span></h1>
                <p class="page-banner-desc">شكراً لكل من وثق بي — شركات وعملاء كرام تعاملت معهم بامتنان واحترام</p>
                <nav class="page-banner-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ url('/') }}">الرئيسية</a>
                    <span class="page-banner-sep">/</span>
                    <span>الشركات والعملاء</span>
                </nav>
            </div>
        </div>
        <div class="page-banner-shape"></div>
    </section>

    <section class="section-padding">
        <div class="container">
            <div class="clients-intro text-center mx-auto animate-on-scroll" style="max-width: 720px;">
                <span class="section-badge">امتنان</span>
                <h2 class="mb-3">ثقة غالية نقدّرها</h2>
                <p class="text-secondary mb-0">كل شركة وكل عميل تعاملت معه كان جزءاً من رحلتي — أقدّر الثقة والتعاون المثمر، وأضع هنا كلمة شكر وعرفان لهم.</p>
            </div>
        </div>
    </section>

    <section class="section-padding" style="background: var(--clr-bg-secondary);">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="glass-panel client-card animate-on-scroll">
                        <div class="client-card-logo"><img src="{{ asset('frontend/assets/images/logo.png') }}" alt="شعار عميل استضافة 1" width="80" height="80" loading="lazy"></div>
                        <span class="client-card-type">شركة</span>
                        <h3 class="client-card-name">اسم الشركة الأولى</h3>
                        <p class="client-card-desc">شركة رائدة في مجالها، تعاملت معها بكل احترافية وشفافية. أشكرهم على الثقة والتعاون المثمر في تنفيذ المشروع.</p>
                        <blockquote class="client-card-quote">"شريك موثوق يلتزم بالمواعيد والجودة."</blockquote>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="glass-panel client-card animate-on-scroll">
                        <div class="client-card-logo"><img src="{{ asset('frontend/assets/images/logo.png') }}" alt="شعار عميل استضافة 2" width="80" height="80" loading="lazy"></div>
                        <span class="client-card-type">عميل</span>
                        <h3 class="client-card-name">عميل / مشروع ثانٍ</h3>
                        <p class="client-card-desc">عميل كريم كان واضحاً في المتطلبات ومتعاوناً طوال التنفيذ. أقدّر صبره وثقته وأتمنى له التوفيق دوماً.</p>
                        <blockquote class="client-card-quote">"تجربة سلسة ونتيجة تفوق التوقعات."</blockquote>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="glass-panel client-card animate-on-scroll">
                        <div class="client-card-logo"><img src="{{ asset('frontend/assets/images/logo.png') }}" alt="شعار عميل استضافة 3" width="80" height="80" loading="lazy"></div>
                        <span class="client-card-type">شركة</span>
                        <h3 class="client-card-name">شركة تقنية</h3>
                        <p class="client-card-desc">تعاون مميز في مشروع تطوير ويب وتدريب الفريق. فريقهم المحترم جعل العمل متعة وحققنا أهدافاً مشتركة.</p>
                        <blockquote class="client-card-quote">"احترافية عالية وتواصل ممتاز."</blockquote>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="glass-panel client-card animate-on-scroll">
                        <div class="client-card-logo"><img src="{{ asset('frontend/assets/images/logo.png') }}" alt="شعار عميل استضافة 4" width="80" height="80" loading="lazy"></div>
                        <span class="client-card-type">عميل</span>
                        <h3 class="client-card-name">متجر / مشروع تجاري</h3>
                        <p class="client-card-desc">مشروع متجر إلكتروني نُفّذ بالكامل مع الدعم والتدريب. أشكر صاحب المشروع على حسن الاستقبال والتقييم الإيجابي.</p>
                        <blockquote class="client-card-quote">"التزام بالوقت وجودة في التنفيذ."</blockquote>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="glass-panel client-card animate-on-scroll">
                        <div class="client-card-logo"><img src="{{ asset('frontend/assets/images/logo.png') }}" alt="شعار عميل استضافة 5" width="80" height="80" loading="lazy"></div>
                        <span class="client-card-type">جهة تدريب</span>
                        <h3 class="client-card-name">مركز أو أكاديمية</h3>
                        <p class="client-card-desc">شراكة تدريبية مع جهة تعليمية. تقديري الكبير لإدارة المركز وطلابهم على الجدية والتفاعل خلال الدورات.</p>
                        <blockquote class="client-card-quote">"مدرب متميز ومحتوى عملي قيّم."</blockquote>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="glass-panel client-card animate-on-scroll">
                        <div class="client-card-logo"><img src="{{ asset('frontend/assets/images/logo.png') }}" alt="شعار عميل استضافة 6" width="80" height="80" loading="lazy"></div>
                        <span class="client-card-type">عميل</span>
                        <h3 class="client-card-name">عميل / مشروع تطبيق</h3>
                        <p class="client-card-desc">مشروع تطبيق جوال من الفكرة حتى النشر. أشكر العميل على الثقة والمرونة في اتخاذ القرارات المشتركة.</p>
                        <blockquote class="client-card-quote">"تعاون رائع ونتيجة نفتخر بها."</blockquote>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-padding">
        <div class="container">
            <div class="glass-panel clients-closing text-center py-5 px-4 animate-on-scroll">
                <i class="fas fa-heart mb-3" style="font-size: 2.5rem; color: var(--clr-primary);"></i>
                <h3 class="mb-2">شكراً لكم</h3>
                <p class="text-secondary mb-0 mx-auto" style="max-width: 560px;">كل اسم في هذه الصفحة يمثّل ثقة غالية وذكرى تعاون نقدّرها. نتمنى لكم التوفيق ونبقى في خدمتكم.</p>
            </div>
        </div>
    </section>
@endsection
