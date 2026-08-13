@extends('admin.layouts.master')
@section('page-title') حساب {{ $account->username }} @stop

@section('content')
<div class="main-content app-content whm-account-page">
<style>
.whm-account-page .whm-hero {
    background: linear-gradient(135deg, rgba(var(--primary-rgb, 132, 90, 223), 0.08) 0%, rgba(13, 110, 253, 0.05) 100%);
    border: 1px solid rgba(var(--primary-rgb, 132, 90, 223), 0.12);
    border-radius: 1rem;
    padding: 1.25rem 1.5rem;
}
.whm-account-page .whm-hero-domain {
    font-size: 1.35rem;
    font-weight: 700;
    word-break: break-all;
}
.whm-account-page .whm-meta-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.75rem;
    border-radius: 50rem;
    font-size: 0.8rem;
    background: rgba(255, 255, 255, 0.7);
    border: 1px solid rgba(0, 0, 0, 0.06);
}
.whm-account-page .whm-stat-tile {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.85rem 1rem;
    border-radius: 0.75rem;
    background: var(--custom-white, #fff);
    border: 1px solid rgba(0, 0, 0, 0.06);
    height: 100%;
}
.whm-account-page .whm-stat-icon {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.5rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.whm-account-page .whm-stat-label {
    display: block;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: var(--text-muted, #6c757d);
    margin-bottom: 0.15rem;
}
.whm-account-page .whm-stat-value {
    display: block;
    font-size: 0.9rem;
    font-weight: 600;
    line-height: 1.3;
}
.whm-account-page .whm-section {
    padding: 1.25rem 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}
.whm-account-page .whm-section:last-child {
    border-bottom: none;
    padding-bottom: 0;
}
.whm-account-page .whm-section:first-child {
    padding-top: 0;
}
.whm-account-page .whm-section-title {
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--text-muted, #6c757d);
    margin-bottom: 0.85rem;
}
.whm-account-page .whm-danger-zone {
    border: 1px solid rgba(var(--danger-rgb, 220, 53, 69), 0.25);
    border-radius: 0.75rem;
    background: rgba(var(--danger-rgb, 220, 53, 69), 0.04);
    padding: 1rem 1.25rem;
}
.whm-account-page .whm-stats-panel {
    padding: 0.25rem 0;
}
.whm-account-page > .container-fluid {
    padding-top: 1.5rem;
    padding-bottom: 1.5rem;
}
</style>
    <div class="container-fluid">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                @if(session('invoice_id'))
                    <a href="{{ route('admin.invoices.show', session('invoice_id')) }}" class="alert-link ms-1">عرض الفاتورة</a>
                @endif
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Hero --}}
        <div class="whm-hero mb-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <span class="badge bg-{{ $account->status === 'active' ? 'success' : ($account->status === 'terminated' ? 'secondary' : 'warning') }}-transparent">
                            {{ $account->status_label }}
                        </span>
                        @if($account->package)
                            <span class="whm-meta-chip"><i class="fe fe-package"></i>{{ $account->package }}</span>
                        @endif
                        <span class="whm-meta-chip" dir="ltr"><i class="fe fe-user"></i>{{ $account->username }}</span>
                    </div>
                    <h4 class="whm-hero-domain mb-1" id="whm-page-title" dir="ltr">
                        @if($url = $account->site_url)
                            <a href="{{ $url }}" target="_blank" rel="noopener" class="text-primary text-decoration-none">{{ $account->domain }}</a>
                        @else
                            {{ $account->domain }}
                        @endif
                    </h4>
                    <p class="text-muted small mb-2">
                        <span dir="ltr" id="whm-display-email">{{ $account->display_email ?: '—' }}</span>
                        <span class="mx-2">·</span>
                        انضم {{ $account->joined_at?->format('Y-m-d') ?? '—' }}
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        @if($account->subscription_ends_at)
                            <span class="whm-meta-chip">
                                <i class="fe fe-calendar"></i>
                                ينتهي {{ $account->subscription_ends_at->format('Y-m-d') }}
                                <span class="badge {{ $account->subscription_status_badge }} ms-1">{{ $account->subscription_status_label }}</span>
                            </span>
                            @if($account->subscription_days_remaining !== null && $account->subscription_days_remaining >= 0)
                                <span class="whm-meta-chip text-muted">{{ $account->subscription_days_remaining }} يوم متبقي</span>
                            @endif
                        @else
                            <span class="whm-meta-chip text-warning">لم يُضبط تاريخ نهاية الاشتراك</span>
                        @endif
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center flex-shrink-0">
                    <a href="{{ route('admin.whm.accounts.wordpress.index', $account) }}" class="btn btn-outline-primary btn-sm">
                        <i class="fab fa-wordpress me-1"></i>ووردبريس
                    </a>
                    @include('admin.whm.accounts.partials.cpanel-link', ['account' => $account, 'configured' => $configured ?? true])
                    <a href="{{ route('admin.whm.accounts.index') }}" class="btn btn-light btn-sm">
                        <i class="fe fe-arrow-right me-1"></i>رجوع
                    </a>
                </div>
            </div>
        </div>

        @include('admin.whm.partials.server-status-panel', [
            'account' => $account,
            'configured' => $configured,
            'serverStatus' => $serverStatus ?? null,
            'proxyUser' => $account->username,
            'showFullPageLink' => true,
            'cardId' => 'whm-account-server-status',
            'refreshBtnId' => 'whm-account-server-refresh',
        ])

        {{-- Stats row (account) --}}
        <div class="card custom-card mb-4">
            <div class="card-header py-2">
                <span class="card-title mb-0 small">موارد الحساب</span>
            </div>
            <div class="card-body py-3">
                @include('admin.whm.accounts.partials.account-summary', [
                    'account' => $account,
                    'summary' => $summary,
                    'summarySyncedAt' => $summarySyncedAt,
                    'configured' => $configured,
                    'sslBadge' => $sslBadge ?? null,
                ])
            </div>
        </div>

        <div class="row g-4">
            {{-- Main management column --}}
            <div class="col-xl-8">
                <div class="card custom-card h-100">
                    <div class="card-header border-bottom-0 pb-0">
                        <ul class="nav nav-tabs card-header-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#whm-tab-overview" type="button">نظرة عامة</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#whm-tab-credentials" type="button">البيانات والأمان</button>
                            </li>
                            @if(($configured ?? false) && $account->status !== 'terminated')
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#whm-tab-package" type="button">الباقة</button>
                            </li>
                            @endif
                        </ul>
                    </div>
                    <div class="card-body pt-3">
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="whm-tab-overview" role="tabpanel">
                                <div class="whm-section">
                                    <div class="whm-section-title">الاشتراك والفواتير</div>
                                    <dl class="row small mb-3">
                                        <dt class="col-sm-4">نهاية الاشتراك</dt>
                                        <dd class="col-sm-8">{{ $account->subscription_ends_at?->format('Y-m-d') ?? '—' }}</dd>
                                        <dt class="col-sm-4">آخر تجديد</dt>
                                        <dd class="col-sm-8">{{ $account->last_renewed_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                                        <dt class="col-sm-4">مبلغ الفاتورة الافتراضي</dt>
                                        <dd class="col-sm-8">{{ number_format($billing['renewal_amount'] ?? 0, 2) }} ر.س</dd>
                                    </dl>
                                    @if($account->status !== 'terminated')
                                    <form action="{{ route('admin.whm.accounts.renew', $account) }}" method="POST" class="row g-2 align-items-end"
                                        onsubmit="return confirm('تجديد الاشتراك لمدة {{ $billing['subscription_years'] ?? 1 }} سنة وإنشاء فاتورة؟');">
                                        @csrf
                                        <div class="col-md-4">
                                            <label class="form-label small">مبلغ الفاتورة (اختياري)</label>
                                            <input type="number" name="amount" class="form-control form-control-sm" min="0" step="0.01"
                                                placeholder="{{ $billing['renewal_amount'] ?? 0 }}">
                                        </div>
                                        <div class="col-md-auto">
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class="fe fe-refresh-cw me-1"></i>تجديد الاشتراك
                                            </button>
                                        </div>
                                    </form>
                                    @endif
                                    @if($account->invoices->isNotEmpty())
                                    <div class="mt-3">
                                        <div class="small text-muted mb-1">آخر الفواتير</div>
                                        <ul class="list-unstyled small mb-0">
                                            @foreach($account->invoices as $inv)
                                            <li class="mb-1">
                                                <a href="{{ route('admin.invoices.show', $inv) }}">{{ $inv->invoice_number }}</a>
                                                — {{ number_format($inv->total, 2) }} ر.س
                                                <span class="badge bg-{{ $inv->status === 'Paid' ? 'success' : 'warning' }}-transparent">{{ $inv->status_name }}</span>
                                            </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                    @endif
                                </div>
                                <div class="whm-section">
                                    <div class="whm-section-title">حالة الحساب</div>
                                    @include('admin.whm.accounts.partials.status-toggle', ['account' => $account])
                                    <p class="text-muted small mb-0 mt-2">يُحدَّث مباشرة في WHM عبر suspend / unsuspend.</p>
                                </div>
                                <div class="whm-section">
                                    <div class="whm-section-title">العميل المسؤول</div>
                                    <div class="whm-client-cell mb-2" data-account-id="{{ $account->id }}">
                                        @include('admin.whm.accounts.partials.client-cell', ['account' => $account])
                                    </div>
                                    @include('admin.whm.accounts.partials.client-assign', ['account' => $account, 'clientUsers' => $clientUsers])
                                </div>
                            </div>

                            <div class="tab-pane fade" id="whm-tab-credentials" role="tabpanel">
                                @include('admin.whm.accounts.partials.account-credentials-form', ['account' => $account, 'embedded' => true])
                            </div>

                            @if(($configured ?? false) && $account->status !== 'terminated')
                            <div class="tab-pane fade" id="whm-tab-package" role="tabpanel">
                                @include('admin.whm.accounts.partials.change-package-form', ['account' => $account, 'packages' => $packages, 'configured' => $configured, 'embedded' => true])
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="col-xl-4">
                <div class="card custom-card mb-4">
                    <div class="card-header">
                        <div class="card-title mb-0">ملخص سريع</div>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0 small">
                            <dt class="col-5 text-muted">المستخدم</dt>
                            <dd class="col-7 mb-2"><code dir="ltr" id="whm-page-username">{{ $account->username }}</code></dd>
                            <dt class="col-5 text-muted">النطاق</dt>
                            <dd class="col-7 mb-2" dir="ltr">{{ $account->domain }}</dd>
                            <dt class="col-5 text-muted">الباقة</dt>
                            <dd class="col-7 mb-2">{{ $account->package ?: '—' }}</dd>
                            <dt class="col-5 text-muted">البريد</dt>
                            <dd class="col-7 mb-0" dir="ltr">{{ $account->display_email ?: '—' }}</dd>
                        </dl>
                    </div>
                </div>

                @if(($configured ?? false) && $account->status !== 'terminated')
                <div class="card custom-card border-0 shadow-none">
                    <div class="card-body p-0">
                        @include('admin.whm.accounts.partials.terminate-form', ['account' => $account, 'configured' => $configured, 'embedded' => true])
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('admin.whm.accounts.partials.whm-toast')
@include('admin.whm.accounts.partials.status-toggle-script')
@include('admin.whm.accounts.partials.account-credentials-script')
@include('admin.whm.accounts.partials.client-assign-script')
@endpush
