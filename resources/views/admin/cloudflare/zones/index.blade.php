@extends('admin.layouts.master')
@section('page-title') Cloudflare Zones @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4 flex-wrap gap-2">
            <div>
                <h4 class="mb-0">جميع نطاقات الحساب (Cloudflare Zones)</h4>
                <p class="text-muted small mb-0">كل النطاقات على حسابك في Cloudflare — للـ DNS. هذا هو المكان الصحيح لرؤية «كل دوميناتي».</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.cloudflare.zones.index', ['refresh' => 1]) }}" class="btn btn-outline-secondary btn-sm">تحديث</a>
                <a href="{{ route('admin.cloudflare.registrar.index') }}" class="btn btn-outline-primary btn-sm">مسجّل عند CF فقط</a>
            </div>
        </div>
        @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        @if(!empty($error))<div class="alert alert-danger">{{ $error }}</div>@endif
        <div class="row mb-3">
            <div class="col-md-4"><div class="card custom-card"><div class="card-body text-center"><h3 class="mb-0">{{ $stats['total'] ?? 0 }}</h3><small class="text-muted">الإجمالي</small></div></div></div>
            <div class="col-md-4"><div class="card custom-card"><div class="card-body text-center"><h3 class="mb-0 text-success">{{ $stats['active'] ?? 0 }}</h3><small class="text-muted">نشط</small></div></div></div>
            <div class="col-md-4"><div class="card custom-card"><div class="card-body text-center"><h3 class="mb-0 text-warning">{{ $stats['pending'] ?? 0 }}</h3><small class="text-muted">قيد الانتظار</small></div></div></div>
        </div>
        <form method="GET" class="card custom-card mb-3">
            <div class="card-body row g-2 align-items-end">
                <div class="col-md-4"><label class="form-label">بحث بالاسم</label><input type="text" name="name" class="form-control" value="{{ request('name') }}"></div>
                <div class="col-md-3"><label class="form-label">الحالة</label>
                    <select name="status" class="form-control">
                        <option value="">الكل</option>
                        <option value="active" @selected(request('status')==='active')>active</option>
                        <option value="pending" @selected(request('status')==='pending')>pending</option>
                    </select>
                </div>
                <div class="col-md-2"><button class="btn btn-primary w-100">تصفية</button></div>
                <div class="col-md-2"><a href="{{ route('admin.cloudflare.zones.index') }}" class="btn btn-light w-100">إعادة</a></div>
            </div>
        </form>
        <div class="card custom-card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>النطاق</th><th>الحالة</th><th>الخطة</th><th>تاريخ الإضافة</th><th></th></tr></thead>
                    <tbody>
                    @forelse($zones as $z)
                        <tr>
                            <td><strong>{{ $z['name'] ?? '—' }}</strong><br><code class="small">{{ $z['id'] ?? '' }}</code></td>
                            <td>
                                @php $st = $z['status'] ?? ''; @endphp
                                <span class="badge bg-{{ $st === 'active' ? 'success' : ($st === 'pending' ? 'warning' : 'secondary') }}-transparent">{{ $st ?: '—' }}</span>
                            </td>
                            <td>{{ $z['plan']['name'] ?? ($z['plan']['legacy_id'] ?? '—') }}</td>
                            <td>{{ \App\Http\Controllers\Admin\Cloudflare\CloudflareZoneController::formatDate($z['created_on'] ?? null) }}</td>
                            <td><a href="{{ route('admin.cloudflare.zones.show', $z['id']) }}" class="btn btn-sm btn-outline-primary">تفاصيل</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">لا توجد zones</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
