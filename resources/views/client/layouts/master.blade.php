<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light"
    data-menu-styles="light" data-toggled="close">

<head>

    <!-- Meta Data -->
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page-title')</title>
    <meta name="Description" content="لوحة العميل">
    <meta name="Author" content="claudSoft">
    <meta name="keywords" content="لوحة العميل">

    @include('admin.layouts.head')
    @yield('css')
</head>

<body>

    @include('admin.layouts.switcher')

    <!-- Loader -->
    <div id="loader">
        <img src="{{ asset('assets/images/media/loader.svg') }}" alt="">
    </div>
    <!-- Loader -->

    <div class="page">

        @include('client.partials.impersonation-banner')

        @include('admin.layouts.main-header')

        @include('admin.layouts.offcanvas-sidebar')

        @include('client.layouts.main-sidebar')

        @yield('content')

        @include('admin.layouts.footer')

    </div>
    @include('admin.layouts.footer-scripts')

    @yield('scripts')
    @stack('scripts')

</body>

</html>
