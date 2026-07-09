@extends('admin.layouts.master')

@section('page-title')
الفواتير
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
                        <span>الفواتير</span>
                    </nav>
                    <h1 class="domain-page-hero__title">قائمة الفواتير</h1>
                    <p class="text-muted small mb-0">إدارة الفواتير والمدفوعات — البحث، التصفية، والإجراءات السريعة.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.invoices.create') }}" class="btn btn-primary btn-sm">
                        <i class="fe fe-plus me-1"></i> إنشاء فاتورة
                    </a>
                    <a href="{{ route('admin.payments.index') }}" class="btn btn-light btn-sm">
                        <i class="fe fe-credit-card me-1"></i> المدفوعات
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
            <div class="domain-kpi domain-kpi--primary">
                <span class="domain-kpi__icon"><i class="fe fe-file-text"></i></span>
                <div>
                    <div class="domain-kpi__label">إجمالي الفواتير</div>
                    <div class="domain-kpi__value">{{ $stats['total'] ?? 0 }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--success">
                <span class="domain-kpi__icon"><i class="fe fe-check-circle"></i></span>
                <div>
                    <div class="domain-kpi__label">مدفوعة</div>
                    <div class="domain-kpi__value">{{ $stats['paid'] ?? 0 }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--warning">
                <span class="domain-kpi__icon"><i class="fe fe-alert-circle"></i></span>
                <div>
                    <div class="domain-kpi__label">غير مدفوعة</div>
                    <div class="domain-kpi__value">{{ $stats['unpaid'] ?? 0 }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--pink">
                <span class="domain-kpi__icon"><i class="fe fe-x-circle"></i></span>
                <div>
                    <div class="domain-kpi__label">ملغاة</div>
                    <div class="domain-kpi__value">{{ $stats['cancelled'] ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="domain-panel domain-search-panel mb-3">
            <div class="domain-panel__head">
                <span class="domain-panel__head-icon"><i class="fe fe-filter"></i></span>
                <h2 class="domain-panel__title">بحث وتصفية</h2>
            </div>
            <div class="domain-panel__body py-2">
                <form method="GET" id="invoices-filter-form" class="domain-filter-row">
                    <div class="domain-filter-field domain-filter-field--search">
                        <label class="domain-filter-field__label" for="invoice-search">بحث</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="fe fe-search"></i></span>
                            <input type="search" id="invoice-search" name="search" class="form-control"
                                value="{{ request('search') }}" placeholder="رقم فاتورة، عميل، بريد" autocomplete="off">
                        </div>
                    </div>
                    <div class="domain-filter-field domain-filter-field--compact">
                        <label class="domain-filter-field__label">الحالة</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            <option value="Paid" @selected(request('status') === 'Paid')>مدفوعة</option>
                            <option value="Unpaid" @selected(request('status') === 'Unpaid')>غير مدفوعة</option>
                            <option value="Cancelled" @selected(request('status') === 'Cancelled')>ملغاة</option>
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
                            <button type="button" id="invoices-filter-reset" class="btn btn-light btn-sm">مسح</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="domain-dns-panel invoices-list-panel">
            <div class="domain-dns-panel__head">
                <h2 class="domain-dns-panel__title">
                    <i class="fe fe-list text-primary"></i> الفواتير
                </h2>
                <span class="domain-dns-count" id="invoices-count">{{ $invoices->total() }} فاتورة</span>
            </div>
            <div id="invoices-list-loading" class="users-list-loading" aria-hidden="true">
                <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                <span>جاري التحميل…</span>
            </div>
            <div id="invoices-list-body">
                @include('admin.invoices.partials.list-results')
            </div>
        </div>
    </div>
</div>

<form id="delete-form" action="" method="POST" class="d-none">
    @csrf
    @method('DELETE')
</form>
<form id="mark-paid-form" action="" method="POST" class="d-none">
    @csrf
</form>

@include('admin.invoices.partials.ajax-filters-script')
@endsection
