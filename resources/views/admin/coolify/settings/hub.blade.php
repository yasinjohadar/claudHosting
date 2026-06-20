@extends('admin.layouts.master')
@section('page-title') إعدادات Coolify @stop
@push('styles')
@include('admin.coolify.settings.partials.settings-hub-styles')
@endpush
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">مركز إعدادات Coolify</h4>
                <nav><ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.coolify.overview') }}">Coolify</a></li>
                    <li class="breadcrumb-item active">الإعدادات</li>
                </ol></nav>
                <p class="text-muted small mb-0 mt-2">كل الإعدادات تُحفظ في قاعدة البيانات من هذه الصفحات — لا حاجة لتعديل <code>.env</code>.</p>
            </div>
            <a href="{{ route('admin.coolify.overview') }}" class="btn btn-light btn-sm">لوحة Coolify</a>
        </div>

        @include('admin.coolify.partials.alerts')
        @include('admin.coolify.settings.partials.readiness-badges')

        <div class="row g-3 mb-4">
            @foreach($sections as $key => $meta)
            <div class="col-md-6 col-xl-4">
                <a href="{{ route('admin.coolify.settings.section', $key) }}" class="coolify-settings-card-link">
                    <div class="coolify-settings-card coolify-settings-card--{{ $meta['color'] ?? 'primary' }}">
                        <span class="coolify-settings-card__icon"><i class="{{ $meta['icon'] ?? 'fe fe-settings' }}"></i></span>
                        <div class="coolify-settings-card__body">
                            <h3 class="coolify-settings-card__title">{{ $meta['label'] }}</h3>
                            <p class="coolify-settings-card__desc">{{ $meta['description'] ?? '' }}</p>
                        </div>
                        <span class="coolify-settings-card__arrow"><i class="fe fe-chevron-left"></i></span>
                    </div>
                </a>
            </div>
            @endforeach
        </div>

        <div class="card custom-card">
            <div class="card-body py-3">
                <div class="d-flex flex-wrap gap-3 small">
                    <span><strong>API:</strong> @if($connected)<span class="text-success">متصل</span>@elseif($configured)<span class="text-warning">مضبوط</span>@else<span class="text-muted">غير مضبوط</span>@endif</span>
                    <span><strong>SSH:</strong> @if($form['has_ssh_key'] ?? false)<span class="text-success">مفتاح محفوظ</span>@else<span class="text-muted">غير مضبوط</span>@endif</span>
                    <span><strong>Terminal:</strong> @if($form['terminal_bridge_enabled'] ?? false)<span class="text-success">مفعّل</span>@else<span class="text-muted">معطّل</span>@endif</span>
                    <span><strong>لقطات S3:</strong> @if($snapshotStorageReady ?? false)<span class="text-success">جاهز</span>@else<span class="text-warning">يحتاج إعداد</span>@endif</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
