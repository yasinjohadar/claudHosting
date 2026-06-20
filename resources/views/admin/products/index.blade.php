@extends('admin.layouts.master')

@section('page-title')
المنتجات
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
                        <span>المنتجات</span>
                    </nav>
                    <h1 class="domain-page-hero__title">قائمة المنتجات</h1>
                    <p class="text-muted small mb-0">باقات الاستضافة والأسعار — إدارة المنتجات والميزات.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm">
                        <i class="fe fe-plus me-1"></i> إضافة منتج
                    </a>
                    <a href="{{ route('admin.order-requests.index') }}" class="btn btn-light btn-sm">
                        <i class="fe fe-inbox me-1"></i> طلبات الباقات
                    </a>
                </div>
            </div>
        </div>

        @if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger py-2">{{ session('error') }}</div>@endif

        <div class="domain-kpi-grid">
            <div class="domain-kpi domain-kpi--primary">
                <span class="domain-kpi__icon"><i class="fe fe-package"></i></span>
                <div>
                    <div class="domain-kpi__label">إجمالي المنتجات</div>
                    <div class="domain-kpi__value">{{ $stats['total'] ?? 0 }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--success">
                <span class="domain-kpi__icon"><i class="fe fe-check-circle"></i></span>
                <div>
                    <div class="domain-kpi__label">نشطة</div>
                    <div class="domain-kpi__value">{{ $stats['active'] ?? 0 }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--pink">
                <span class="domain-kpi__icon"><i class="fe fe-x-circle"></i></span>
                <div>
                    <div class="domain-kpi__label">غير نشطة</div>
                    <div class="domain-kpi__value">{{ $stats['inactive'] ?? 0 }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--info">
                <span class="domain-kpi__icon"><i class="fe fe-server"></i></span>
                <div>
                    <div class="domain-kpi__label">استضافة</div>
                    <div class="domain-kpi__value">{{ $stats['hosting'] ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="domain-panel domain-search-panel mb-3">
            <div class="domain-panel__head">
                <span class="domain-panel__head-icon"><i class="fe fe-filter"></i></span>
                <h2 class="domain-panel__title">بحث وتصفية</h2>
            </div>
            <div class="domain-panel__body py-2">
                <form method="GET" id="products-filter-form" class="domain-filter-row">
                    <div class="domain-filter-field domain-filter-field--search">
                        <label class="domain-filter-field__label" for="product-q">بحث</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="fe fe-search"></i></span>
                            <input type="search" id="product-q" name="q" class="form-control"
                                value="{{ request('q') }}" placeholder="اسم، مجموعة" autocomplete="off">
                        </div>
                    </div>
                    <div class="domain-filter-field domain-filter-field--compact">
                        <label class="domain-filter-field__label">النوع</label>
                        <select name="type" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            @foreach(\App\Http\Controllers\Admin\ProductController::filterTypes() as $key => $label)
                            <option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="domain-filter-field domain-filter-field--compact">
                        <label class="domain-filter-field__label">الحالة</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            <option value="Active" @selected(request('status') === 'Active')>نشط</option>
                            <option value="Inactive" @selected(request('status') === 'Inactive')>غير نشط</option>
                        </select>
                    </div>
                    <div class="domain-filter-field domain-filter-field--actions">
                        <label class="domain-filter-field__label d-none d-xl-block">&nbsp;</label>
                        <div class="domain-filter-inline-actions">
                            <button type="submit" class="btn btn-primary btn-sm">تطبيق</button>
                            <button type="button" id="products-filter-reset" class="btn btn-light btn-sm">مسح</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="domain-dns-panel customers-list-panel">
            <div class="domain-dns-panel__head">
                <h2 class="domain-dns-panel__title">
                    <i class="fe fe-list text-primary"></i> المنتجات
                </h2>
                <span class="domain-dns-count" id="products-count">{{ $products->total() }} منتج</span>
            </div>
            <div id="products-list-loading" class="customers-list-loading" aria-hidden="true">
                <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                <span>جاري التحميل…</span>
            </div>
            <div id="products-list-body">
                @include('admin.products.partials.list-results')
            </div>
        </div>
    </div>
</div>

<form id="delete-form" action="" method="POST" class="d-none">
    @csrf
    @method('DELETE')
</form>
<form id="duplicate-form" action="" method="POST" class="d-none">
    @csrf
</form>

@include('admin.products.partials.ajax-filters-script')
@endsection
