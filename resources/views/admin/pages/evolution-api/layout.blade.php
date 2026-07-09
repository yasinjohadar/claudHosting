@extends('admin.layouts.master')

@section('page-title', $evoPageTitle ?? 'Evolution API')

@section('css')
<style>
    .evo-flash-alert { border-right: 4px solid currentColor; animation: evoFlashIn .35s ease; }
    @keyframes evoFlashIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
    .evo-nav-pills .nav-link.active { box-shadow: 0 .25rem .5rem rgba(25,135,84,.25); }
    .evo-stat-card { transition: transform .2s ease, box-shadow .2s ease; }
    .evo-stat-card:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.08)!important; }
    .evo-inline-result { border-right: 4px solid; animation: evoFlashIn .3s ease; }
    #evoMemberMessageModal { z-index: 1055 !important; }
    #evoMemberMessageModal .modal-content { background-color: var(--custom-white, #fff); color: var(--default-text-color, #212529); }
    #evoMemberMessageModal .form-control { background-color: var(--form-control-bg, #fff); color: var(--default-text-color, #212529); border-color: var(--input-border, #dee2e6); }
</style>
@yield('evo-css')
@endsection

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        @include('admin.pages.evolution-api.partials.flash')
        @include('admin.pages.evolution-api.partials.header', [
            'title' => $evoTitle ?? 'Evolution API',
            'subtitle' => $evoSubtitle ?? 'ربط المنصة مع Evolution API للواتساب',
            'breadcrumb' => $evoBreadcrumb ?? null,
            'headerActions' => $evoHeaderActions ?? null,
        ])
        @include('admin.pages.evolution-api.partials.nav')

        <div class="dashboard-fade-in">
            @yield('evo-content')
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    const area = document.getElementById('evo-flash-area');
    if (area && area.querySelector('.evo-flash-alert')) {
        area.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    window.evoShowInlineAlert = function (el, message, type) {
        if (!el) return;
        el.className = 'alert evo-inline-result alert-' + (type || 'info') + ' shadow-sm border-0';
        el.innerHTML = '<div class="d-flex align-items-center gap-2"><i class="ri-' + (type === 'success' ? 'checkbox-circle' : type === 'danger' ? 'error-warning' : 'information') + '-line fs-18"></i><span>' + message + '</span></div>';
        el.classList.remove('d-none');
        el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    };
})();
</script>
@yield('evo-scripts')
@stack('evo-scripts')
@endsection
