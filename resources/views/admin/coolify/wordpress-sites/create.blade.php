@extends('admin.layouts.master')
@section('page-title') إنشاء موقع WordPress @stop

@push('styles')
    @include('admin.coolify.wordpress-sites.partials.wizard-styles')
@endpush

@section('content')
<div class="main-content app-content">
    <div class="container-fluid wp-wizard-page">
        <a href="{{ route('admin.coolify.wordpress-sites.index') }}" class="wp-wizard-back">
            <i class="fe fe-arrow-left"></i> رجوع للقائمة
        </a>

        @include('admin.coolify.partials.alerts')

        <div class="card custom-card wp-wizard-shell">
            <div class="card-body p-0">
                <div class="wp-wizard-hero">
                    <div class="wp-wizard-hero__icon" aria-hidden="true">
                        <i class="fab fa-wordpress"></i>
                    </div>
                    <div>
                        <h1 class="wp-wizard-hero__title">معالج إنشاء موقع WordPress</h1>
                        <p class="wp-wizard-hero__sub">ثلاث خطوات لنشر موقع جاهز على Coolify مع حماية وتسريع اختياري</p>
                    </div>
                </div>

                @include('admin.coolify.wordpress-sites.partials.wizard-stepper', ['step' => $step])

                <div class="wp-wizard-body">
                    @if ($step === 1)
                        @include('admin.coolify.wordpress-sites.partials.wizard-step-1')
                    @elseif ($step === 2)
                        @include('admin.coolify.wordpress-sites.partials.wizard-step-2')
                    @else
                        @include('admin.coolify.wordpress-sites.partials.wizard-step-3')
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @include('admin.coolify.wordpress-sites.partials.wizard-scripts')
@endpush
