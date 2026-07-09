@extends('admin.layouts.master')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/admin-email-settings.css') }}?v={{ @filemtime(public_path('assets/css/admin-email-settings.css')) ?: '1' }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin-mail-templates.css') }}?v={{ @filemtime(public_path('assets/css/admin-mail-templates.css')) ?: '1' }}">
@endpush

@section('page-title')
إضافة قالب بريد
@stop

@section('content')
<div class="main-content app-content admin-email-templates-page">
    <div class="container-fluid">

        @include('admin.components.alerts')

        <div class="my-4 page-header-breadcrumb">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.mail-templates.index') }}">قوالب البريد</a></li>
                    <li class="breadcrumb-item active">إضافة قالب</li>
                </ol>
            </nav>
        </div>

        <div class="group-show-hero dashboard-fade-in mb-4">
            <div class="row align-items-start g-3">
                <div class="col-lg-8">
                    <div class="d-flex align-items-start gap-3">
                        <span class="admin-group-form-page__icon"><i class="fe fe-plus-circle"></i></span>
                        <div>
                            <span class="group-show-hero__eyebrow">قالب جديد</span>
                            <h2 class="group-show-hero__title mb-2">إضافة قالب بريد</h2>
                            <p class="group-show-hero__desc mb-0">أنشئ قالباً جديداً مع محرر HTML غني كما في المقالات.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="group-show-actions group-show-actions--single">
                        <a href="{{ route('admin.mail-templates.index') }}" class="group-show-action group-show-action--info">
                            <span class="group-show-action__icon"><i class="fe fe-arrow-right"></i></span>
                            <span class="group-show-action__text">رجوع للقائمة</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.mail-templates.store') }}" id="mailTemplateForm">
            @csrf
            @include('admin.mail-templates.partials.form')
        </form>

    </div>
</div>
@endsection

@section('scripts')
@include('admin.mail-templates.partials.editor-scripts')
@endsection
