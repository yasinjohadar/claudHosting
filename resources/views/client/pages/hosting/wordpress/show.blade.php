@extends('client.layouts.master')

@section('page-title')
إدارة {{ $site->display_name }}
@stop

@section('css')
@include('client.partials.portal-ui-styles')
@include('admin.coolify.partials.overview-styles')
@include('admin.coolify.wordpress-sites.partials.site-show-styles')
@endsection

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="client-portal-hero d-md-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <nav class="client-portal-breadcrumb mb-2">
                    <a href="{{ route('client.hosting.wordpress.index', $account) }}">ووردبريس</a>
                    <span class="text-muted mx-1">/</span>
                    <span>{{ $site->display_name }}</span>
                </nav>
                <h4 class="mb-1">{{ $site->display_name }}</h4>
                <p class="text-muted small mb-0">
                    <span class="badge bg-info-transparent">{{ $site->source_label }}</span>
                    @if($site->wp_version)
                        <span class="ms-2" dir="ltr">v{{ $site->wp_version }}</span>
                    @endif
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('client.hosting.wordpress.open', [$account, $site]) }}" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill">فتح الموقع</a>
                <a href="{{ route('client.hosting.wordpress.wp-admin', [$account, $site]) }}" target="_blank" class="btn btn-primary btn-sm rounded-pill">wp-admin</a>
                <a href="{{ route('client.hosting.wordpress.index', $account) }}" class="btn btn-light btn-sm rounded-pill">رجوع</a>
            </div>
        </div>

        @if(session('error'))
            <div class="alert alert-danger py-2">{{ session('error') }}</div>
        @endif
        @if(session('success'))
            <div class="alert alert-success py-2">{{ session('success') }}</div>
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
