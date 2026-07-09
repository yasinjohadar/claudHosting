@extends('admin.layouts.master')

@section('page-title')
خدمات العملاء
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
                        <span>خدمات العملاء</span>
                    </nav>
                    <h1 class="domain-page-hero__title">خدمات العملاء</h1>
                    <p class="text-muted small mb-0">تسجيل ومتابعة الخدمات المقدّمة للعملاء — الاشتراك، التجديد، والفواتير.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.customer-services.create') }}" class="btn btn-primary btn-sm">
                        <i class="fe fe-plus me-1"></i> تسجيل خدمة لعميل
                    </a>
                    <a href="{{ route('admin.offered-services.index') }}" class="btn btn-light btn-sm">
                        <i class="fe fe-layers me-1"></i> كتالوج الخدمات
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
                <span class="domain-kpi__icon"><i class="fe fe-briefcase"></i></span>
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
                <span class="domain-kpi__icon"><i class="fe fe-clock"></i></span>
                <div>
                    <div class="domain-kpi__label">قيد الانتظار</div>
                    <div class="domain-kpi__value">{{ $stats['pending'] ?? 0 }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--pink">
                <span class="domain-kpi__icon"><i class="fe fe-alert-circle"></i></span>
                <div>
                    <div class="domain-kpi__label">متأخرة</div>
                    <div class="domain-kpi__value">{{ $stats['overdue'] ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="domain-panel domain-search-panel mb-3">
            <div class="domain-panel__head">
                <span class="domain-panel__head-icon"><i class="fe fe-filter"></i></span>
                <h2 class="domain-panel__title">بحث وتصفية</h2>
            </div>
            <div class="domain-panel__body py-2">
                <form method="GET" id="customer-services-filter-form" class="domain-filter-row">
                    <div class="domain-filter-field domain-filter-field--search">
                        <label class="domain-filter-field__label" for="cs-search">بحث</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="fe fe-search"></i></span>
                            <input type="search" id="cs-search" name="search" class="form-control"
                                value="{{ request('search') }}" placeholder="عميل، بريد، خدمة" autocomplete="off">
                        </div>
                    </div>
                    <div class="domain-filter-field domain-filter-field--compact">
                        <label class="domain-filter-field__label">العميل</label>
                        <select name="customer_id" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            @foreach($customers as $c)
                            <option value="{{ $c->id }}" @selected(request('customer_id') == $c->id)>{{ $c->fullname }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="domain-filter-field domain-filter-field--compact">
                        <label class="domain-filter-field__label">الخدمة</label>
                        <select name="offered_service_id" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            @foreach($catalogServices as $s)
                            <option value="{{ $s->id }}" @selected(request('offered_service_id') == $s->id)>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="domain-filter-field domain-filter-field--compact">
                        <label class="domain-filter-field__label">الحالة</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            @foreach(\App\Models\CustomerService::statusOptions() as $val => $label)
                            <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="domain-filter-field domain-filter-field--actions">
                        <label class="domain-filter-field__label d-none d-xl-block">&nbsp;</label>
                        <div class="domain-filter-inline-actions">
                            <button type="submit" class="btn btn-primary btn-sm">تطبيق</button>
                            <button type="button" id="customer-services-filter-reset" class="btn btn-light btn-sm">مسح</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="domain-dns-panel customer-services-list-panel">
            <div class="domain-dns-panel__head">
                <h2 class="domain-dns-panel__title">
                    <i class="fe fe-list text-primary"></i> سجل الخدمات
                </h2>
                <span class="domain-dns-count" id="customer-services-count">{{ $records->total() }} خدمة</span>
            </div>
            <div id="customer-services-list-loading" class="users-list-loading" aria-hidden="true">
                <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                <span>جاري التحميل…</span>
            </div>
            <div id="customer-services-list-body">
                @include('admin.customer-services.partials.list-results')
            </div>
        </div>
    </div>
</div>

@include('admin.customer-services.partials.ajax-filters-script')
@endsection
