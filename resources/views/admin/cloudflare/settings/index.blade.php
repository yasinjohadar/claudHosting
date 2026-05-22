@extends('admin.layouts.master')
@section('page-title') إعدادات Cloudflare @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4">
            <div>
                <h4 class="mb-0">إعدادات Cloudflare API</h4>
                <nav><ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.domains.index') }}">النطاقات</a></li>
                    <li class="breadcrumb-item active">إعدادات Cloudflare</li>
                </ol></nav>
            </div>
        </div>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        @if($accountIdCleared ?? false)
        <div class="alert alert-warning">تم تجاهل Account ID غير صالح (كان بريداً وليس معرّف حساب). اتركه فارغاً أو الصق الـ hex من اختبار الاتصال.</div>
        @endif
        <div class="row">
            <div class="col-xl-8">
                <div class="card custom-card mb-4">
                    <div class="card-header"><div class="card-title">بيانات الاتصال</div></div>
                    <div class="card-body">
                        <p class="text-muted small">تُحفظ في <code>system_settings</code> (مجموعة cloudflare). التوكن مشفّر.</p>
                        <form action="{{ route('admin.cloudflare.settings.update') }}" method="POST">
                            @csrf @method('PUT')
                            <div class="mb-3">
                                <label class="form-label">رمز API *</label>
                                <input type="password" name="api_token" class="form-control" dir="ltr"
                                    placeholder="{{ ($form['has_token'] ?? false) ? 'اتركه فارغاً للإبقاء على الرمز الحالي' : 'API Token من Cloudflare' }}">
                                @if($form['has_token'] ?? false)<div class="form-text text-success">يوجد رمز محفوظ.</div>@endif
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Account ID (اختياري)</label>
                                <input type="text" name="account_id" class="form-control" dir="ltr"
                                    value="{{ old('account_id', $form['account_id'] ?? '') }}"
                                    placeholder="32 حرف hex — يُجلب تلقائياً إن تُرك فارغاً"
                                    pattern="[a-fA-F0-9]{32}" maxlength="32">
                                <div class="form-text">ليس البريد الإلكتروني. مثال: <code dir="ltr">8ac849e8f93eb206bd8d2703e0b6f7ca</code></div>
                                @error('account_id')<div class="text-danger small">{{ $message }}</div>@enderror
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
                        @if($accountId)<p class="small text-muted">Account ID: <code>{{ $accountId }}</code></p>@endif
                        <button type="button" class="btn btn-outline-primary" id="btnTestCf" @if(!$configured) disabled @endif>اختبار</button>
                        <div id="cfTestResult" class="mt-3"></div>
                    </div>
                </div>
            </div>
            @if($configured && $tokenPermissions)
            <div class="col-xl-4">
                <div class="card custom-card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="card-title mb-0">صلاحيات التوكن</div>
                        @if($tokenPermissions['verified'] ?? false)
                            <span class="badge bg-{{ ($tokenPermissions['status'] ?? '') === 'active' ? 'success' : 'warning' }}">
                                {{ $tokenPermissions['status'] ?? '—' }}
                            </span>
                        @elseif($tokenPermissions['api_connected'] ?? false)
                            <span class="badge bg-success">متصل</span>
                        @endif
                    </div>
                    <div class="card-body">
                        @if($tokenPermissions['api_connected'] ?? false)
                            <p class="small mb-2"><span class="badge bg-success">API يعمل</span> التوكن يصل إلى Cloudflare.</p>
                        @else
                            <p class="small mb-2"><span class="badge bg-danger">API لا يعمل</span> تحقق من الرمز.</p>
                        @endif

                        @if($tokenPermissions['verify_error'] ?? null)
                            <div class="alert alert-info py-2 small mb-3">
                                تفاصيل التوكن من Cloudflare غير متاحة: {{ $tokenPermissions['verify_error'] }}.
                                <br>شائع مع توكنات Zone فقط — القائمة أدناه تُظهر ما تسمح به فعلياً.
                            </div>
                        @elseif($tokenPermissions['verified'] ?? false)
                            @if($tokenPermissions['expires_on'] ?? null)
                                <p class="small text-muted mb-2">ينتهي: <span dir="ltr">{{ $tokenPermissions['expires_on'] }}</span></p>
                            @else
                                <p class="small text-muted mb-2">بدون تاريخ انتهاء</p>
                            @endif
                            @if($tokenPermissions['token_id'] ?? null)
                                <p class="small text-muted mb-2">معرّف التوكن: <code dir="ltr" class="small">{{ Str::limit($tokenPermissions['token_id'], 20) }}</code></p>
                            @endif
                        @endif

                        @if($tokenPermissions['details_error'] ?? null)
                            <div class="alert alert-warning py-2 small mb-3">{{ $tokenPermissions['details_error'] }}</div>
                        @endif

                        @if(!empty($tokenPermissions['policies']))
                            <h6 class="fs-12 text-uppercase text-muted mb-2">السياسات على التوكن</h6>
                            @foreach($tokenPermissions['policies'] as $policy)
                            <div class="border rounded p-2 mb-2 small">
                                <span class="badge bg-{{ ($policy['effect'] ?? 'allow') === 'allow' ? 'success' : 'danger' }} mb-1">
                                    {{ $policy['effect'] ?? 'allow' }}
                                </span>
                                @if(!empty($policy['permissions']))
                                    <ul class="mb-1 ps-3">
                                        @foreach($policy['permissions'] as $perm)
                                        <li dir="ltr" class="text-break">{{ $perm }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                                <p class="text-muted mb-0"><strong>الموارد:</strong> {{ $policy['resources'] ?? '—' }}</p>
                            </div>
                            @endforeach
                        @endif

                        <h6 class="fs-12 text-uppercase text-muted mb-2 mt-2">ما يمكن التحكم به من اللوحة</h6>
                        <p class="text-muted small mb-2">اختبار مباشر عبر API:</p>
                        <ul class="list-group list-group-flush">
                            @foreach($tokenPermissions['panel_capabilities'] ?? [] as $cap)
                            <li class="list-group-item px-0 d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <strong>{{ $cap['label'] }}</strong>
                                    <div class="text-muted small">{{ $cap['description'] }}</div>
                                    @if(!empty($cap['hint']))
                                    <div class="text-muted small" dir="ltr">{{ $cap['hint'] }}</div>
                                    @endif
                                </div>
                                @if($cap['allowed'] ?? false)
                                    <span class="badge bg-success flex-shrink-0">متاح</span>
                                @else
                                    <span class="badge bg-secondary flex-shrink-0">غير متاح</span>
                                @endif
                            </li>
                            @endforeach
                        </ul>
                        <p class="text-muted small mt-3 mb-0">
                            Cloudflare → API Tokens: Zone Read + DNS Read + Registrar Read + Account Read
                            (أو Read all resources).
                        </p>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@push('scripts')
<script>
document.getElementById('btnTestCf')?.addEventListener('click', function() {
    const el = document.getElementById('cfTestResult');
    el.innerHTML = '<span class="text-muted">جاري الاختبار...</span>';
    fetch(@json(route('admin.cloudflare.settings.test')), {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': @json(csrf_token()), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    }).then(r => r.json()).then(d => {
        el.innerHTML = d.success
            ? '<div class="alert alert-success mb-0">' + d.message + (d.zones_count != null ? ' — ' + d.zones_count + ' zone' : '') + '</div>'
            : '<div class="alert alert-danger mb-0">' + (d.message || 'فشل') + '</div>';
    }).catch(e => { el.innerHTML = '<div class="alert alert-danger">' + e.message + '</div>'; });
});
</script>
@endpush
@endsection
