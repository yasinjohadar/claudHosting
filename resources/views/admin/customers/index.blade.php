@extends('admin.layouts.master')

@section('page-title')
عملاء الاستضافة
@stop

@push('styles')
@include('admin.partials.domain-ui-styles')
@endpush

@section('content')
@php
    $statusLabels = [
        'active' => 'فعال',
        'inactive' => 'غير نشط',
        'banned' => 'محظور',
    ];
@endphp
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="domain-page-hero">
            <div class="d-md-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <nav class="domain-page-hero__breadcrumb mb-2">
                        <a href="{{ route('admin.dashboard') }}">لوحة التحكم</a>
                        <span class="text-muted mx-1">/</span>
                        <span>عملاء الاستضافة</span>
                    </nav>
                    <h1 class="domain-page-hero__title">عملاء الاستضافة</h1>
                    <p class="text-muted small mb-0">مستخدمي النظام المسؤولون عن حسابات cPanel — الربط من <a href="{{ route('admin.whm.accounts.index') }}">حسابات WHM</a>.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
                        <i class="fe fe-user-plus me-1"></i> مستخدم جديد
                    </a>
                    <a href="{{ route('admin.whm.accounts.index') }}" class="btn btn-light btn-sm">
                        <i class="fe fe-server me-1"></i> حسابات WHM
                    </a>
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm">كل المستخدمين</a>
                </div>
            </div>
        </div>

        @if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger py-2">{{ session('error') }}</div>@endif

        <div class="domain-kpi-grid">
            <div class="domain-kpi domain-kpi--primary">
                <span class="domain-kpi__icon"><i class="fe fe-users"></i></span>
                <div>
                    <div class="domain-kpi__label">إجمالي العملاء</div>
                    <div class="domain-kpi__value">{{ $stats['total'] ?? 0 }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--success">
                <span class="domain-kpi__icon"><i class="fe fe-server"></i></span>
                <div>
                    <div class="domain-kpi__label">لديهم cPanel</div>
                    <div class="domain-kpi__value">{{ $stats['with_whm'] ?? 0 }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--info">
                <span class="domain-kpi__icon"><i class="fe fe-user"></i></span>
                <div>
                    <div class="domain-kpi__label">بدون استضافة</div>
                    <div class="domain-kpi__value">{{ $stats['without_whm'] ?? 0 }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--purple">
                <span class="domain-kpi__icon"><i class="fe fe-check-circle"></i></span>
                <div>
                    <div class="domain-kpi__label">نشطون</div>
                    <div class="domain-kpi__value">{{ $stats['active'] ?? 0 }}</div>
                </div>
            </div>
        </div>

        @if(!($configured ?? false))
        <div class="domain-connection-bar">
            <span class="domain-connection-badge domain-connection-badge--warn">WHM غير مضبوط — <a href="{{ route('admin.whm.settings.index') }}" class="text-decoration-none">الإعدادات</a></span>
        </div>
        @else
        <div class="domain-connection-bar">
            <span class="domain-connection-badge domain-connection-badge--ok"><i class="fe fe-check"></i> WHM متصل</span>
        </div>
        @endif

        <div class="domain-panel domain-search-panel mb-3">
            <div class="domain-panel__head">
                <span class="domain-panel__head-icon"><i class="fe fe-filter"></i></span>
                <h2 class="domain-panel__title">بحث وتصفية</h2>
            </div>
            <div class="domain-panel__body py-2">
                <form method="GET" id="customers-filter-form" class="domain-filter-row">
                    <div class="domain-filter-field domain-filter-field--search">
                        <label class="domain-filter-field__label" for="client-q">بحث</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="fe fe-search"></i></span>
                            <input type="search" id="client-q" name="q" class="form-control"
                                value="{{ request('q') }}" placeholder="اسم، بريد، هاتف" autocomplete="off">
                        </div>
                    </div>
                    <div class="domain-filter-field domain-filter-field--compact">
                        <label class="domain-filter-field__label">الاستضافة</label>
                        <select name="has_whm" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            <option value="1" @selected(request()->boolean('has_whm'))>cPanel</option>
                        </select>
                    </div>
                    <div class="domain-filter-field domain-filter-field--compact">
                        <label class="domain-filter-field__label">الحالة</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            @foreach($statusLabels as $key => $label)
                            <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="domain-filter-field domain-filter-field--compact">
                        <label class="domain-filter-field__label">التفعيل</label>
                        <select name="is_active" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            <option value="1" @selected(request('is_active') === '1')>مفعّل</option>
                            <option value="0" @selected(request('is_active') === '0')>غير مفعّل</option>
                        </select>
                    </div>
                    <div class="domain-filter-field domain-filter-field--compact">
                        <label class="domain-filter-field__label">ترتيب</label>
                        <select name="sort" class="form-select form-select-sm">
                            <option value="name" @selected(request('sort', 'name') === 'name')>الاسم</option>
                            <option value="created" @selected(request('sort') === 'created')>الإنشاء</option>
                            <option value="whm" @selected(request('sort') === 'whm')>cPanel</option>
                        </select>
                    </div>
                    <div class="domain-filter-field domain-filter-field--compact">
                        <label class="domain-filter-field__label">الاتجاه</label>
                        <select name="dir" class="form-select form-select-sm">
                            <option value="asc" @selected(request('dir', 'asc') === 'asc')>↑</option>
                            <option value="desc" @selected(request('dir') === 'desc')>↓</option>
                        </select>
                    </div>
                    <div class="domain-filter-field domain-filter-field--actions">
                        <label class="domain-filter-field__label d-none d-xl-block">&nbsp;</label>
                        <div class="domain-filter-inline-actions">
                            <button type="submit" class="btn btn-primary btn-sm">تطبيق</button>
                            <button type="button" id="customers-filter-reset" class="btn btn-light btn-sm">مسح</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="domain-dns-panel customers-list-panel">
            <div class="domain-dns-panel__head">
                <h2 class="domain-dns-panel__title">
                    <i class="fe fe-users text-primary"></i> قائمة العملاء
                </h2>
                <span class="domain-dns-count" id="customers-count">{{ $clients->total() }} عميل</span>
            </div>
            <div id="customers-list-loading" class="customers-list-loading" aria-hidden="true">
                <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                <span>جاري التحميل…</span>
            </div>
            <div id="customers-list-body">
                @include('admin.customers.partials.list-results')
            </div>
        </div>
    </div>
</div>

@include('admin.partials.impersonate-client-modal')
@include('admin.partials.customer-whatsapp-modal')
@include('admin.partials.customer-password-modal')
@include('admin.customers.partials.ajax-filters-script')
@endsection
