@extends('admin.layouts.master')
@section('page-title') إعدادات WHM @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4">
            <div>
                <h4 class="mb-0">إعدادات WHM / cPanel</h4>
                <nav><ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                    <li class="breadcrumb-item active">إعدادات WHM</li>
                </ol></nav>
            </div>
            <a href="{{ route('admin.whm.accounts.index') }}" class="btn btn-outline-primary">حسابات الاستضافة</a>
        </div>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        <div class="row">
            <div class="col-xl-8">
                <div class="card custom-card mb-4">
                    <div class="card-header"><div class="card-title">بيانات الاتصال</div></div>
                    <div class="card-body">
                        <p class="text-muted small">تُحفظ في <code>system_settings</code> (مجموعة whm) — لا حاجة لملف <code>.env</code>. رمز API مشفّر.</p>
                        <form action="{{ route('admin.whm.settings.update') }}" method="POST">
                            @csrf @method('PUT')
                            <div class="mb-3">
                                <label class="form-label">عنوان WHM (منفذ 2087) *</label>
                                <input type="url" name="host" class="form-control @error('host') is-invalid @enderror" dir="ltr" required
                                    value="{{ old('host', $form['host'] ?? '') }}"
                                    placeholder="https://server.example.com:2087">
                                @error('host')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">اسم مستخدم WHM *</label>
                                <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" dir="ltr" required
                                    value="{{ old('username', $form['username'] ?? 'root') }}">
                                @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">رمز API *</label>
                                <input type="password" name="api_token" class="form-control" dir="ltr"
                                    placeholder="{{ ($form['has_token'] ?? false) ? 'اتركه فارغاً للإبقاء على الرمز الحالي' : 'API Token من WHM' }}">
                                @if($form['has_token'] ?? false)<div class="form-text text-success">يوجد رمز محفوظ.</div>@endif
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">الباقة الافتراضية *</label>
                                    <input type="text" name="default_package" class="form-control" required
                                        value="{{ old('default_package', $form['default_package'] ?? 'default') }}"
                                        placeholder="اسم Package في WHM">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">لاحقة النطاق الافتراضية</label>
                                    <input type="text" name="default_domain_suffix" class="form-control" dir="ltr"
                                        value="{{ old('default_domain_suffix', $form['default_domain_suffix'] ?? '') }}"
                                        placeholder="example.com (للتزويد التلقائي من الطلبات)">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">مهلة الطلب (ثوانٍ)</label>
                                    <input type="number" name="timeout" class="form-control" min="10" max="180"
                                        value="{{ old('timeout', $form['timeout'] ?? 60) }}">
                                </div>
                                <div class="col-md-6 mb-3 d-flex align-items-end">
                                    <div class="form-check mb-2">
                                        <input type="hidden" name="verify_ssl" value="0">
                                        <input type="checkbox" name="verify_ssl" value="1" class="form-check-input" id="verify_ssl"
                                            @checked(old('verify_ssl', $form['verify_ssl'] ?? true))>
                                        <label class="form-check-label" for="verify_ssl">التحقق من شهادة SSL</label>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">حفظ</button>
                            <a href="{{ route('admin.whm.accounts.index') }}" class="btn btn-light">حسابات الاستضافة</a>
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
                        @if($message && !($connected ?? false))<div class="alert alert-warning small mb-3">{{ $message }}</div>@endif
                        @if($version)<pre class="bg-light p-2 small mb-3" dir="ltr">{{ json_encode($version, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>@endif
                        <button type="button" class="btn btn-outline-primary" id="whm-test-btn" @disabled(!$configured)>اختبار الاتصال</button>
                        <span id="whm-test-result" class="ms-2 small"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.getElementById('whm-test-btn')?.addEventListener('click', function () {
    const btn = this;
    const out = document.getElementById('whm-test-result');
    btn.disabled = true;
    out.textContent = 'جاري الاختبار...';
    out.className = 'ms-2 small text-muted';
    fetch('{{ route('admin.whm.settings.test') }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
    }).then(r => r.json()).then(d => {
        out.textContent = d.message || (d.success ? 'نجح' : 'فشل');
        out.className = 'ms-2 small ' + (d.success ? 'text-success' : 'text-danger');
    }).catch(() => { out.textContent = 'خطأ في الطلب'; out.className = 'ms-2 small text-danger'; })
    .finally(() => { btn.disabled = false; });
});
</script>
@endpush
@endsection
