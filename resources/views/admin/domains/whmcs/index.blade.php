@extends('admin.layouts.master')
@section('page-title') نطاقات WHMCS @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4 flex-wrap gap-2">
            <div>
                <h4 class="mb-0">نطاقات WHMCS</h4>
                <p class="text-muted small mb-0">تسجيل، صلاحية، فوترة، ومسجّل — من مزامنة GetClientsDomains</p>
            </div>
            <form method="POST" action="{{ route('admin.domains.whmcs.sync') }}" class="d-inline">@csrf
                <button type="submit" class="btn btn-primary"><i class="fe fe-refresh-cw"></i> مزامنة من WHMCS</button>
            </form>
        </div>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if($needsSync)<div class="alert alert-info">لا توجد نطاقات محلية — اضغط «مزامنة من WHMCS» لجلب البيانات.</div>@endif
        <div class="row mb-3">
            <div class="col-md-4"><div class="card custom-card"><div class="card-body text-center"><h4>{{ $stats['total'] }}</h4><small>الإجمالي</small></div></div></div>
            <div class="col-md-4"><div class="card custom-card"><div class="card-body text-center"><h4 class="text-success">{{ $stats['active'] }}</h4><small>نشط</small></div></div></div>
            <div class="col-md-4"><div class="card custom-card"><div class="card-body text-center"><h4 class="text-warning">{{ $stats['expiring'] }}</h4><small>تنتهي خلال 30 يوماً</small></div></div></div>
        </div>
        <form method="GET" class="card custom-card mb-3">
            <div class="card-body row g-2 align-items-end">
                <div class="col-md-4"><input type="text" name="q" class="form-control" placeholder="بحث بالنطاق" value="{{ request('q') }}"></div>
                <div class="col-md-3">
                    <select name="status" class="form-control">
                        <option value="">كل الحالات</option>
                        @foreach(['Active','Expired','Grace','Pending','Cancelled'] as $s)
                        <option value="{{ $s }}" @selected(request('status')===$s)>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><label class="form-check"><input type="checkbox" name="expiring" value="1" class="form-check-input" @checked(request('expiring'))> تنتهي قريباً</label></div>
                <div class="col-md-2"><button class="btn btn-outline-primary w-100">تصفية</button></div>
            </div>
        </form>
        <div class="card custom-card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>النطاق</th><th>العميل</th><th>الحالة</th><th>التسجيل</th><th>الانتهاء</th><th>الاستحقاق</th><th>المبلغ</th><th>المسجّل</th><th></th></tr></thead>
                    <tbody>
                    @forelse($domains as $d)
                        <tr class="{{ $d->isExpiringSoon() ? 'table-warning' : '' }}">
                            <td><strong>{{ $d->domain }}</strong></td>
                            <td>@if($d->customer)<a href="{{ route('admin.customers.show', $d->customer_id) }}">{{ $d->customer->full_name }}</a>@else — @endif</td>
                            <td><span class="badge bg-secondary-transparent">{{ $d->status_label }}</span></td>
                            <td>{{ $d->registrationdate?->format('Y-m-d') ?? '—' }}</td>
                            <td>{{ $d->expirydate?->format('Y-m-d') ?? '—' }}</td>
                            <td>{{ $d->nextduedate?->format('Y-m-d') ?? '—' }}</td>
                            <td>{{ $d->recurringamount !== null ? number_format($d->recurringamount, 2) : '—' }}</td>
                            <td>{{ $d->registrar ?? '—' }}</td>
                            <td><a href="{{ route('admin.domains.whmcs.show', $d) }}" class="btn btn-sm btn-outline-primary">عرض</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">لا توجد نطاقات — قم بالمزامنة</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($domains->hasPages())<div class="card-footer">{{ $domains->links() }}</div>@endif
        </div>
    </div>
</div>
@endsection
