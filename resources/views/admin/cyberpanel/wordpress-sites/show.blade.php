@extends('admin.layouts.master')
@section('page-title') {{ $site->domain }} @stop

@push('styles')
    @include('admin.coolify.partials.overview-styles')
    @include('admin.cyberpanel.wordpress-sites.partials.show-styles')
@endpush

@section('content')
<div class="main-content app-content cp-wp-show-page">
    <div class="container-fluid">
        @include('admin.cyberpanel.wordpress-sites.partials.show-header')
        @include('admin.coolify.partials.alerts')

        @if(!($supportsCloud ?? true))
            <div class="alert alert-warning border-0 shadow-sm mb-3">
                <strong>CloudAPI:</strong> فعّل API Access في CyberPanel واحفظ كلمة مرور المدير في
                <a href="{{ route('admin.cyberpanel.settings.index') }}">الإعدادات</a>.
            </div>
        @endif

        @include('admin.cyberpanel.wordpress-sites.partials.show-stats')

        <h6 class="cp-wp-show-section-title">إدارة الموقع</h6>
        <div class="cp-wp-tabs-panel mb-4">
            <div class="cp-wp-tabs-panel__head">
                @include('admin.cyberpanel.wordpress-sites.partials.show-tabs-nav')
            </div>
            <div class="tab-content" id="cpWpSiteTabContent">
                @include('admin.cyberpanel.wordpress-sites.partials.tab-overview')

                <div class="tab-pane fade" id="siteTabWordpress" role="tabpanel">
                    @include('admin.cyberpanel.wordpress-sites.partials.management', ['embeddedInSiteShow' => true])
                </div>

                @include('admin.cyberpanel.wordpress-sites.partials.tab-backups')
                @include('admin.cyberpanel.wordpress-sites.partials.tab-hosting')
                @include('admin.cyberpanel.wordpress-sites.partials.tab-cyberpanel-tools')
            </div>
        </div>
    </div>
</div>
@push('scripts')
    @include('admin.cyberpanel.wordpress-sites.partials.management-scripts')
    @include('admin.cyberpanel.wordpress-sites.partials.show-scripts')
@endpush
@endsection
