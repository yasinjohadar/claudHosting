@extends('admin.layouts.master')
@section('page-title') {{ $sectionMeta['label'] }} — Coolify @stop
@push('styles')
@include('admin.coolify.settings.partials.settings-page-styles')
@include('admin.coolify.settings.partials.settings-hub-styles')
@endpush
@section('content')
<div class="main-content app-content coolify-section-layout">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">
                    <i class="{{ $sectionMeta['icon'] ?? 'fe fe-settings' }} me-1"></i>
                    {{ $sectionMeta['label'] }}
                </h4>
                <nav><ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.coolify.overview') }}">Coolify</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.coolify.settings.index') }}">الإعدادات</a></li>
                    <li class="breadcrumb-item active">{{ $sectionMeta['label'] }}</li>
                </ol></nav>
                @if(!empty($sectionMeta['description']))
                <p class="text-muted small mb-0 mt-2">{{ $sectionMeta['description'] }}</p>
                @endif
            </div>
            <a href="{{ route('admin.coolify.settings.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fe fe-grid me-1"></i> مركز الإعدادات
            </a>
        </div>

        @include('admin.coolify.partials.alerts')

        @if(($sectionMeta['show_api_test'] ?? false) || ($sectionMeta['show_ssh_test'] ?? false))
            @include('admin.coolify.settings.partials.readiness-badges')
            @include('admin.coolify.settings.partials.sticky-actions')
        @endif

        @if(!empty($synced))
        <div class="alert alert-success py-2 small">تم ضبط تلقائياً من Coolify: {{ implode('، ', $synced) }}</div>
        @endif

        <div class="row">
            <div class="col-12">
                <div class="card custom-card mb-4">
                    <div class="card-body">
                        <form action="{{ route('admin.coolify.settings.section.update', $section) }}" method="POST" id="coolifySettingsForm">
                            @csrf
                            @method('PUT')

                            @include('admin.coolify.settings.partials.'.$sectionMeta['partial'])

                            <div class="coolify-settings-footer mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fe fe-save"></i> حفظ {{ $sectionMeta['label'] }}
                                </button>
                                <a href="{{ route('admin.coolify.settings.index') }}" class="btn btn-light">رجوع للمركز</a>
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
