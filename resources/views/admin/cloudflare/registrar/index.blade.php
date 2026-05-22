@extends('admin.layouts.master')
@section('page-title') مسجّل Cloudflare @stop
@section('content')
<style>
    .cf-registrar-table thead th {
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: none;
        border-bottom-width: 2px;
        white-space: nowrap;
    }
    .cf-registrar-table tbody tr {
        transition: background-color 0.15s ease;
    }
    .cf-registrar-table tbody tr:hover {
        background-color: rgba(var(--primary-rgb, 132, 90, 223), 0.04);
    }
    .cf-registrar-table .domain-cell {
        font-size: 0.95rem;
        letter-spacing: 0.01em;
    }
    .cf-registrar-table .sort-link {
        color: inherit;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }
    .cf-registrar-table .sort-link:hover {
        color: var(--primary-color, #845adf);
    }
    .cf-registrar-table .sort-link.active {
        color: var(--primary-color, #845adf);
        font-weight: 700;
    }
    .cf-status-pill {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.35em 0.75em;
        border-radius: 50rem;
    }
    .cf-registrar-table tr.row-expiring-soon {
        --bs-table-bg: rgba(var(--warning-rgb, 255, 193, 7), 0.08);
    }
</style>
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4 flex-wrap gap-2">
            <div>
                <h4 class="mb-0">مسجّل عند Cloudflare (Registrar)</h4>
                <p class="text-muted small mb-0">فقط النطاقات التي <strong>اشترَيتها/سجّلتها عند Cloudflare</strong> كمسجّل. لرؤية كل نطاقات الحساب (DNS) افتح Zones.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('admin.cloudflare.zones.index') }}" class="btn btn-primary btn-sm">جميع النطاقات — Zones ({{ $zonesCount ?? 0 }})</a>
                <a href="{{ route('admin.cloudflare.registrar.index', ['refresh' => 1, 'sort' => $sort ?? 'expires', 'dir' => $sortDir ?? 'asc']) }}" class="btn btn-outline-secondary btn-sm">تحديث Registrar</a>
            </div>
        </div>
        @if(($zonesCount ?? 0) > 0)
        <div class="alert alert-{{ count($domains) > 0 ? 'info' : 'warning' }}">
            على حسابك <strong>{{ $zonesCount }}</strong> نطاقاً في
            <a href="{{ route('admin.cloudflare.zones.index') }}"><strong>Cloudflare Zones</strong></a>
            (كل النطاقات المضافة لـ DNS).
            @if(count($domains) > 0)
                منها <strong>{{ count($domains) }}</strong>@if(($registrarTotal ?? 0) > count($domains)) / {{ $registrarTotal }}@endif مسجّلة عند Cloudflare كمسجّل.
                الباقي مسجّل عند مسجّل آخر ويستخدم Cloudflare للـ DNS فقط.
            @else
                <br>لا يوجد أي نطاق مسجّل عند Cloudflare كمسجّل — هذا طبيعي إن سجّلت نطاقاتك عند GoDaddy أو غيره.
                <strong>لعرض كل نطاقاتك اضغط الزر الأزرق «جميع النطاقات — Zones».</strong>
            @endif
        </div>
        @endif
        @if(!empty($error))<div class="alert alert-danger">{{ $error }}</div>@endif

        <div class="card custom-card overflow-hidden">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="card-title mb-0">قائمة النطاقات</div>
                <span class="badge bg-primary-transparent text-primary">{{ count($domains) }} نطاق</span>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0 cf-registrar-table">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">النطاق</th>
                            <th>المسجّل الحالي</th>
                            <th>تاريخ التسجيل</th>
                            <th>
                                @php
                                    $currentSort = $sort ?? 'expires';
                                    $currentDir = $sortDir ?? 'asc';
                                    $nextDir = ($currentSort === 'expires' && $currentDir === 'asc') ? 'desc' : 'asc';
                                @endphp
                                <a href="{{ route('admin.cloudflare.registrar.index', ['sort' => 'expires', 'dir' => $nextDir]) }}"
                                   class="sort-link {{ $currentSort === 'expires' ? 'active' : '' }}">
                                    تاريخ الانتهاء
                                    @if($currentSort === 'expires')
                                        <i class="fe fe-chevron-{{ $currentDir === 'asc' ? 'up' : 'down' }}"></i>
                                    @else
                                        <i class="fe fe-chevrons-up text-muted opacity-50"></i>
                                    @endif
                                </a>
                            </th>
                            <th>تجديد تلقائي</th>
                            <th class="pe-4">الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($domains as $d)
                        @php
                            $name = $d['name'] ?? $d['id'] ?? '—';
                            $expiresRaw = $d['expires_at'] ?? $d['payment_expires_at'] ?? null;
                            $status = \App\Http\Controllers\Admin\Cloudflare\CloudflareRegistrarController::formatStatus(
                                $d['last_known_status'] ?? $d['registry_statuses'] ?? null
                            );
                            $expiringSoon = \App\Http\Controllers\Admin\Cloudflare\CloudflareRegistrarController::isExpiringSoon($expiresRaw);
                        @endphp
                        <tr class="{{ $expiringSoon ? 'row-expiring-soon' : '' }}">
                            <td class="ps-4 domain-cell">
                                <span class="avatar avatar-sm bg-primary-transparent rounded-circle me-2 d-inline-flex align-items-center justify-content-center">
                                    <i class="fe fe-globe text-primary fs-12"></i>
                                </span>
                                <strong dir="ltr">{{ $name }}</strong>
                            </td>
                            <td>
                                <span class="badge bg-info-transparent text-info">{{ $d['current_registrar'] ?? 'Cloudflare' }}</span>
                            </td>
                            <td class="text-muted">
                                {{ \App\Http\Controllers\Admin\Cloudflare\CloudflareRegistrarController::formatDate($d['registered_at'] ?? $d['created_at'] ?? null) }}
                            </td>
                            <td>
                                <span class="{{ $expiringSoon ? 'text-warning fw-semibold' : '' }}">
                                    {{ \App\Http\Controllers\Admin\Cloudflare\CloudflareRegistrarController::formatDate($expiresRaw) }}
                                </span>
                                @if($expiringSoon)
                                    <span class="badge bg-warning-transparent text-warning ms-1">قريباً</span>
                                @endif
                            </td>
                            <td>
                                @if(!empty($d['auto_renew']))
                                    <span class="badge bg-success-transparent text-success"><i class="fe fe-check-circle me-1"></i>نعم</span>
                                @else
                                    <span class="badge bg-light text-muted">لا</span>
                                @endif
                            </td>
                            <td class="pe-4">
                                <span class="cf-status-pill badge {{ $status['badge_class'] }}">
                                    @if($status['is_active'])
                                        <i class="fe fe-check-circle me-1"></i>
                                    @endif
                                    {{ $status['label'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="fe fe-inbox fs-2 d-block mb-2 opacity-50"></i>
                                لا توجد نطاقات عند Cloudflare Registrar.
                                @if(($zonesCount ?? 0) > 0)
                                    <br><a href="{{ route('admin.cloudflare.zones.index') }}">عرض {{ $zonesCount }} نطاق في Zones</a>
                                @else
                                    <br>تحقق من <a href="{{ route('admin.cloudflare.settings.index') }}">إعدادات API</a> ثم أعد تحميل الصفحة.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if(count($domains) > 0)
            <div class="card-footer text-muted small">
                الترتيب حسب تاريخ الانتهاء:
                <strong>{{ ($sortDir ?? 'asc') === 'asc' ? 'الأقرب أولاً' : 'الأبعد أولاً' }}</strong>
                — اضغط عنوان العمود لتبديل الاتجاه.
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
