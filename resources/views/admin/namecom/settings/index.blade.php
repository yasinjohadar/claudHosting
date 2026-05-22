@extends('admin.layouts.master')
@section('page-title') إعدادات name.com @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4">
            <div>
                <h4 class="mb-0">إعدادات name.com API</h4>
                <nav><ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.domains.index') }}">النطاقات</a></li>
                    <li class="breadcrumb-item active">إعدادات name.com</li>
                </ol></nav>
            </div>
        </div>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        <div class="row">
            <div class="col-xl-8">
                <div class="card custom-card mb-4">
                    <div class="card-header"><div class="card-title">بيانات الاتصال</div></div>
                    <div class="card-body">
                        <p class="text-muted small">تُحفظ في <code>system_settings</code> (مجموعة namecom) — لا حاجة لملف <code>.env</code>. التوكن مشفّر.</p>
                        <form action="{{ route('admin.namecom.settings.update') }}" method="POST">
                            @csrf @method('PUT')
                            <div class="mb-3">
                                <label class="form-label">API Username *</label>
                                <input type="text" name="username" class="form-control" dir="ltr"
                                    value="{{ old('username', $form['username'] ?? '') }}"
                                    placeholder="من Account Settings → API Tokens">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">API Token *</label>
                                <input type="password" name="api_token" class="form-control" dir="ltr"
                                    placeholder="{{ ($form['has_token'] ?? false) ? 'اتركه فارغاً للإبقاء على التوكن الحالي' : 'API Token من name.com' }}">
                                @if($form['has_token'] ?? false)<div class="form-text text-success">يوجد توكن محفوظ.</div>@endif
                            </div>
                            <div class="mb-3">
                                <label class="form-label">API Base URL</label>
                                <input type="url" name="api_base" class="form-control" dir="ltr"
                                    value="{{ old('api_base', $form['api_base'] ?? config('namecom.defaults.api_base')) }}">
                                <div class="form-text">إنتاج: <code>https://api.name.com/v4</code> — تجريبي: <code>{{ config('namecom.sandbox_api_base') }}</code></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">مهلة الطلب (ثوانٍ)</label>
                                    <input type="number" name="timeout" class="form-control" min="5" max="120"
                                        value="{{ old('timeout', $form['timeout'] ?? 30) }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">مدة الكاش (ثوانٍ)</label>
                                    <input type="number" name="cache_ttl" class="form-control" min="60" max="3600"
                                        value="{{ old('cache_ttl', $form['cache_ttl'] ?? 600) }}">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">حفظ</button>
                            <a href="{{ route('admin.namecom.domains.index') }}" class="btn btn-outline-primary">عرض النطاقات</a>
                            <a href="{{ route('admin.domains.index') }}" class="btn btn-light">رجوع</a>
                        </form>
                    </div>
                </div>
                <div class="card custom-card">
                    <div class="card-header"><div class="card-title">اختبار الاتصال</div></div>
                    <div class="card-body">
                        <p><strong>الحالة:</strong>
                            @if($connected ?? false)<span class="badge bg-success">متصل</span>
                            @elseif($configured)<span class="badge bg-warning">مضبوط — فشل الاتصال</span>
                            @else<span class="badge bg-secondary">غير مضبوط</span>@endif
                        </p>
                        <button type="button" class="btn btn-outline-primary" id="btnTestNamecom" @if(!$configured) disabled @endif>اختبار</button>
                        <div id="namecomTestResult" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.getElementById('btnTestNamecom')?.addEventListener('click', function() {
    const el = document.getElementById('namecomTestResult');
    el.innerHTML = '<span class="text-muted">جاري الاختبار...</span>';
    fetch(@json(route('admin.namecom.settings.test')), {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': @json(csrf_token()), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    }).then(r => r.json()).then(d => {
        el.innerHTML = d.success
            ? '<div class="alert alert-success mb-0">' + d.message + (d.domains_count != null ? ' — ' + d.domains_count + ' نطاق' : '') + '</div>'
            : '<div class="alert alert-danger mb-0">' + (d.message || 'فشل') + '</div>';
    }).catch(e => { el.innerHTML = '<div class="alert alert-danger">' + e.message + '</div>'; });
});
</script>
@endpush
@endsection
