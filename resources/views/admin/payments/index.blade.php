@extends('admin.layouts.master')

@section('page-title')
المدفوعات
@stop

@push('styles')
@include('admin.partials.domain-ui-styles')
@endpush

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="domain-page-hero">
            <div class="d-md-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <nav class="domain-page-hero__breadcrumb mb-2">
                        <a href="{{ route('admin.dashboard') }}">لوحة التحكم</a>
                        <span class="text-muted mx-1">/</span>
                        <span>المدفوعات</span>
                    </nav>
                    <h1 class="domain-page-hero__title">المدفوعات</h1>
                    <p class="text-muted small mb-0">متابعة وتأكيد المدفوعات — البحث، التصفية، والمراجعة.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.payments.index', ['status' => 'Pending']) }}" class="btn btn-warning btn-sm">
                        <i class="fe fe-clock me-1"></i> معلّقة ({{ $stats['pending_count'] ?? 0 }})
                    </a>
                    <a href="{{ route('admin.invoices.index') }}" class="btn btn-light btn-sm">
                        <i class="fe fe-file-text me-1"></i> الفواتير
                    </a>
                </div>
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger py-2">{{ session('error') }}</div>
        @endif

        <div class="domain-kpi-grid">
            <div class="domain-kpi domain-kpi--success">
                <span class="domain-kpi__icon"><i class="fe fe-check-circle"></i></span>
                <div>
                    <div class="domain-kpi__label">إجمالي المدفوع (مكتمل)</div>
                    <div class="domain-kpi__value" dir="ltr">{{ number_format($stats['total_completed'] ?? 0, 2) }} ر.س</div>
                    <div class="domain-kpi__sub">{{ $stats['completed_count'] ?? 0 }} عملية</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--warning">
                <span class="domain-kpi__icon"><i class="fe fe-clock"></i></span>
                <div>
                    <div class="domain-kpi__label">معلّق للمراجعة</div>
                    <div class="domain-kpi__value" dir="ltr">{{ number_format($stats['pending_amount'] ?? 0, 2) }} ر.س</div>
                    <div class="domain-kpi__sub">{{ $stats['pending_count'] ?? 0 }} عملية</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--info">
                <span class="domain-kpi__icon"><i class="fe fe-calendar"></i></span>
                <div>
                    <div class="domain-kpi__label">مدفوع هذا الشهر</div>
                    <div class="domain-kpi__value" dir="ltr">{{ number_format($stats['month_completed'] ?? 0, 2) }} ر.س</div>
                    <div class="domain-kpi__sub">{{ $stats['month_count'] ?? 0 }} عملية</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--primary">
                <span class="domain-kpi__icon"><i class="fe fe-sun"></i></span>
                <div>
                    <div class="domain-kpi__label">مدفوع اليوم</div>
                    <div class="domain-kpi__value" dir="ltr">{{ number_format($stats['today_completed'] ?? 0, 2) }} ر.س</div>
                </div>
            </div>
        </div>

        <div class="domain-panel domain-search-panel mb-3">
            <div class="domain-panel__head">
                <span class="domain-panel__head-icon"><i class="fe fe-filter"></i></span>
                <h2 class="domain-panel__title">بحث وتصفية</h2>
            </div>
            <div class="domain-panel__body py-2">
                <form method="GET" id="payments-filter-form" class="domain-filter-row">
                    <div class="domain-filter-field domain-filter-field--search">
                        <label class="domain-filter-field__label" for="payment-search">بحث</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="fe fe-search"></i></span>
                            <input type="search" id="payment-search" name="search" class="form-control"
                                value="{{ request('search') }}" placeholder="عميل، بريد، معاملة، فاتورة" autocomplete="off">
                        </div>
                    </div>
                    <div class="domain-filter-field domain-filter-field--compact">
                        <label class="domain-filter-field__label">الحالة</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            <option value="Completed" @selected(request('status') === 'Completed')>مكتمل</option>
                            <option value="Pending" @selected(request('status') === 'Pending')>قيد الانتظار</option>
                            <option value="Cancelled" @selected(request('status') === 'Cancelled')>ملغى</option>
                            <option value="Failed" @selected(request('status') === 'Failed')>فشل</option>
                            <option value="Refunded" @selected(request('status') === 'Refunded')>مسترد</option>
                        </select>
                    </div>
                    <div class="domain-filter-field domain-filter-field--compact">
                        <label class="domain-filter-field__label">المصدر</label>
                        <select name="initiated_by" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            <option value="client" @selected(request('initiated_by') === 'client')>العميل</option>
                            <option value="admin" @selected(request('initiated_by') === 'admin')>الإدارة</option>
                        </select>
                    </div>
                    <div class="domain-filter-field domain-filter-field--compact">
                        <label class="domain-filter-field__label">من تاريخ</label>
                        <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                    </div>
                    <div class="domain-filter-field domain-filter-field--compact">
                        <label class="domain-filter-field__label">إلى تاريخ</label>
                        <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                    </div>
                    <div class="domain-filter-field domain-filter-field--actions">
                        <label class="domain-filter-field__label d-none d-xl-block">&nbsp;</label>
                        <div class="domain-filter-inline-actions">
                            <button type="submit" class="btn btn-primary btn-sm">تطبيق</button>
                            <button type="button" id="payments-filter-reset" class="btn btn-light btn-sm">مسح</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="domain-dns-panel payments-list-panel">
            <div class="domain-dns-panel__head">
                <h2 class="domain-dns-panel__title">
                    <i class="fe fe-credit-card text-primary"></i> قائمة المدفوعات
                </h2>
                <span class="domain-dns-count" id="payments-count">{{ $payments->total() }} عملية</span>
            </div>
            <div id="payments-list-loading" class="users-list-loading" aria-hidden="true">
                <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                <span>جاري التحميل…</span>
            </div>
            <div id="payments-list-body">
                @include('admin.payments.partials.list-results')
            </div>
        </div>
    </div>
</div>

@include('admin.payments.partials.ajax-filters-script')
@endsection
