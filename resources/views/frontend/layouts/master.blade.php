<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="استضافة كلاودسوفت - استضافة مواقع سحابية موثوقة. باقات مرنة، دعم فني مستمر، وبنية تحتية قوية لموقعك أو متجرك.">
    <title>@yield('page-title', 'استضافة كلاودسوفت | ClaudSoft')</title>

    <!-- Canonical URL -->
    <link rel="canonical" href="https://cloudsofthosting.com/">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('frontend/assets/images/favicon.svg') }}">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://cloudsofthosting.com/">
    <meta property="og:title" content="استضافة كلاودسوفت | ClaudSoft">
    <meta property="og:description" content="استضافة كلاودسوفت - استضافة مواقع سحابية موثوقة. باقات مرنة، دعم فني مستمر، وبنية تحتية قوية.">
    <meta property="og:image" content="https://cloudsofthosting.com/assets/images/logo.png">
    <meta property="og:locale" content="ar_AR">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="استضافة كلاودسوفت | ClaudSoft">
    <meta name="twitter:description" content="استضافة كلاودسوفت - استضافة مواقع سحابية موثوقة. باقات مرنة ودعم فني مستمر.">
    <meta name="twitter:image" content="https://cloudsofthosting.com/assets/images/logo.png">

    <!-- Structured Data (JSON-LD) for SEO -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "استضافة كلاودسوفت",
        "url": "https://cloudsofthosting.com/",
        "description": "استضافة مواقع سحابية موثوقة - باقات مرنة ودعم فني مستمر",
        "inLanguage": "ar",
        "publisher": {
            "@type": "Organization",
            "name": "ClaudSoft",
            "url": "https://cloudsofthosting.com/"
        }
    }
    </script>

    <!-- Bootstrap 5 RTL -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('frontend/assets/css/style.css') }}">
    @stack('styles')
</head>

<body>

    <!-- لورد احترافي عند تحميل الصفحة -->
    <div id="pageLoader" aria-hidden="true">
        <div class="pageLoader-inner">
            <div class="pageLoader-logo">
                <img src="{{ asset('frontend/assets/images/logo.png') }}" alt="شعار ClaudSoft" width="72" height="72">
            </div>
            <div class="pageLoader-spinner"></div>
            <p class="pageLoader-text">جاري التحميل...</p>
        </div>
    </div>

    <!-- Background Orbs -->
    <div class="bg-orbs">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>

    @include('frontend.layouts.header')

    @yield('content')

    @include('frontend.layouts.footer')

    <!-- Lightbox -->
    <div class="lightbox-overlay" id="lightbox">
        <button class="lightbox-close" id="lightboxClose"><i class="fas fa-times"></i></button>
        <img src="" alt="" id="lightboxImg">
    </div>

    <!-- Back to Top -->
    <button class="back-to-top" id="backToTop" aria-label="العودة للأعلى"><i class="fas fa-chevron-up"></i></button>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Main JS -->
    <script src="{{ asset('frontend/assets/js/main.js') }}"></script>

    @yield('scripts')
    @yield('styles')
</body>

</html>
