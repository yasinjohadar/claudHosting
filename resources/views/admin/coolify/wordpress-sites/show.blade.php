@extends('admin.layouts.master')
@section('page-title') {{ $site->display_name }} @stop
@push('styles')
@include('admin.coolify.partials.overview-styles')
@include('admin.coolify.wordpress-sites.partials.site-show-styles')
@endpush
@section('content')
{{-- احتياط: أنماط اللوحة (تُحمّل أيضاً عبر @push في head) --}}
@include('admin.coolify.partials.overview-styles')
@include('admin.coolify.wordpress-sites.partials.site-show-styles')
<div class="main-content app-content">
    <div class="container-fluid">
        @include('admin.coolify.wordpress-sites.partials.show-header')
        @include('admin.coolify.partials.alerts')
        @include('admin.coolify.wordpress-sites.partials.show-stats')

        <h6 class="text-muted text-uppercase small fw-bold mb-3">إدارة الموقع</h6>
        <div class="site-show-tabs-panel mb-4">
            <div class="site-show-tabs-head">
                @include('admin.coolify.wordpress-sites.partials.show-tabs-nav')
            </div>
            <div class="tab-content" id="siteShowTabContent">
                @include('admin.coolify.wordpress-sites.partials.tab-overview')

                <div class="tab-pane fade" id="siteTabWordpress" role="tabpanel">
                    @include('admin.coolify.wordpress-sites.partials.management', ['embeddedInSiteShow' => true])
                </div>

                @include('admin.coolify.wordpress-sites.partials.tab-files')
                @include('admin.coolify.wordpress-sites.partials.tab-terminal')

                @include('admin.coolify.wordpress-sites.partials.tab-cloudflare')
                @include('admin.coolify.wordpress-sites.partials.tab-infrastructure')
                @include('admin.coolify.wordpress-sites.partials.tab-technical')
            </div>
        </div>
    </div>
</div>
@push('scripts')
@include('admin.coolify.wordpress-sites.partials.show-scripts')
@endpush
@endsection
