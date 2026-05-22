@extends('admin.layouts.master')
@section('page-title') نطاقات name.com @stop
@section('content')
<style>
    .namecom-table thead th { font-weight: 600; font-size: 0.8rem; border-bottom-width: 2px; white-space: nowrap; }
    .namecom-table tbody tr { transition: background-color 0.15s ease; }
    .namecom-table tbody tr:hover { background-color: rgba(var(--primary-rgb, 132, 90, 223), 0.04); }
    .namecom-table .sort-link { color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 0.25rem; }
    .namecom-table .sort-link:hover, .namecom-table .sort-link.active { color: var(--primary-color, #845adf); font-weight: 700; }
    .namecom-status-pill { font-size: 0.75rem; font-weight: 600; padding: 0.35em 0.75em; border-radius: 50rem; }
    .namecom-table tr.row-expiring-soon { --bs-table-bg: rgba(var(--warning-rgb, 255, 193, 7), 0.08); }
</style>
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4 flex-wrap gap-2">
            <div>
                <h4 class="mb-0">نطاقات name.com</h4>
                <p class="text-muted small mb-0">كل النطاقات المسجّلة في حساب name.com — من API مباشرة.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('admin.namecom.domains.index', ['refresh' => 1, 'sort' => $sort ?? 'expires', 'dir' => $sortDir ?? 'asc', 'q' => $q ?? '']) }}" class="btn btn-outline-secondary btn-sm">تحديث</a>
                <a href="{{ route('admin.namecom.settings.index') }}" class="btn btn-outline-primary btn-sm">الإعدادات</a>
            </div>
        </div>
        @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        @if(!empty($error))<div class="alert alert-danger">{{ $error }}</div>@endif

        <form method="GET" class="card custom-card mb-3">
            <div class="card-body row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label">بحث بالنطاق</label>
                    <input type="text" name="q" class="form-control" value="{{ $q ?? '' }}" placeholder="example.com">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">بحث</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.namecom.domains.index') }}" class="btn btn-light w-100">إعادة</a>
                </div>
            </div>
        </form>

        <div class="card custom-card overflow-hidden">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="card-title mb-0">قائمة النطاقات</div>
                <span class="badge bg-primary-transparent text-primary">{{ count($domains) }} نطاق</span>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0 namecom-table">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">النطاق</th>
                            <th>تاريخ التسجيل</th>
                            <th>
                                @php
                                    $currentSort = $sort ?? 'expires';
                                    $currentDir = $sortDir ?? 'asc';
                                    $nextDir = ($currentSort === 'expires' && $currentDir === 'asc') ? 'desc' : 'asc';
                                    $sortParams = ['sort' => 'expires', 'dir' => $nextDir];
                                    if (!empty($q)) { $sortParams['q'] = $q; }
                                @endphp
                                <a href="{{ route('admin.namecom.domains.index', $sortParams) }}" class="sort-link {{ $currentSort === 'expires' ? 'active' : '' }}">
                                    تاريخ الانتهاء
                                    @if($currentSort === 'expires')
                                        <i class="fe fe-chevron-{{ $currentDir === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </a>
                            </th>
                            <th>تجديد تلقائي</th>
                            <th>قفل</th>
                            <th>خصوصية</th>
                            <th>الحالة</th>
                            <th class="pe-4"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($domains as $d)
                        @php
                            $name = \App\Http\Controllers\Admin\Namecom\NamecomDomainController::domainName($d);
                            $expiresRaw = $d['expireDate'] ?? $d['expires_at'] ?? null;
                            $status = \App\Http\Controllers\Admin\Namecom\NamecomDomainController::formatStatus($d);
                            $expiringSoon = \App\Http\Controllers\Admin\Namecom\NamecomDomainController::isExpiringSoon($expiresRaw);
                        @endphp
                        <tr class="{{ $expiringSoon ? 'row-expiring-soon' : '' }}">
                            <td class="ps-4">
                                <span class="avatar avatar-sm bg-primary-transparent rounded-circle me-2 d-inline-flex align-items-center justify-content-center">
                                    <i class="fe fe-globe text-primary fs-12"></i>
                                </span>
                                <strong dir="ltr">{{ $name }}</strong>
                            </td>
                            <td class="text-muted">{{ \App\Http\Controllers\Admin\Namecom\NamecomDomainController::formatDate($d['createDate'] ?? $d['registered_at'] ?? null) }}</td>
                            <td>
                                <span class="{{ $expiringSoon ? 'text-warning fw-semibold' : '' }}">
                                    {{ \App\Http\Controllers\Admin\Namecom\NamecomDomainController::formatDate($expiresRaw) }}
                                </span>
                                @if($expiringSoon)<span class="badge bg-warning-transparent text-warning ms-1">قريباً</span>@endif
                            </td>
                            <td>
                                @if(!empty($d['autorenewEnabled']))
                                    <span class="badge bg-success-transparent text-success">نعم</span>
                                @else
                                    <span class="badge bg-light text-muted">لا</span>
                                @endif
                            </td>
                            <td>{{ \App\Http\Controllers\Admin\Namecom\NamecomDomainController::formatBool($d['locked'] ?? false) }}</td>
                            <td>{{ \App\Http\Controllers\Admin\Namecom\NamecomDomainController::formatBool($d['privacyEnabled'] ?? false) }}</td>
                            <td>
                                <span class="namecom-status-pill badge {{ $status['badge_class'] }}">
                                    @if($status['is_active'])<i class="fe fe-check-circle me-1"></i>@endif
                                    {{ $status['label'] }}
                                </span>
                            </td>
                            <td class="pe-4 text-end">
                                <a href="{{ route('admin.namecom.domains.show', ['domain' => $name]) }}" class="btn btn-sm btn-outline-primary">التفاصيل</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fe fe-inbox fs-2 d-block mb-2 opacity-50"></i>
                                لا توجد نطاقات — تحقق من <a href="{{ route('admin.namecom.settings.index') }}">الإعدادات</a> واضغط تحديث.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if(count($domains) > 0)
            <div class="card-footer text-muted small">
                الترتيب: <strong>{{ ($sortDir ?? 'asc') === 'asc' ? 'الأقرب انتهاءً أولاً' : 'الأبعد أولاً' }}</strong>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
