@extends('frontend.layouts.master')

@section('page-title')
استضافة كلاودسوفت | CloudSoft Hosting
@endsection

@section('meta-description')
استضافة كلاودسوفت تمنحك بنية سحابية مستقرة وسريعة وآمنة لموقعك أو متجرك. باقات مرنة من المواقع الشخصية حتى الشركات، لوحة تحكم سهلة ودعم فني مستمر. ابدأ خلال دقائق.
@endsection

@section('content')
    <!-- ============ HERO SECTION ============ -->
    <section class="hero-section" id="hero">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-7 order-2 order-lg-1">
                    <div class="hero-content animate-on-scroll">
                        <h1>
                            مرحباً بك في
                            <span id="typingText"
                                data-texts="استضافة كلاودسوفت|خوادم سحابية موثوقة|باقات استضافة تناسب مشروعك">استضافة
                                كلاودسوفت</span>
                            <span class="blinking-cursor"
                                style="animation: blink 0.8s infinite; color: var(--clr-primary);">|</span>
                        </h1>
                        <p class="subtitle">
                            استضافة كلاودسوفت تمنحك بنية سحابية مستقرة، سريعة وآمنة لموقعك أو متجرك الإلكتروني، مع خطط
                            مرنة تبدأ من المواقع الشخصية وحتى مشاريع الشركات. اختر باقتك وابدأ خلال دقائق مع لوحة تحكم
                            سهلة ودعم فني مستمر.
                        </p>
                        <div class="hero-btns">
                            <a href="{{ route('frontend.packages') }}" class="btn-primary-custom">
                                <i class="fas fa-server"></i> تصفّح الباقات
                            </a>
                            <a href="/contact" class="btn-outline-custom">
                                <i class="fas fa-paper-plane"></i> تواصل معنا
                            </a>
                        </div>

                        <div class="hero-stats">
                            <div class="hero-stat-item">
                                <span class="stat-num counter-num" data-count="200">0+</span>
                                <span class="stat-label">موقع مستضاف</span>
                            </div>
                            <div class="hero-stat-item">
                                <span class="stat-num counter-num" data-count="500">0+</span>
                                <span class="stat-label">عميل نشط</span>
                            </div>
                            <div class="hero-stat-item">
                                <span class="stat-num counter-num" data-count="5">0+</span>
                                <span class="stat-label">سنوات خبرة في الاستضافة</span>
                            </div>
                            <div class="hero-stat-item">
                                <span class="stat-num counter-num" data-count="99">0+</span>
                                <span class="stat-label">نسبة توفر %</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5 order-1 order-lg-2">
                    <div class="hero-image-wrapper animate-on-scroll">
                        <div class="hero-ring"></div>
                        <img src="{{ asset('frontend/assets/images/hero-servers.svg') }}" alt="خوادم استضافة سحابية موثوقة - استضافة كلاودسوفت" class="hero-img" width="400" height="400" loading="eager">
                    </div>
                </div>
            </div>
        </div>
    </section>

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
                    <a href="/service-detail" class="glass-panel skill-card animate-on-scroll animate-delay-1" style="text-decoration:none;color:inherit;display:block;height:100%;">
                        <div class="skill-icon"><i class="fas fa-globe"></i></div>
                        <h5>تطوير تطبيقات الويب</h5>
                        <p>تصميم وتطوير مواقع وتطبيقات ويب حديثة ومتجاوبة واحترافية</p>
                    </a>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <a href="/service-detail-mobile" class="glass-panel skill-card animate-on-scroll animate-delay-2" style="text-decoration:none;color:inherit;display:block;height:100%;">
                        <div class="skill-icon"><i class="fas fa-mobile-alt"></i></div>
                        <h5>تطبيقات الجوال</h5>
                        <p>تطوير تطبيقات الهواتف الذكية متعددة المنصات للأندرويد والـ iOS</p>
                    </a>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <a href="/service-detail-security" class="glass-panel skill-card animate-on-scroll animate-delay-3" style="text-decoration:none;color:inherit;display:block;height:100%;">
                        <div class="skill-icon"><i class="fas fa-shield-alt"></i></div>
                        <h5>أمن المعلومات</h5>
                        <p>حماية الأنظمة والبيانات وتقييم الثغرات وتطبيق أفضل الممارسات الأمنية</p>
                    </a>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <a href="/service-detail-servers" class="glass-panel skill-card animate-on-scroll animate-delay-4" style="text-decoration:none;color:inherit;display:block;height:100%;">
                        <div class="skill-icon"><i class="fas fa-server"></i></div>
                        <h5>إدارة السيرفرات</h5>
                        <p>إعداد وإدارة الخوادم، الاستضافة، والنشر مع Linux والخدمات السحابية</p>
                    </a>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <a href="/service-detail" class="glass-panel skill-card animate-on-scroll animate-delay-1" style="text-decoration:none;color:inherit;display:block;height:100%;">
                        <div class="skill-icon"><i class="fas fa-database"></i></div>
                        <h5>قواعد البيانات</h5>
                        <p>تصميم وإدارة قواعد البيانات SQL و NoSQL وتحسين الأداء</p>
                    </a>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <a href="/service-detail-devops" class="glass-panel skill-card animate-on-scroll animate-delay-2" style="text-decoration:none;color:inherit;display:block;height:100%;">
                        <div class="skill-icon"><i class="fas fa-cloud"></i></div>
                        <h5>DevOps والسحابة</h5>
                        <p>أتمتة النشر، الحاويات، CI/CD والعمل على منصات سحابية</p>
                    </a>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <a href="/service-detail" class="glass-panel skill-card animate-on-scroll animate-delay-3" style="text-decoration:none;color:inherit;display:block;height:100%;">
                        <div class="skill-icon"><i class="fas fa-project-diagram"></i></div>
                        <h5>إدارة المشاريع التقنية</h5>
                        <p>تخطيط ومتابعة المشاريع البرمجية وتنسيق الفرق التقنية</p>
                    </a>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <a href="/service-detail" class="glass-panel skill-card animate-on-scroll animate-delay-4" style="text-decoration:none;color:inherit;display:block;height:100%;">
                        <div class="skill-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                        <h5>استشارات وتدريب تقني</h5>
                        <p>تقديم الاستشارات التقنية ودورات تدريبية في البرمجة والتكنولوجيا</p>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ============ HOSTING PACKAGES ============ -->
    <section class="section-padding" id="packages" style="background: var(--clr-bg-secondary);">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge">باقات الاستضافة</span>
                <h2>اختر الباقة المناسبة لموقعك</h2>
                <p>خطط استضافة مرنة وآمنة تناسب المواقع الشخصية والمتاجر الإلكترونية والشركات</p>
            </div>
            @if(isset($featuredPackages) && $featuredPackages->isNotEmpty())
            <div class="row g-4">
                @foreach($featuredPackages as $index => $product)
                <div class="col-lg-4 col-md-6">
                    <a href="{{ route('frontend.package-detail', $product->id) }}" class="glass-panel course-card animate-on-scroll animate-delay-{{ ($index % 3) + 1 }}"
                        style="text-decoration:none;color:inherit;cursor:pointer;">
                        <div class="course-img-wrapper">
                            <i class="fas fa-server fa-3x text-white"></i>
                            <span class="course-badge">{{ $product->group_name ?? 'باقة' }}</span>
                        </div>
                        <div class="course-body">
                            <h5>{{ $product->name }}</h5>
                            <p>{{ Str::limit(strip_tags($product->description ?? ''), 80) ?: 'باقة استضافة مناسبة لاحتياجاتك.' }}</p>
                            <ul class="course-features">
                                <li><i class="fas fa-check"></i> {{ $product->type_name ?? $product->type }}</li>
                                <li><i class="fas fa-check"></i> {{ $product->price }} $</li>
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
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="glass-panel testimonial-card animate-on-scroll animate-delay-1">
                        <div class="stars">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                        <p class="quote-text">"دورة تطوير الويب كانت نقطة تحول في مسيرتي المهنية. أسلوب الشرح ممتاز والتطبيقات العملية رائعة. أنصح الجميع بالتسجيل!"</p>
                        <div class="student-info">
                            <div>
                                <div class="student-name">أحمد محمد</div>
                                <div class="student-role">مطور ويب - سوريا</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="glass-panel testimonial-card animate-on-scroll animate-delay-2">
                        <div class="stars">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                        <p class="quote-text">"فريق كلاودسوفت من أفضل مزودي الاستضافة. الدعم سريع، الخوادم مستقرة، والمحتوى محدث بأحدث التقنيات. استفدت كثيراً من باقة VPS."</p>
                        <div class="student-info">
                            <div>
                                <div class="student-name">سارة العلي</div>
                                <div class="student-role">مهندسة برمجيات - الأردن</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="glass-panel testimonial-card animate-on-scroll animate-delay-3">
                        <div class="stars">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                        </div>
                        <p class="quote-text">"تعلمت إدارة الاستضافة من الدليل والفيديوهات وقمت بنقل موقعي خلال أيام فقط! الدعم الفني والمتابعة من الفريق كانت ممتازة."</p>
                        <div class="student-info">
                            <div>
                                <div class="student-name">عمر حسان</div>
                                <div class="student-role">مطور تطبيقات - العراق</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-5 animate-on-scroll">
                <a href="/testimonials" class="btn-primary-custom">
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
                <div class="col-lg-4 col-md-6">
                    <div class="glass-panel video-card animate-on-scroll animate-delay-1">
                        <div class="video-wrapper" onclick="window.open('https://youtube.com', '_blank')">
                            <img src="{{ asset('frontend/assets/images/course-webdev.svg') }}" alt="شرح أساسيات استضافة المواقع" width="400" height="200" loading="lazy">
                            <div class="play-btn"><i class="fas fa-play-circle"></i></div>
                        </div>
                        <div class="video-body">
                            <h6>ما هي استضافة المواقع؟ أنواعها وكيف تختار الأنسب</h6>
                            <span><i class="fas fa-eye"></i> 15,000 مشاهدة</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="glass-panel video-card animate-on-scroll animate-delay-2">
                        <div class="video-wrapper" onclick="window.open('https://youtube.com', '_blank')">
                            <img src="{{ asset('frontend/assets/images/course-python.svg') }}" alt="فيديو عن إعداد الاستضافة" width="400" height="200" loading="lazy">
                            <div class="play-btn"><i class="fas fa-play-circle"></i></div>
                        </div>
                        <div class="video-body">
                            <h6>جولة في لوحة تحكم استضافة كلاودسوفت وإعداد موقعك الأول</h6>
                            <span><i class="fas fa-eye"></i> 12,000 مشاهدة</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="glass-panel video-card animate-on-scroll animate-delay-3">
                        <div class="video-wrapper" onclick="window.open('https://youtube.com', '_blank')">
                            <img src="{{ asset('frontend/assets/images/course-mobile.svg') }}" alt="فيديو عن أمان الاستضافة" width="400" height="200" loading="lazy">
                            <div class="play-btn"><i class="fas fa-play-circle"></i></div>
                        </div>
                        <div class="video-body">
                            <h6>حماية موقعك: نسخ احتياطي، شهادات SSL، وجدران الحماية</h6>
                            <span><i class="fas fa-eye"></i> 8,500 مشاهدة</span>
                        </div>
                    </div>
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
    <section class="section-padding" id="blog" style="background: var(--clr-bg-secondary);">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge">المدونة</span>
                <h2>آخر التدوينات</h2>
                <p>مقالات تقنية وتعليمية في عالم الاستضافة والسيرفرات والتكنولوجيا</p>
            </div>
            <div class="row g-4">
                @foreach($latestBlogPosts ?? [] as $index => $post)
                <div class="col-lg-4 col-md-6">
                    <a href="{{ route('frontend.blog.show', $post->slug) }}" class="glass-panel blog-card animate-on-scroll animate-delay-{{ ($index % 3) + 1 }}" style="text-decoration:none;color:inherit;display:block;height:100%;">
                        <div class="blog-img-wrapper">
                            @if($post->featured_image && function_exists('blog_image_url'))
                                <img src="{{ blog_image_url($post->featured_image) }}" alt="{{ $post->featured_image_alt ?? $post->title }}" width="400" height="180" loading="lazy">
                            @else
                                <img src="{{ asset('frontend/assets/images/course-webdev.svg') }}" alt="{{ $post->title }}" width="400" height="180" loading="lazy">
                            @endif
                        </div>
                        <div class="blog-body">
                            <div class="blog-meta">
                                <span><i class="fas fa-calendar-alt"></i> {{ $post->published_at?->translatedFormat('d F Y') ?? $post->created_at->format('Y-m-d') }}</span>
                                @if($post->category)
                                <span><i class="fas fa-folder"></i> {{ $post->category->name }}</span>
                                @endif
                            </div>
                            <h5>{{ $post->title }}</h5>
                            <p>{{ Str::limit(strip_tags($post->excerpt), 100) }}</p>
                            <span class="read-more">اقرأ المزيد <i class="fas fa-arrow-left"></i></span>
                        </div>
                    </a>
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
                    <div class="glass-panel client-card animate-on-scroll">
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
                    <div class="glass-panel client-card animate-on-scroll">
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
                    <div class="glass-panel client-card animate-on-scroll">
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
