<!DOCTYPE html>
<html lang="ar" dir="rtl" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light"
    data-menu-styles="light" data-toggled="close">

<head>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page-title') — {{ config('app.name', 'ClaudHosting') }}</title>
    <meta name="Description" content="لوحة العميل">
    @include('client.layouts.head')
    @yield('css')
</head>

<body>
    @include('client.layouts.switcher')

    <div id="loader">
        <img src="{{ asset('assets/images/media/loader.svg') }}" alt="">
    </div>

    <div class="page">
        @include('client.partials.impersonation-banner')
        @include('client.layouts.main-header')
        @include('client.layouts.offcanvas-sidebar')
        @include('client.layouts.main-sidebar')

        @yield('content')

        @include('client.layouts.footer')
    </div>

    @include('client.layouts.footer-scripts')
    @yield('scripts')
    @stack('scripts')
</body>

</html>
