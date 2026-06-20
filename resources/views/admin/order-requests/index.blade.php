@extends('admin.layouts.master')

@section('page-title')
طلبات الباقات
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
                        <span>طلبات الباقات</span>
                    </nav>
                    <h1 class="domain-page-hero__title">طلبات الباقات</h1>
                    <p class="text-muted small mb-0">طلبات الاشتراك في باقات الاستضافة — متابعة الحالة والتزويد.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.products.index') }}" class="btn btn-light btn-sm">
                        <i class="fe fe-package me-1"></i> المنتجات
                    </a>
                </div>
            </div>
        </div>

        @if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger py-2">{{ session('error') }}</div>@endif
        @if(session('info'))<div class="alert alert-info py-2">{{ session('info') }}</div>@endif

        <div class="domain-kpi-grid">
            <div class="domain-kpi domain-kpi--primary">
                <span class="domain-kpi__icon"><i class="fe fe-inbox"></i></span>
                <div>
                    <div class="domain-kpi__label">إجمالي الطلبات</div>
                    <div class="domain-kpi__value">{{ $stats['total'] ?? 0 }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--warning">
                <span class="domain-kpi__icon"><i class="fe fe-clock"></i></span>
                <div>
                    <div class="domain-kpi__label">قيد الانتظار</div>
                    <div class="domain-kpi__value">{{ $stats['pending'] ?? 0 }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--info">
                <span class="domain-kpi__icon"><i class="fe fe-phone"></i></span>
                <div>
                    <div class="domain-kpi__label">تم التواصل</div>
                    <div class="domain-kpi__value">{{ $stats['contacted'] ?? 0 }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--success">
                <span class="domain-kpi__icon"><i class="fe fe-check-circle"></i></span>
                <div>
                    <div class="domain-kpi__label">تم التنفيذ</div>
                    <div class="domain-kpi__value">{{ $stats['converted'] ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="domain-panel domain-search-panel mb-3">
            <div class="domain-panel__head">
                <span class="domain-panel__head-icon"><i class="fe fe-filter"></i></span>
                <h2 class="domain-panel__title">بحث وتصفية</h2>
            </div>
            <div class="domain-panel__body py-2">
                <form method="GET" id="order-requests-filter-form" class="domain-filter-row">
                    <div class="domain-filter-field domain-filter-field--search">
                        <label class="domain-filter-field__label" for="order-q">بحث</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="fe fe-search"></i></span>
                            <input type="search" id="order-q" name="q" class="form-control"
                                value="{{ request('q') }}" placeholder="اسم، بريد، هاتف" autocomplete="off">
                        </div>
                    </div>
                    <div class="domain-filter-field domain-filter-field--compact">
                        <label class="domain-filter-field__label">الحالة</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            @foreach(\App\Models\PackageOrderRequest::statuses() as $key => $label)
                            <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="domain-filter-field domain-filter-field--compact">
                        <label class="domain-filter-field__label">الفوترة</label>
                        <select name="billing_cycle" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            @foreach(\App\Models\PackageOrderRequest::billingCycles() as $key => $label)
                            <option value="{{ $key }}" @selected(request('billing_cycle') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="domain-filter-field domain-filter-field--actions">
                        <label class="domain-filter-field__label d-none d-xl-block">&nbsp;</label>
                        <div class="domain-filter-inline-actions">
                            <button type="submit" class="btn btn-primary btn-sm">تطبيق</button>
                            <button type="button" id="order-requests-filter-reset" class="btn btn-light btn-sm">مسح</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="domain-dns-panel customers-list-panel">
            <div class="domain-dns-panel__head">
                <h2 class="domain-dns-panel__title">
                    <i class="fe fe-list text-primary"></i> قائمة الطلبات
                </h2>
                <span class="domain-dns-count" id="order-requests-count">{{ $orderRequests->total() }} طلب</span>
            </div>
            <div id="order-requests-list-loading" class="customers-list-loading" aria-hidden="true">
                <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                <span>جاري التحميل…</span>
            </div>
            <div id="order-requests-list-body">
                @include('admin.order-requests.partials.list-results')
            </div>
        </div>
    </div>
</div>

@include('admin.order-requests.partials.ajax-filters-script')
@endsection
