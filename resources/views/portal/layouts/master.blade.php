<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page-title', 'خدماتي') — {{ config('app.name', 'ClaudHosting') }}</title>
    <link href="{{ asset('assets/libs/bootstrap/css/bootstrap.rtl.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/icons.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
    <style>
        .portal-sidebar { min-height: calc(100vh - 56px); background: #f8f9fc; border-left: 1px solid rgba(0,0,0,.06); }
        .portal-sidebar .nav-link { color: #4a5568; border-radius: .5rem; margin-bottom: .25rem; }
        .portal-sidebar .nav-link.active, .portal-sidebar .nav-link:hover { background: rgba(var(--primary-rgb, 132, 90, 223), .1); color: var(--primary-color, #845adf); }
        .portal-brand { font-weight: 700; }
    </style>
    @yield('css')
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand portal-brand" href="{{ route('portal.dashboard') }}">خدماتي</a>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small d-none d-md-inline">{{ auth()->user()->name }}</span>
            @if(auth()->user()->isAdminPanelUser())
                <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-primary">لوحة الإدارة</a>
            @endif
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button type="submit" class="btn btn-sm btn-light">خروج</button>
            </form>
        </div>
    </div>
</nav>
<div class="container-fluid">
    <div class="row">
        <aside class="col-md-3 col-lg-2 portal-sidebar py-4">
            <nav class="nav flex-column px-2">
                <a class="nav-link {{ request()->routeIs('portal.dashboard') ? 'active' : '' }}" href="{{ route('portal.dashboard') }}">
                    <i class="fe fe-home me-2"></i> الرئيسية
                </a>
                <a class="nav-link {{ request()->routeIs('portal.domains.*') ? 'active' : '' }}" href="{{ route('portal.domains.index') }}">
                    <i class="fe fe-globe me-2"></i> النطاقات
                </a>
                <a class="nav-link {{ request()->routeIs('portal.projects.*') ? 'active' : '' }}" href="{{ route('portal.projects.index') }}">
                    <i class="fe fe-layers me-2"></i> مشاريع Coolify
                </a>
                <a class="nav-link {{ request()->routeIs('portal.hosting.*') ? 'active' : '' }}" href="{{ route('portal.hosting.index') }}">
                    <i class="fe fe-server me-2"></i> الاستضافة cPanel
                </a>
            </nav>
        </aside>
        <main class="col-md-9 col-lg-10 py-4">
            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
            @yield('content')
        </main>
    </div>
</div>
<script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
@yield('scripts')
</body>
</html>
