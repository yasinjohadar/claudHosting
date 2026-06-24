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
                <div class="top-bar-actions">
                    <button class="theme-toggle top-bar-theme-toggle" id="themeToggle" type="button" title="تبديل الوضع" aria-label="تبديل الوضع الليلي/النهاري">
                        <i class="fas fa-moon" aria-hidden="true"></i>
                    </button>
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

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="فتح وإغلاق القائمة">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav navbar-nav-main mx-auto">
                    <li class="nav-item"><a class="nav-link" href="{{ url('/') }}">الرئيسية</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('frontend.about') }}">حول الشركة</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('frontend.packages') }}">الباقات</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('frontend.domain-search') }}">بحث النطاقات</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('frontend.projects') }}">المشاريع</a></li>
                    <li class="nav-item"><a class="nav-link" href="#skills">التخصصات</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('frontend.blog') }}">المدونة</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('frontend.contact') }}">تواصل معنا</a></li>
                </ul>

                <div class="navbar-actions">
                    @auth
                        @php
                            $authUser = auth()->user();
                            $authPanelUrl = $authUser->isAdminPanelUser()
                                ? route('admin.dashboard')
                                : route('client.dashboard');
                            $authAvatar = $authUser->photo
                                ? asset('storage/' . $authUser->photo)
                                : asset('assets/images/faces/default-avatar.jpg');
                        @endphp
                        <a href="{{ $authPanelUrl }}" class="navbar-user" title="لوحة التحكم">
                            <img src="{{ $authAvatar }}" alt="{{ $authUser->name }}" class="navbar-user__avatar" width="36" height="36">
                            <span class="navbar-user__name">{{ $authUser->name }}</span>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-primary navbar-login-btn">
                            <i class="fas fa-sign-in-alt ms-1" aria-hidden="true"></i>
                            تسجيل الدخول
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>
