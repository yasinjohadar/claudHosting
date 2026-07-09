<!-- Favicon -->
<link rel="icon" href="{{ asset('assets/images/brand-logos/favicon.ico') }}" type="image/x-icon">

<!-- Choices JS -->
<script src="{{ asset('assets/libs/choices.js/public/assets/scripts/choices.min.js') }}"></script>

<script>
    window.__BOOTSTRAP_RTL_CSS__ = @json(asset('assets/libs/bootstrap/css/bootstrap.rtl.min.css'));
    window.__BOOTSTRAP_LTR_CSS__ = @json(asset('assets/libs/bootstrap/css/bootstrap.min.css'));
</script>

<!-- Main Theme Js -->
<script src="{{ asset('assets/js/main.js') }}"></script>

<!-- Bootstrap Css — RTL فقط -->
<link id="style" href="{{ asset('assets/libs/bootstrap/css/bootstrap.rtl.min.css') }}" rel="stylesheet"
    data-rtl-href="{{ asset('assets/libs/bootstrap/css/bootstrap.rtl.min.css') }}">

<!-- Style Css -->
<link href="{{ asset('assets/css/styles.min.css') }}" rel="stylesheet">

<!-- Icons Css -->
<link href="{{ asset('assets/css/icons.css') }}" rel="stylesheet">

<!-- Node Waves Css -->
<link href="{{ asset('assets/libs/node-waves/waves.min.css') }}" rel="stylesheet">

<!-- Simplebar Css -->
<link href="{{ asset('assets/libs/simplebar/simplebar.min.css') }}" rel="stylesheet">

<!-- Color Picker Css -->
<link rel="stylesheet" href="{{ asset('assets/libs/flatpickr/flatpickr.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/libs/@simonwep/pickr/themes/nano.min.css') }}">

<!-- Choices Css -->
<link rel="stylesheet" href="{{ asset('assets/libs/choices.js/public/assets/styles/choices.min.css') }}">

<!-- Jsvector Maps -->
<link rel="stylesheet" href="{{ asset('assets/libs/jsvectormap/css/jsvectormap.min.css') }}">

<!-- Custom Css -->
<link rel="stylesheet" href="{{ asset('assets/css/app-brand.css') }}?v={{ @filemtime(public_path('assets/css/app-brand.css')) ?: '1' }}">
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}?v={{ @filemtime(public_path('assets/css/custom.css')) ?: '1' }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-sidebar.css') }}?v={{ @filemtime(public_path('assets/css/admin-sidebar.css')) ?: '1' }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-switcher.css') }}?v={{ @filemtime(public_path('assets/css/admin-switcher.css')) ?: '1' }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-dashboard.css') }}?v={{ @filemtime(public_path('assets/css/admin-dashboard.css')) ?: '1' }}">

@stack('styles')
