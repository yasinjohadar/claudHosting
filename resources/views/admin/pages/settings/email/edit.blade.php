@extends('admin.layouts.master')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/admin-email-settings.css') }}?v={{ @filemtime(public_path('assets/css/admin-email-settings.css')) ?: '1' }}">
@endpush

@section('page-title')
    تعديل إعدادات البريد الإلكتروني
@stop

@section('content')
    <div class="main-content app-content admin-email-settings-page">
        <div class="container-fluid">

            @include('admin.components.alerts')

            <div class="my-4 page-header-breadcrumb">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.settings.email.index') }}">إعدادات البريد</a></li>
                        <li class="breadcrumb-item active">تعديل الإعدادات</li>
                    </ol>
                </nav>
            </div>

            <div class="group-show-hero dashboard-fade-in mb-4">
                <div class="row align-items-start g-3">
                    <div class="col-lg-8">
                        <div class="d-flex align-items-start gap-3">
                            <span class="admin-group-form-page__icon"><i class="fe fe-edit-2"></i></span>
                            <div>
                                <span class="group-show-hero__eyebrow">تعديل SMTP</span>
                                <h2 class="group-show-hero__title mb-2">{{ $providers[$emailSetting->provider]['name'] ?? 'إعداد مخصص' }}</h2>
                                <p class="group-show-hero__desc mb-2">{{ $emailSetting->mail_from_address }}</p>
                                @if($emailSetting->is_active)
                                    <span class="group-show-chip group-show-chip--sm text-success">الإعداد النشط حالياً</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="group-show-actions">
                            <a href="{{ route('admin.settings.email.index') }}" class="group-show-action group-show-action--info">
                                <span class="group-show-action__icon"><i class="fe fe-arrow-right"></i></span>
                                <span class="group-show-action__text">رجوع للقائمة</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <form action="{{ route('admin.settings.email.update', $emailSetting->id) }}" method="POST" class="dashboard-fade-in">
                @csrf
                @method('PUT')
                @include('admin.pages.settings.email.partials.form-fields', ['providers' => $providers, 'emailSetting' => $emailSetting])
            </form>

        </div>
    </div>

    <div class="modal fade" id="formSendTestEmailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title">إرسال بريد اختبار</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-2">
                    <p class="admin-group-form-hint mb-3">يُرسل بريداً فعلياً. إذا تركت كلمة المرور فارغة يُستخدم الإعداد المحفوظ.</p>
                    <label class="form-label fw-semibold">البريد الإلكتروني</label>
                    <input type="email" class="form-control" id="formSendTestEmailInput" value="{{ $emailSetting->mail_from_address }}" required>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-primary" id="formSendTestEmailBtn" onclick="submitFormSendTestEmail()">
                        <i class="fe fe-send me-1"></i>إرسال
                    </button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('scripts')
    @include('admin.pages.settings.email.partials.scripts')
@endsection
