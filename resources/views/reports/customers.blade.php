@extends('admin.layouts.master')

@section('page-title')
تقرير العملاء
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
                        <a href="{{ route('admin.reports.index') }}">التقارير</a>
                        <span class="text-muted mx-1">/</span>
                        <span>العملاء</span>
                    </nav>
                    <h1 class="domain-page-hero__title">تقرير العملاء</h1>
                    <p class="text-muted small mb-0">قائمة العملاء مع البحث والتصفية — تصدير إلى Excel عند الحاجة.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.reports.export.customers', request()->query()) }}" class="btn btn-success btn-sm">
                        <i class="fe fe-download me-1"></i> تصدير Excel
                    </a>
                    <a href="{{ route('admin.customers.index') }}" class="btn btn-light btn-sm">
                        <i class="fe fe-users me-1"></i> عملاء الاستضافة
                    </a>
                </div>
            </div>
        </div>

        <div class="domain-kpi-grid">
            <div class="domain-kpi domain-kpi--primary">
                <span class="domain-kpi__icon"><i class="fe fe-users"></i></span>
                <div>
                    <div class="domain-kpi__label">إجمالي العملاء</div>
                    <div class="domain-kpi__value">{{ $stats['total'] ?? 0 }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--success">
                <span class="domain-kpi__icon"><i class="fe fe-user-check"></i></span>
                <div>
                    <div class="domain-kpi__label">نشطون</div>
                    <div class="domain-kpi__value">{{ $stats['active'] ?? 0 }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--warning">
                <span class="domain-kpi__icon"><i class="fe fe-user-x"></i></span>
                <div>
                    <div class="domain-kpi__label">غير نشطين</div>
                    <div class="domain-kpi__value">{{ $stats['inactive'] ?? 0 }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--info">
                <span class="domain-kpi__icon"><i class="fe fe-globe"></i></span>
                <div>
                    <div class="domain-kpi__label">دول</div>
                    <div class="domain-kpi__value">{{ $stats['countries'] ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="domain-panel domain-search-panel mb-3">
            <div class="domain-panel__head">
                <span class="domain-panel__head-icon"><i class="fe fe-filter"></i></span>
                <h2 class="domain-panel__title">بحث وتصفية</h2>
            </div>
            <div class="domain-panel__body py-2">
                <form method="GET" id="customers-report-filter-form" class="domain-filter-row">
                    <div class="domain-filter-field domain-filter-field--search">
                        <label class="domain-filter-field__label" for="customer-report-search">بحث</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="fe fe-search"></i></span>
                            <input type="search" id="customer-report-search" name="search" class="form-control"
                                value="{{ $filters['search'] ?? '' }}" placeholder="اسم، بريد، شركة" autocomplete="off">
                        </div>
                    </div>
                    <div class="domain-filter-field domain-filter-field--compact">
                        <label class="domain-filter-field__label">الحالة</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            <option value="Active" @selected(($filters['status'] ?? '') === 'Active')>نشط</option>
                            <option value="Inactive" @selected(($filters['status'] ?? '') === 'Inactive')>غير نشط</option>
                            <option value="Closed" @selected(($filters['status'] ?? '') === 'Closed')>مغلق</option>
                        </select>
                    </div>
                    <div class="domain-filter-field domain-filter-field--compact">
                        <label class="domain-filter-field__label">الدولة</label>
                        <select name="country" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            @foreach($countries as $country)
                            <option value="{{ $country }}" @selected(($filters['country'] ?? '') === $country)>{{ $country }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="domain-filter-field domain-filter-field--actions">
                        <label class="domain-filter-field__label d-none d-xl-block">&nbsp;</label>
                        <div class="domain-filter-inline-actions">
                            <button type="submit" class="btn btn-primary btn-sm">تطبيق</button>
                            <button type="button" id="customers-report-filter-reset" class="btn btn-light btn-sm">مسح</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="domain-dns-panel customers-report-panel">
            <div class="domain-dns-panel__head">
                <h2 class="domain-dns-panel__title">
                    <i class="fe fe-list text-primary"></i> قائمة العملاء
                </h2>
                <span class="domain-dns-count" id="customers-report-count">{{ $customers->total() }} عميل</span>
            </div>
            <div id="customers-report-list-loading" class="users-list-loading" aria-hidden="true">
                <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                <span>جاري التحميل…</span>
            </div>
            <div id="customers-report-list-body">
                @include('reports.partials.customers-list-results')
            </div>
        </div>
    </div>
</div>

@include('reports.partials.customers-ajax-filters-script')
@endsection
