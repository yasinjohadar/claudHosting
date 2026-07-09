@php
    $brandUrl = $brandUrl ?? (request()->is('client*') || request()->routeIs('client.*')
        ? route('client.dashboard')
        : route('admin.dashboard'));
    $brandContext = $brandContext ?? 'sidebar';
@endphp
<a href="{{ $brandUrl }}" class="header-logo app-brand app-brand--{{ $brandContext }}">
    <span class="app-brand__mark">
        <img src="{{ asset('assets/images/brand-logos/logo.png') }}" alt="كلاودسوفت" width="40" height="40" decoding="async">
    </span>
    <span class="app-brand__title">كلاودسوفت</span>
</a>
