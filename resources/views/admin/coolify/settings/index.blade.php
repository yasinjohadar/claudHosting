@extends('admin.layouts.master')
@section('page-title') إعدادات Coolify @stop
@push('styles')
@include('admin.coolify.settings.partials.settings-page-styles')
@endpush
@section('content')
@include('admin.coolify.partials.overview-styles')
@include('admin.coolify.settings.partials.settings-page-styles')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">إعدادات اتصال Coolify</h4>
                <nav><ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.coolify.overview') }}">Coolify</a></li>
                    <li class="breadcrumb-item active">الإعدادات</li>
                </ol></nav>
            </div>
        </div>
        @include('admin.coolify.partials.alerts')

        @include('admin.coolify.settings.partials.readiness-badges')
        @include('admin.coolify.settings.partials.sticky-actions')

        <div class="row">
            <div class="col-12">
                <div class="card custom-card mb-4">
                    <div class="card-header">
                        <div class="card-title mb-0">إعدادات Coolify</div>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">زر «حفظ» يحفظ <strong>كل التبويبات</strong> دفعة واحدة.</p>
                        <form action="{{ route('admin.coolify.settings.update') }}" method="POST" id="coolifySettingsForm">
                            @csrf
                            @method('PUT')

                            @include('admin.coolify.settings.partials.tabs-nav')

                            <div class="tab-content pt-2" id="coolifySettingsTabContent">
                                @include('admin.coolify.settings.partials.tab-api')
                                @include('admin.coolify.settings.partials.tab-backups')
                                @include('admin.coolify.settings.partials.tab-wordpress')
                                @include('admin.coolify.settings.partials.tab-cloudflare')
                                @include('admin.coolify.settings.partials.tab-wp-management')
                                @include('admin.coolify.settings.partials.tab-ssh')
                                @include('admin.coolify.settings.partials.tab-terminal-bridge')
                            </div>

                            <div class="coolify-settings-footer mt-4">
                                <button type="submit" class="btn btn-primary"><i class="fe fe-save"></i> حفظ الإعدادات</button>
                                <a href="{{ route('admin.coolify.overview') }}" class="btn btn-light">رجوع</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
@include('admin.coolify.settings.partials.settings-scripts')
@endpush
@endsection
