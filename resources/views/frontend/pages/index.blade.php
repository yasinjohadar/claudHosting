@extends('frontend.layouts.master')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/devicon@2.15.1/devicon.min.css" crossorigin="anonymous">
@endpush

@section('page-title')
استضافة كلاودسوفت | CloudSoft Hosting
@endsection

@section('meta-description')
استضافة كلاودسوفت تمنحك بنية سحابية مستقرة وسريعة وآمنة لموقعك أو متجرك. باقات مرنة من المواقع الشخصية حتى الشركات، لوحة تحكم سهلة ودعم فني مستمر. ابدأ خلال دقائق.
@endsection

@section('content')
    @include('frontend.partials.hero-section', ['hero' => $hero ?? null])

    <!-- ============ SKILLS SECTION ============ -->
    <section class="section-padding" id="skills">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge">مجالات الخدمة</span>
                <h2>التخصصات والخدمات</h2>
                <p>خبرة في مجالات تقنية متعددة من تطوير واستضافة المواقع إلى إدارة الخوادم والأمن والاستشارات</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <a href="{{ route('frontend.service-detail-web') }}" class="glass-panel skill-card skill-card-link animate-on-scroll animate-delay-1">
                        <div class="skill-icon"><i class="fas fa-globe"></i></div>
                        <h5>تطوير تطبيقات الويب</h5>
                        <p>تصميم وتطوير مواقع وتطبيقات ويب حديثة ومتجاوبة واحترافية</p>
                    </a>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <a href="{{ route('frontend.service-detail-mobile') }}" class="glass-panel skill-card skill-card-link animate-on-scroll animate-delay-2">
                        <div class="skill-icon"><i class="fas fa-mobile-alt"></i></div>
                        <h5>تطبيقات الجوال</h5>
                        <p>تطوير تطبيقات الهواتف الذكية متعددة المنصات للأندرويد والـ iOS</p>
                    </a>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <a href="{{ route('frontend.service-detail-security') }}" class="glass-panel skill-card skill-card-link animate-on-scroll animate-delay-3">
                        <div class="skill-icon"><i class="fas fa-shield-alt"></i></div>
                        <h5>أمن المعلومات</h5>
                        <p>حماية الأنظمة والبيانات وتقييم الثغرات وتطبيق أفضل الممارسات الأمنية</p>
                    </a>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <a href="{{ route('frontend.service-detail-servers') }}" class="glass-panel skill-card skill-card-link animate-on-scroll animate-delay-4">
                        <div class="skill-icon"><i class="fas fa-server"></i></div>
                        <h5>إدارة السيرفرات</h5>
                        <p>إعداد وإدارة الخوادم، الاستضافة، والنشر مع Linux والخدمات السحابية</p>
                    </a>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <a href="{{ route('frontend.service-detail-servers') }}" class="glass-panel skill-card skill-card-link animate-on-scroll animate-delay-1">
                        <div class="skill-icon"><i class="fas fa-database"></i></div>
                        <h5>قواعد البيانات</h5>
                        <p>تصميم وإدارة قواعد البيانات SQL و NoSQL وتحسين الأداء</p>
                    </a>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <a href="{{ route('frontend.service-detail-devops') }}" class="glass-panel skill-card skill-card-link animate-on-scroll animate-delay-2">
                        <div class="skill-icon"><i class="fas fa-cloud"></i></div>
                        <h5>DevOps والسحابة</h5>
                        <p>أتمتة النشر، الحاويات، CI/CD والعمل على منصات سحابية</p>
                    </a>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <a href="{{ route('frontend.contact') }}" class="glass-panel skill-card skill-card-link animate-on-scroll animate-delay-3">
                        <div class="skill-icon"><i class="fas fa-project-diagram"></i></div>
                        <h5>إدارة المشاريع التقنية</h5>
                        <p>تخطيط ومتابعة المشاريع البرمجية وتنسيق الفرق التقنية</p>
                    </a>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <a href="{{ route('frontend.consultation') }}" class="glass-panel skill-card skill-card-link animate-on-scroll animate-delay-4">
                        <div class="skill-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                        <h5>استشارات وتدريب تقني</h5>
                        <p>تقديم الاستشارات التقنية ودورات تدريبية في البرمجة والتكنولوجيا</p>
                    </a>
                </div>
            </div>
        </div>
    </section>

    @include('frontend.partials.tech-stack-section')

    <!-- ============ HOSTING PACKAGES ============ -->
    <section class="section-padding" id="packages" style="background: var(--clr-bg-secondary);">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge">باقات الاستضافة</span>
                <h2>اختر الباقة المناسبة لموقعك</h2>
                <p>خطط استضافة مرنة وآمنة تناسب المواقع الشخصية والمتاجر الإلكترونية والشركات</p>
            </div>
            @if(isset($featuredPackages) && $featuredPackages->isNotEmpty())
            <div class="row g-4 align-items-stretch">
                @foreach($featuredPackages as $index => $product)
                <div class="col-lg-4 col-md-6 d-flex">
                    @include('frontend.partials.package-card', ['product' => $product, 'index' => $index, 'featureLimit' => 10])
                </div>
                @endforeach
            </div>
            <div class="text-center mt-5 animate-on-scroll">
                <a href="{{ route('frontend.packages') }}" class="btn-primary-custom">
                    <i class="fas fa-list"></i> عرض جميع الباقات
                </a>
            </div>
            @else
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <a href="{{ route('frontend.packages') }}" class="glass-panel course-card animate-on-scroll animate-delay-1"
                        style="text-decoration:none;color:inherit;cursor:pointer;">
                        <div class="course-img-wrapper">
                            <i class="fas fa-server fa-3x text-white"></i>
                            <span class="course-badge">مثالية للبداية</span>
                        </div>
                        <div class="course-body">
                            <h5>باقة الاستضافة المشتركة الأساسية</h5>
                            <p>استضافة موثوقة لموقع واحد مع مساحة SSD وحماية أساسية وشهادة SSL مجانية.</p>
                            <ul class="course-features">
                                <li><i class="fas fa-check"></i> 20GB مساحة SSD</li>
                                <li><i class="fas fa-check"></i> موقع واحد</li>
                                <li><i class="fas fa-check"></i> شهادة SSL مجانية</li>
                            </ul>
                        </div>
                        <div class="course-footer">
                            <span class="price">$3.99 / شهرياً</span>
                        </div>
                    </a>
                </div>
                <div class="col-lg-4 col-md-6">
                    <a href="{{ route('frontend.packages') }}" class="glass-panel course-card animate-on-scroll animate-delay-2"
                        style="text-decoration:none;color:inherit;cursor:pointer;">
                        <div class="course-img-wrapper">
                            <i class="fas fa-layer-group fa-3x text-white"></i>
                            <span class="course-badge course-badge-popular">الأكثر شيوعاً</span>
                        </div>
                        <div class="course-body">
                            <h5>باقة الأعمال للاستضافة المشتركة</h5>
                            <p>استضافة تدعم عدّة مواقع مع أداء أعلى وموارد مخصصة لمشاريع الأعمال والمتاجر.</p>
                            <ul class="course-features">
                                <li><i class="fas fa-check"></i> 50GB مساحة SSD</li>
                                <li><i class="fas fa-check"></i> حتى 5 مواقع</li>
                                <li><i class="fas fa-check"></i> بريد إلكتروني غير محدود</li>
                            </ul>
                        </div>
                        <div class="course-footer">
                            <span class="price">$7.99 / شهرياً</span>
                        </div>
                    </a>
                </div>
                <div class="col-lg-4 col-md-6">
                    <a href="{{ route('frontend.packages') }}" class="glass-panel course-card animate-on-scroll animate-delay-3"
                        style="text-decoration:none;color:inherit;cursor:pointer;">
                        <div class="course-img-wrapper">
                            <i class="fas fa-cloud fa-3x text-white"></i>
                            <span class="course-badge">أداء عالي</span>
                        </div>
                        <div class="course-body">
                            <h5>باقة الاستضافة السحابية الاحترافية</h5>
                            <p>موارد مضمونة مع خوادم سحابية سريعة، مناسبة للمشاريع المتوسطة والـ traffic العالي.</p>
                            <ul class="course-features">
                                <li><i class="fas fa-check"></i> 4 vCPU / 8GB RAM</li>
                                <li><i class="fas fa-check"></i> 160GB مساحة SSD</li>
                                <li><i class="fas fa-check"></i> مواقع غير محدودة</li>
                            </ul>
                        </div>
                        <div class="course-footer">
                            <span class="price">$19.99 / شهرياً</span>
                        </div>
                    </a>
                </div>
            </div>
            <div class="text-center mt-5 animate-on-scroll">
                <a href="{{ route('frontend.packages') }}" class="btn-primary-custom">
                    <i class="fas fa-list"></i> عرض جميع الباقات
                </a>
            </div>
            @endif
        </div>
    </section>

    <!-- ============ TESTIMONIALS ============ -->
    <section class="section-padding" id="testimonials">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge">آراء عملائنا</span>
                <h2>ماذا يقول عملاؤنا</h2>
                <p>آراء وتجارب بعض العملاء الذين اختاروا استضافة كلاودسوفت لمواقعهم ومشاريعهم</p>
            </div>
            @include('frontend.partials.testimonials-list')
            <div class="text-center mt-5 animate-on-scroll">
                <a href="{{ route('frontend.testimonials') }}" class="btn-primary-custom">
                    <i class="fas fa-comments"></i> عرض كل آراء العملاء
                </a>
            </div>
        </div>
    </section>

    <!-- ============ HOSTING VIDEOS SECTION ============ -->
    <section class="section-padding" id="videos">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge">فيديوهات تعليمية</span>
                <h2>فيديوهات عن استضافة المواقع</h2>
                <p>شروحات مرئية حول أساسيات الاستضافة، إدارة الخوادم، وتأمين مواقعك على استضافة كلاودسوفت</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6 d-flex">
                    <a href="https://youtube.com" target="_blank" rel="noopener noreferrer"
                        class="glass-panel video-card video-card-link animate-on-scroll animate-delay-1 w-100">
                        <div class="video-wrapper">
                            <img src="{{ asset('frontend/assets/images/course-webdev.svg') }}" alt="شرح أساسيات استضافة المواقع" width="400" height="200" loading="lazy">
                            <span class="video-badge-yt"><i class="fab fa-youtube"></i> يوتيوب</span>
                            <div class="play-btn" aria-hidden="true"><i class="fas fa-play"></i></div>
                        </div>
                        <div class="video-body">
                            <h6>ما هي استضافة المواقع؟ أنواعها وكيف تختار الأنسب</h6>
                            <div class="video-meta">
                                <span class="video-views"><i class="fas fa-eye"></i> 15,000 مشاهدة</span>
                                <span class="video-cta">شاهد الآن <i class="fas fa-arrow-left"></i></span>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-4 col-md-6 d-flex">
                    <a href="https://youtube.com" target="_blank" rel="noopener noreferrer"
                        class="glass-panel video-card video-card-link animate-on-scroll animate-delay-2 w-100">
                        <div class="video-wrapper">
                            <img src="{{ asset('frontend/assets/images/course-python.svg') }}" alt="فيديو عن إعداد الاستضافة" width="400" height="200" loading="lazy">
                            <span class="video-badge-yt"><i class="fab fa-youtube"></i> يوتيوب</span>
                            <div class="play-btn" aria-hidden="true"><i class="fas fa-play"></i></div>
                        </div>
                        <div class="video-body">
                            <h6>جولة في لوحة تحكم استضافة كلاودسوفت وإعداد موقعك الأول</h6>
                            <div class="video-meta">
                                <span class="video-views"><i class="fas fa-eye"></i> 12,000 مشاهدة</span>
                                <span class="video-cta">شاهد الآن <i class="fas fa-arrow-left"></i></span>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-4 col-md-6 d-flex">
                    <a href="https://youtube.com" target="_blank" rel="noopener noreferrer"
                        class="glass-panel video-card video-card-link animate-on-scroll animate-delay-3 w-100">
                        <div class="video-wrapper">
                            <img src="{{ asset('frontend/assets/images/course-mobile.svg') }}" alt="فيديو عن أمان الاستضافة" width="400" height="200" loading="lazy">
                            <span class="video-badge-yt"><i class="fab fa-youtube"></i> يوتيوب</span>
                            <div class="play-btn" aria-hidden="true"><i class="fas fa-play"></i></div>
                        </div>
                        <div class="video-body">
                            <h6>حماية موقعك: نسخ احتياطي، شهادات SSL، وجدران الحماية</h6>
                            <div class="video-meta">
                                <span class="video-views"><i class="fas fa-eye"></i> 8,500 مشاهدة</span>
                                <span class="video-cta">شاهد الآن <i class="fas fa-arrow-left"></i></span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
            <div class="text-center mt-5 animate-on-scroll">
                <a href="/videos" class="btn-primary-custom">
                    <i class="fas fa-play-circle"></i> عرض كل فيديوهات الاستضافة
                </a>
            </div>
        </div>
    </section>

    <!-- ============ BLOG SECTION ============ -->
    <section class="section-padding" id="blog">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge">المدونة</span>
                <h2>آخر التدوينات</h2>
                <p>مقالات تقنية وتعليمية في عالم الاستضافة والسيرفرات والتكنولوجيا</p>
            </div>
            <div class="row g-4 blog-cards-grid">
                @foreach ($latestBlogPosts ?? [] as $index => $post)
                <div class="col-lg-3 col-md-6">
                    @include('frontend.partials.blog-card', ['post' => $post, 'index' => $index, 'excerptLimit' => 100])
                </div>
                @endforeach
            </div>
            @if(count($latestBlogPosts ?? []) > 0)
            <div class="text-center mt-5 animate-on-scroll">
                <a href="{{ route('frontend.blog') }}" class="btn-primary-custom">
                    <i class="fas fa-book-open"></i> عرض كل التدوينات
                </a>
            </div>
            @else
            <div class="text-center py-4">
                <p class="text-muted">لا توجد تدوينات حالياً. يمكنك إضافة مقالات من <a href="{{ url('/admin/blog/posts') }}">لوحة التحكم</a>.</p>
            </div>
            @endif
        </div>
    </section>

    <!-- ============ CLIENTS PREVIEW ============ -->
    <section class="section-padding" id="clients-preview" style="background: var(--clr-bg-secondary);">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge">ثقة غالية</span>
                <h2>شركاؤنا والعملاء</h2>
                <p>شكراً لكل من وثق بي — تعرف على بعض الشركات والعملاء الذين تعاملت معهم</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="glass-panel client-card animate-on-scroll animate-delay-1" tabindex="0" role="article">
                        <div class="client-card-logo">
                            <img src="{{ asset('frontend/assets/images/logo.png') }}" alt="شعار عميل استضافة 1" width="80" height="80" loading="lazy">
                        </div>
                        <span class="client-card-type">شركة</span>
                        <h3 class="client-card-name">اسم الشركة الأولى</h3>
                        <p class="client-card-desc">شركة رائدة في مجالها، تعاملت معها بكل احترافية وشفافية. أشكرهم على الثقة والتعاون المثمر.</p>
                        <blockquote class="client-card-quote">"شريك موثوق يلتزم بالمواعيد والجودة."</blockquote>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="glass-panel client-card animate-on-scroll animate-delay-2" tabindex="0" role="article">
                        <div class="client-card-logo">
                            <img src="{{ asset('frontend/assets/images/logo.png') }}" alt="شعار عميل استضافة 2" width="80" height="80" loading="lazy">
                        </div>
                        <span class="client-card-type">عميل</span>
                        <h3 class="client-card-name">عميل / مشروع ثانٍ</h3>
                        <p class="client-card-desc">عميل كريم كان واضحاً في المتطلبات ومتعاوناً طوال التنفيذ. أقدّر صبره وثقته.</p>
                        <blockquote class="client-card-quote">"تجربة سلسة ونتيجة تفوق التوقعات."</blockquote>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="glass-panel client-card animate-on-scroll animate-delay-3" tabindex="0" role="article">
                        <div class="client-card-logo">
                            <img src="{{ asset('frontend/assets/images/logo.png') }}" alt="شعار عميل استضافة 3" width="80" height="80" loading="lazy">
                        </div>
                        <span class="client-card-type">شركة</span>
                        <h3 class="client-card-name">شركة تقنية</h3>
                        <p class="client-card-desc">تعاون مميز في مشروع تطوير ويب وتدريب الفريق. فريقهم المحترم جعل العمل متعة.</p>
                        <blockquote class="client-card-quote">"احترافية عالية وتواصل ممتاز."</blockquote>
                    </div>
                </div>
            </div>
            <div class="text-center mt-5 animate-on-scroll">
                <a href="{{ route('frontend.clients') }}" class="btn-primary-custom">
                    <i class="fas fa-handshake"></i> تعرف على كل الشركات والعملاء
                </a>
            </div>
        </div>
    </section>
@endsection

@section('styles')
    <style>
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0; }
        }
    </style>
@endsection
