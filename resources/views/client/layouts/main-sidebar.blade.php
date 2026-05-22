<aside class="app-sidebar sticky" id="sidebar">
    <div class="main-sidebar-header">
        <a href="{{ route('client.dashboard') }}" class="header-logo">
            <img src="{{ asset('assets/images/brand-logos/desktop-logo.png') }}" alt="logo" class="desktop-logo">
            <img src="{{ asset('assets/images/brand-logos/toggle-logo.png') }}" alt="logo" class="toggle-logo">
            <img src="{{ asset('assets/images/brand-logos/desktop-white.png') }}" alt="logo" class="desktop-white">
            <img src="{{ asset('assets/images/brand-logos/toggle-white.png') }}" alt="logo" class="toggle-white">
        </a>
    </div>
    <div class="main-sidebar" id="sidebar-scroll">
        <nav class="main-menu-container nav nav-pills flex-column sub-open">
            <ul class="main-menu">
                {{-- روابط الصفحات تُضاف لاحقاً ضمن لوحة العميل --}}
            </ul>
        </nav>
    </div>
</aside>
