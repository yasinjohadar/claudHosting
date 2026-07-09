@extends('admin.layouts.master')

@section('page-title')
كتالوج الخدمات
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
                        <span>كتالوج الخدمات</span>
                    </nav>
                    <h1 class="domain-page-hero__title">كتالوج الخدمات</h1>
                    <p class="text-muted small mb-0">إدارة الخدمات المعروضة للعملاء — الأسعار، الأنواع، ومدة التنفيذ.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.offered-services.create') }}" class="btn btn-primary btn-sm">
                        <i class="fe fe-plus me-1"></i> إضافة خدمة
                    </a>
                    <a href="{{ route('admin.service-types.index') }}" class="btn btn-light btn-sm">
                        <i class="fe fe-tag me-1"></i> أنواع الخدمات
                    </a>
                    <a href="{{ route('admin.customer-services.index') }}" class="btn btn-outline-secondary btn-sm">
                        خدمات العملاء
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
                <span class="domain-kpi__icon"><i class="fe fe-layers"></i></span>
                <div>
                    <div class="domain-kpi__label">إجمالي الخدمات</div>
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
            <div class="domain-kpi domain-kpi--warning">
                <span class="domain-kpi__icon"><i class="fe fe-pause-circle"></i></span>
                <div>
                    <div class="domain-kpi__label">غير نشطة</div>
                    <div class="domain-kpi__value">{{ $stats['inactive'] ?? 0 }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--info">
                <span class="domain-kpi__icon"><i class="fe fe-tag"></i></span>
                <div>
                    <div class="domain-kpi__label">أنواع الخدمات</div>
                    <div class="domain-kpi__value">{{ $stats['types'] ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="domain-panel domain-search-panel mb-3">
            <div class="domain-panel__head">
                <span class="domain-panel__head-icon"><i class="fe fe-filter"></i></span>
                <h2 class="domain-panel__title">بحث وتصفية</h2>
            </div>
            <div class="domain-panel__body py-2">
                <form method="GET" id="offered-services-filter-form" class="domain-filter-row">
                    <div class="domain-filter-field domain-filter-field--search">
                        <label class="domain-filter-field__label" for="os-search">بحث</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="fe fe-search"></i></span>
                            <input type="search" id="os-search" name="search" class="form-control"
                                value="{{ request('search') }}" placeholder="اسم أو وصف" autocomplete="off">
                        </div>
                    </div>
                    <div class="domain-filter-field domain-filter-field--compact">
                        <label class="domain-filter-field__label">نوع الخدمة</label>
                        <select name="service_type_id" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            @foreach($serviceTypes as $type)
                            <option value="{{ $type->id }}" @selected(request('service_type_id') == $type->id)>{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="domain-filter-field domain-filter-field--compact">
                        <label class="domain-filter-field__label">الحالة</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            <option value="active" @selected(request('status') === 'active')>نشط</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>غير نشط</option>
                        </select>
                    </div>
                    <div class="domain-filter-field domain-filter-field--actions">
                        <label class="domain-filter-field__label d-none d-xl-block">&nbsp;</label>
                        <div class="domain-filter-inline-actions">
                            <button type="submit" class="btn btn-primary btn-sm">تطبيق</button>
                            <button type="button" id="offered-services-filter-reset" class="btn btn-light btn-sm">مسح</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="domain-dns-panel offered-services-list-panel">
            <div class="domain-dns-panel__head">
                <h2 class="domain-dns-panel__title">
                    <i class="fe fe-list text-primary"></i> قائمة الخدمات
                </h2>
                <span class="domain-dns-count" id="offered-services-count">{{ $services->total() }} خدمة</span>
            </div>
            <div id="offered-services-list-loading" class="users-list-loading" aria-hidden="true">
                <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                <span>جاري التحميل…</span>
            </div>
            <div id="offered-services-list-body">
                @include('admin.offered-services.partials.list-results')
            </div>
        </div>
    </div>
</div>

<form id="offered-services-delete-form" action="" method="POST" class="d-none">
    @csrf
    @method('DELETE')
</form>

@include('admin.offered-services.partials.ajax-filters-script')
@endsection
