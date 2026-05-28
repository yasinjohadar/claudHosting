@extends('admin.layouts.master')

@section('page-title')
إعدادات SMTP
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">إعدادات SMTP</h4>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="card custom-card mb-4">
            <div class="card-header"><h6 class="mb-0">إعدادات الإرسال</h6></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.mail-settings.update') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="mail_enabled" id="mail_enabled" value="1" {{ ($settings['mail_enabled'] ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="mail_enabled">تفعيل البريد</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Mailer</label>
                            <select name="mailer" class="form-select">
                                <option value="smtp" {{ ($settings['mailer'] ?? 'smtp') === 'smtp' ? 'selected' : '' }}>smtp</option>
                                <option value="log" {{ ($settings['mailer'] ?? '') === 'log' ? 'selected' : '' }}>log</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Host</label>
                            <input type="text" name="host" class="form-control" value="{{ old('host', $settings['host'] ?? '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Port</label>
                            <input type="number" name="port" class="form-control" value="{{ old('port', $settings['port'] ?? 2525) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" value="{{ old('username', $settings['username'] ?? '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" value="">
                            <small class="text-muted">اتركه فارغاً للاحتفاظ بالقيمة الحالية.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Encryption</label>
                            <select name="encryption" class="form-select">
                                <option value="none" {{ ($settings['encryption'] ?? '') === '' ? 'selected' : '' }}>None</option>
                                <option value="tls" {{ ($settings['encryption'] ?? '') === 'tls' ? 'selected' : '' }}>tls</option>
                                <option value="ssl" {{ ($settings['encryption'] ?? '') === 'ssl' ? 'selected' : '' }}>ssl</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">From Address</label>
                            <input type="email" name="from_address" class="form-control" value="{{ old('from_address', $settings['from_address'] ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">From Name</label>
                            <input type="text" name="from_name" class="form-control" value="{{ old('from_name', $settings['from_name'] ?? '') }}">
                        </div>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-primary" type="submit">حفظ الإعدادات</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header"><h6 class="mb-0">اختبار SMTP</h6></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.mail-settings.test') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">بريد الاختبار</label>
                            <input type="email" name="test_email" class="form-control" required>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button class="btn btn-success w-100" type="submit">إرسال اختبار</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
