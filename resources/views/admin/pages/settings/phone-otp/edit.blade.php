@extends('admin.layouts.master')

@section('page-title')
    إعدادات OTP واتساب
@stop

@push('styles')
@include('admin.partials.domain-ui-styles')
@endpush

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="domain-page-hero">
            <div class="d-md-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <nav class="domain-page-hero__breadcrumb mb-2">
                        <a href="{{ route('admin.settings.email.index') }}">الإعدادات</a>
                        <span class="text-muted mx-1">/</span>
                        <span>OTP واتساب</span>
                    </nav>
                    <h1 class="domain-page-hero__title">إعدادات OTP واتساب</h1>
                    <p class="text-muted small mb-0">تخصيص رسالة الرمز وتفعيل الدخول واستعادة كلمة المرور عبر Evolution.</p>
                </div>
                <a href="{{ route('phone-login') }}" target="_blank" class="btn btn-success btn-sm">
                    <i class="fe fe-external-link me-1"></i> معاينة صفحة الدخول
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="domain-kpi-grid mb-4">
            <div class="domain-kpi domain-kpi--{{ ($health['otp_enabled'] ?? false) ? 'success' : 'secondary' }}">
                <span class="domain-kpi__icon"><i class="fe fe-power"></i></span>
                <div>
                    <div class="domain-kpi__label">حالة OTP</div>
                    <div class="domain-kpi__value">{{ ($health['otp_enabled'] ?? false) ? 'مفعّل' : 'معطّل' }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--{{ ($health['ready'] ?? false) ? 'success' : 'warning' }}">
                <span class="domain-kpi__icon"><i class="fe fe-check-circle"></i></span>
                <div>
                    <div class="domain-kpi__label">جاهزية الإرسال</div>
                    <div class="domain-kpi__value">{{ ($health['ready'] ?? false) ? 'جاهز' : 'غير جاهز' }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--info">
                <span class="domain-kpi__icon"><i class="fe fe-layers"></i></span>
                <div>
                    <div class="domain-kpi__label">Evolution instances</div>
                    <div class="domain-kpi__value">{{ $evolutionPoolCount }}</div>
                </div>
            </div>
        </div>

        @if(!empty($health['template_issues']))
            <div class="alert alert-warning border-0 shadow-sm mb-4">
                <strong>ملاحظات:</strong>
                <ul class="mb-0 mt-2">
                    @foreach($health['template_issues'] as $issue)
                        <li>{{ $issue }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card custom-card border-0 shadow-sm">
            <div class="card-header bg-transparent">
                <h4 class="card-title mb-1">إعدادات OTP</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.settings.phone-otp.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="enabled" value="1" id="enabled" @checked(old('enabled', $settings['enabled'] ?? false))>
                                <label class="form-check-label" for="enabled">تفعيل OTP عبر واتساب</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="login_enabled" value="1" id="login_enabled" @checked(old('login_enabled', $settings['login_enabled'] ?? false))>
                                <label class="form-check-label" for="login_enabled">تسجيل الدخول برمز OTP</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="reset_password_enabled" value="1" id="reset_password_enabled" @checked(old('reset_password_enabled', $settings['reset_password_enabled'] ?? false))>
                                <label class="form-check-label" for="reset_password_enabled">استعادة كلمة المرور عبر OTP</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="evolution_message_template">قالب رسالة OTP</label>
                            <textarea name="evolution_message_template" id="evolution_message_template" class="form-control" rows="3" required>{{ old('evolution_message_template', $settings['evolution_message_template'] ?? '') }}</textarea>
                            <small class="text-muted">يجب أن يحتوي على <code>{code}</code> — مثال: رمز التحقق: {code}</small>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="code_length">طول الرمز</label>
                            <input type="number" name="code_length" id="code_length" class="form-control" min="4" max="8" value="{{ old('code_length', $settings['code_length'] ?? 6) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="ttl_seconds">صلاحية الرمز (ثانية)</label>
                            <input type="number" name="ttl_seconds" id="ttl_seconds" class="form-control" min="60" max="3600" value="{{ old('ttl_seconds', $settings['ttl_seconds'] ?? 300) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="max_attempts">أقصى محاولات</label>
                            <input type="number" name="max_attempts" id="max_attempts" class="form-control" min="1" max="20" value="{{ old('max_attempts', $settings['max_attempts'] ?? 5) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="resend_cooldown_seconds">انتظار إعادة الإرسال</label>
                            <input type="number" name="resend_cooldown_seconds" id="resend_cooldown_seconds" class="form-control" min="30" max="600" value="{{ old('resend_cooldown_seconds', $settings['resend_cooldown_seconds'] ?? 60) }}">
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <button type="submit" class="btn btn-primary"><i class="fe fe-save me-1"></i> حفظ</button>
                    </div>
                </form>

                <form method="POST" action="{{ route('admin.settings.phone-otp.restore-defaults') }}" class="mt-3" onsubmit="return confirm('استعادة الإعدادات الافتراضية؟');">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm">استعادة الافتراضي</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
