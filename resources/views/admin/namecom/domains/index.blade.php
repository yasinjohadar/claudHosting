@extends('admin.layouts.master')
@section('page-title') نطاقات name.com @stop

@push('styles')
@include('admin.partials.domain-ui-styles')
@endpush

@section('content')
@php
    use App\Http\Controllers\Admin\Namecom\NamecomDomainController as NamecomCtrl;
    $total = count($domains);
    $activeCount = 0;
    $expiredCount = 0;
    $expiringCount = 0;
    foreach ($domains as $d) {
        $st = NamecomCtrl::formatStatus($d);
        if ($st['is_active']) {
            $activeCount++;
            if (NamecomCtrl::isExpiringSoon($d['expireDate'] ?? $d['expires_at'] ?? null)) {
                $expiringCount++;
            }
        } else {
            $expiredCount++;
        }
    }
    $currentSort = $sort ?? 'expires';
    $currentDir = $sortDir ?? 'asc';
    $nextDir = ($currentSort === 'expires' && $currentDir === 'asc') ? 'desc' : 'asc';
    $sortParams = ['sort' => 'expires', 'dir' => $nextDir];
    if (! empty($q)) {
        $sortParams['q'] = $q;
    }
    $refreshParams = ['refresh' => 1, 'sort' => $currentSort, 'dir' => $currentDir];
    if (! empty($q)) {
        $refreshParams['q'] = $q;
    }
@endphp
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="domain-page-hero">
            <div class="d-md-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <nav class="domain-page-hero__breadcrumb mb-2">
                        <a href="{{ route('admin.dashboard') }}">لوحة التحكم</a>
                        <span class="text-muted mx-1">/</span>
                        <span>نطاقات name.com</span>
                    </nav>
                    <h1 class="domain-page-hero__title">نطاقات name.com</h1>
                    <p class="text-muted small mb-0">كل النطاقات المسجّلة في حساب name.com — من API مباشرة.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.namecom.domains.index', $refreshParams) }}" class="btn btn-light btn-sm">
                        <i class="fe fe-refresh-cw me-1"></i> تحديث
                    </a>
                    <a href="{{ route('admin.namecom.settings.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fe fe-settings me-1"></i> الإعدادات
                    </a>
                </div>
            </div>
        </div>

        @if(session('error'))
        <div class="alert alert-danger py-2">{{ session('error') }}</div>
        @endif
        @if(!empty($error))
        <div class="alert alert-danger py-2">{{ $error }}</div>
        @endif

        <div class="domain-kpi-grid">
            <div class="domain-kpi domain-kpi--purple">
                <span class="domain-kpi__icon"><i class="fe fe-globe"></i></span>
                <div>
                    <div class="domain-kpi__label">إجمالي النطاقات</div>
                    <div class="domain-kpi__value">{{ $total }}</div>
                    @if(!empty($q))
                    <div class="domain-kpi__sub">نتائج البحث</div>
                    @endif
                </div>
            </div>
            <div class="domain-kpi domain-kpi--success">
                <span class="domain-kpi__icon"><i class="fe fe-check-circle"></i></span>
                <div>
                    <div class="domain-kpi__label">نطاقات فعّالة</div>
                    <div class="domain-kpi__value">{{ $activeCount }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--warning">
                <span class="domain-kpi__icon"><i class="fe fe-alert-triangle"></i></span>
                <div>
                    <div class="domain-kpi__label">تنتهي خلال 30 يوم</div>
                    <div class="domain-kpi__value">{{ $expiringCount }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--pink">
                <span class="domain-kpi__icon"><i class="fe fe-x-circle"></i></span>
                <div>
                    <div class="domain-kpi__label">منتهية</div>
                    <div class="domain-kpi__value">{{ $expiredCount }}</div>
                </div>
            </div>
        </div>

        <div class="domain-panel domain-search-panel mb-3">
            <div class="domain-panel__head">
                <span class="domain-panel__head-icon"><i class="fe fe-search"></i></span>
                <h2 class="domain-panel__title">بحث وتصفية</h2>
            </div>
            <div class="domain-panel__body">
                <form method="GET" class="domain-search-form">
                    <input type="hidden" name="sort" value="{{ $currentSort }}">
                    <input type="hidden" name="dir" value="{{ $currentDir }}">
                    <div class="domain-search-form__field">
                        <label class="domain-search-form__label" for="domain-q">بحث بالنطاق</label>
                        <input type="text" id="domain-q" name="q" class="form-control form-control-sm domain-search-form__input" value="{{ $q ?? '' }}" placeholder="example.com" dir="ltr">
                    </div>
                    <div class="domain-search-form__actions">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fe fe-search me-1"></i> بحث
                        </button>
                        <a href="{{ route('admin.namecom.domains.index') }}" class="btn btn-light btn-sm">إعادة</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="domain-dns-panel">
            <div class="domain-dns-panel__head">
                <h2 class="domain-dns-panel__title">
                    <i class="fe fe-list text-primary"></i> قائمة النطاقات
                </h2>
                <span class="domain-dns-count">{{ $total }} نطاق</span>
            </div>
            <div class="table-responsive">
                <table class="domain-dns-table domain-list-table">
                    <thead>
                        <tr>
                            <th class="domain-list-table__domain">النطاق</th>
                            <th>تاريخ التسجيل</th>
                            <th>
                                <a href="{{ route('admin.namecom.domains.index', $sortParams) }}" class="domain-sort-link {{ $currentSort === 'expires' ? 'is-active' : '' }}">
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
                            <th class="domain-list-table__action"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($domains as $d)
                        @php
                            $name = NamecomCtrl::domainName($d);
                            $expiresRaw = $d['expireDate'] ?? $d['expires_at'] ?? null;
                            $status = NamecomCtrl::formatStatus($d);
                            $expiringSoon = NamecomCtrl::isExpiringSoon($expiresRaw);
                            $statusClass = match(true) {
                                !($status['is_active'] ?? true) => 'expired',
                                $expiringSoon => 'warning',
                                default => 'active',
                            };
                        @endphp
                        <tr class="{{ $expiringSoon ? 'domain-list-table__row--warning' : '' }}">
                            <td class="domain-list-table__domain">
                                <a href="{{ route('admin.namecom.domains.show', ['domain' => $name]) }}" class="domain-name-link">
                                    <span class="domain-name-link__icon"><i class="fe fe-globe"></i></span>
                                    <span class="domain-name-link__text" dir="ltr">{{ $name }}</span>
                                </a>
                            </td>
                            <td class="text-muted">{{ NamecomCtrl::formatDate($d['createDate'] ?? $d['registered_at'] ?? null) }}</td>
                            <td>
                                <span class="{{ $expiringSoon ? 'domain-expire-soon' : '' }}">{{ NamecomCtrl::formatDate($expiresRaw) }}</span>
                                @if($expiringSoon)
                                <span class="domain-mini-badge domain-mini-badge--warning">قريباً</span>
                                @endif
                            </td>
                            <td>
                                <span class="domain-mini-badge {{ !empty($d['autorenewEnabled']) ? 'domain-mini-badge--yes' : 'domain-mini-badge--no' }}">
                                    {{ NamecomCtrl::formatBool($d['autorenewEnabled'] ?? false) }}
                                </span>
                            </td>
                            <td>
                                <span class="domain-mini-badge {{ !empty($d['locked']) ? 'domain-mini-badge--yes' : 'domain-mini-badge--no' }}">
                                    {{ NamecomCtrl::formatBool($d['locked'] ?? false) }}
                                </span>
                            </td>
                            <td>
                                <span class="domain-mini-badge {{ !empty($d['privacyEnabled']) ? 'domain-mini-badge--yes' : 'domain-mini-badge--no' }}">
                                    {{ NamecomCtrl::formatBool($d['privacyEnabled'] ?? false) }}
                                </span>
                            </td>
                            <td>
                                <span class="domain-status-badge domain-status-badge--{{ $statusClass }}">{{ $status['label'] }}</span>
                            </td>
                            <td class="domain-list-table__action">
                                <a href="{{ route('admin.namecom.domains.show', ['domain' => $name]) }}" class="domain-action-btn">
                                    التفاصيل <i class="fe fe-arrow-left"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="domain-list-empty">
                                <i class="fe fe-inbox"></i>
                                <p>لا توجد نطاقات — تحقق من <a href="{{ route('admin.namecom.settings.index') }}">الإعدادات</a> واضغط تحديث.</p>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($total > 0)
            <div class="domain-list-footer">
                الترتيب:
                <strong>{{ ($sortDir ?? 'asc') === 'asc' ? 'الأقرب انتهاءً أولاً' : 'الأبعد انتهاءً أولاً' }}</strong>
                @if(!empty($q))
                · بحث: <code dir="ltr">{{ $q }}</code>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
