    <!-- ============ TOP BAR ============ -->
    <div class="top-bar" id="topBar">
        <div class="container">
            <div class="top-bar-inner">
                <div class="top-bar-contact">
                    @php
                        $contactEmail = $settings['contact_email'] ?? 'info@cloudsofthosting.com';
                        $contactPhone = $settings['contact_phone'] ?? '+963 XXX XXX XXX';
                        $contactWhatsapp = $settings['contact_whatsapp'] ?? $contactPhone;
                        $phoneHref = 'tel:' . preg_replace('/\s+/', '', $contactPhone);
                        $whatsappNumber = preg_replace('/[^0-9]/', '', $contactWhatsapp);
                        $whatsappHref = 'https://wa.me/' . $whatsappNumber;
                    @endphp
                    <a href="mailto:{{ $contactEmail }}" class="top-bar-item"><i class="fas fa-envelope"></i><span class="top-bar-text">{{ $contactEmail }}</span></a>
                    <a href="{{ $phoneHref }}" class="top-bar-item"><i class="fas fa-phone-alt"></i><span class="top-bar-text">{{ $contactPhone }}</span></a>
                    <a href="{{ $whatsappHref }}" target="_blank" rel="noopener noreferrer" class="top-bar-item" title="تواصل عبر واتساب" aria-label="واتساب"><i class="fab fa-whatsapp"></i><span class="top-bar-text">واتساب</span></a>
                </div>
                <div class="top-bar-links">
                    <a href="{{ route('frontend.packages') }}" class="top-bar-item"><i class="fas fa-server"></i><span class="top-bar-text">الباقات</span></a>
                    <a href="{{ route('frontend.domain-search') }}" class="top-bar-item"><i class="fas fa-globe"></i><span class="top-bar-text">بحث النطاقات</span></a>
                    <a href="{{ route('frontend.videos') }}" class="top-bar-item"><i class="fas fa-play-circle"></i><span class="top-bar-text">الفيديوهات</span></a>
                    <a href="{{ route('frontend.consultation') }}" class="top-bar-item"><i class="fas fa-calendar-check"></i><span class="top-bar-text">حجز موعد</span></a>
                    <a href="{{ route('frontend.contact') }}" class="top-bar-item"><i class="fas fa-paper-plane"></i><span class="top-bar-text">تواصل معنا</span></a>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ NAVBAR ============ -->
    <nav class="navbar navbar-expand-lg main-navbar" id="mainNavbar">
        <div class="container">
            <a class="navbar-brand" href="{{ url('/') }}">
                <img src="{{ asset('frontend/assets/images/logo.png') }}" alt="شعار {{ $settings['site_name'] ?? 'ClaudSoft' }}" class="navbar-logo-img" width="45" height="45">
                <span>{{ $settings['site_name'] ?? 'ClaudSoft' }}</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="{{ url('/') }}">الرئيسية</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('frontend.about') }}">حول الشركة</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('frontend.packages') }}">الباقات</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('frontend.domain-search') }}">بحث النطاقات</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('frontend.projects') }}">المشاريع</a></li>
                    <li class="nav-item"><a class="nav-link" href="#skills">التخصصات</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('frontend.blog') }}">المدونة</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('frontend.contact') }}">تواصل معنا</a></li>
                </ul>

                <div class="d-flex align-items-center gap-3">
                    <!-- Social Icons -->
                    <div class="nav-social">
                        @if(!empty($settings['social_facebook'] ?? null))<a href="{{ $settings['social_facebook'] }}" target="_blank" rel="noopener noreferrer" title="فيسبوك" aria-label="فيسبوك"><i class="fab fa-facebook-f"></i></a>@endif
                        @if(!empty($settings['social_youtube'] ?? null))<a href="{{ $settings['social_youtube'] }}" target="_blank" rel="noopener noreferrer" title="يوتيوب" aria-label="يوتيوب"><i class="fab fa-youtube"></i></a>@endif
                        @if(!empty($settings['social_instagram'] ?? null))<a href="{{ $settings['social_instagram'] }}" target="_blank" rel="noopener noreferrer" title="انستغرام" aria-label="انستغرام"><i class="fab fa-instagram"></i></a>@endif
                        @if(!empty($settings['social_linkedin'] ?? null))<a href="{{ $settings['social_linkedin'] }}" target="_blank" rel="noopener noreferrer" title="لينكد إن" aria-label="لينكد إن"><i class="fab fa-linkedin-in"></i></a>@endif
                        @if(!empty($settings['social_github'] ?? null))<a href="{{ $settings['social_github'] }}" target="_blank" rel="noopener noreferrer" title="جيت هاب" aria-label="جيت هاب"><i class="fab fa-github"></i></a>@endif
                        @if(!empty($settings['social_telegram'] ?? null))<a href="{{ $settings['social_telegram'] }}" target="_blank" rel="noopener noreferrer" title="تليجرام" aria-label="تليجرام"><i class="fab fa-telegram-plane"></i></a>@endif
                    </div>
                    <!-- Theme Toggle -->
                    <button class="theme-toggle" id="themeToggle" title="تبديل الوضع" aria-label="تبديل الوضع الليلي/النهاري">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>
