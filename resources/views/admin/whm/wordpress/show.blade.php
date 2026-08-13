@extends('admin.layouts.master')
@section('page-title') {{ $site->display_name }} @stop

@push('styles')
@include('admin.coolify.partials.overview-styles')
@include('admin.coolify.wordpress-sites.partials.site-show-styles')
@endpush

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <div>
                <nav class="small text-muted mb-1">
                    <a href="{{ route('admin.whm.accounts.show', $account) }}">{{ $account->username }}</a>
                    <span class="mx-1">/</span>
                    <a href="{{ route('admin.whm.accounts.wordpress.index', $account) }}">ووردبريس</a>
                    <span class="mx-1">/</span>
                    <span>{{ $site->display_name }}</span>
                </nav>
                <h4 class="mb-1">{{ $site->display_name }}</h4>
                <p class="text-muted small mb-0">
                    <span class="badge bg-info-transparent">{{ $site->source_label }}</span>
                    <span class="ms-2" dir="ltr">{{ $site->public_url ?: $site->path }}</span>
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('admin.whm.accounts.wordpress.open', [$account, $site]) }}" target="_blank" class="btn btn-outline-primary btn-sm">فتح الموقع</a>
                <a href="{{ route('admin.whm.accounts.wordpress.wp-admin', [$account, $site]) }}" target="_blank" class="btn btn-primary btn-sm">wp-admin</a>
                <a href="{{ route('admin.whm.accounts.wordpress.manager', [$account, $site]) }}" target="_blank" class="btn btn-outline-secondary btn-sm">Softaculous / cPanel</a>
                <a href="{{ route('admin.whm.accounts.wordpress.index', $account) }}" class="btn btn-light btn-sm">رجوع</a>
            </div>
        </div>

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @include('admin.coolify.wordpress-sites.partials.management', [
            'embeddedInSiteShow' => true,
            'hideDockerTab' => true,
            'wpManagementState' => $wpManagementState,
            'wpInfo' => $wpInfo,
            'site' => $site,
            'uuid' => $uuid,
            'wpSiteRoutes' => $wpSiteRoutes,
        ])
    </div>
</div>
@endsection
