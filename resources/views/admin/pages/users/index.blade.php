@extends('admin.layouts.master')

@section('page-title')
قائمة المستخدمين
@stop

@push('styles')
@include('admin.partials.domain-ui-styles')
@endpush

@section('content')
@php
    $statusLabels = [
        'active' => 'مفعل',
        'inactive' => 'موقوف',
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
                        <span>المستخدمون</span>
                    </nav>
                    <h1 class="domain-page-hero__title">كافة المستخدمين</h1>
                    <p class="text-muted small mb-0">إدارة حسابات لوحة الإدارة والعملاء — الأدوار، التفعيل، وربط cPanel.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
                        <i class="fe fe-user-plus me-1"></i> إنشاء مستخدم جديد
                    </a>
                    <a href="{{ route('admin.customers.index') }}" class="btn btn-light btn-sm">
                        <i class="fe fe-users me-1"></i> عملاء الاستضافة
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
        @if($errors->any())
        <div class="alert alert-danger py-2">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="domain-kpi-grid">
            <div class="domain-kpi domain-kpi--primary">
                <span class="domain-kpi__icon"><i class="fe fe-users"></i></span>
                <div>
                    <div class="domain-kpi__label">إجمالي المستخدمين</div>
                    <div class="domain-kpi__value">{{ $stats['total'] ?? 0 }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--success">
                <span class="domain-kpi__icon"><i class="fe fe-check-circle"></i></span>
                <div>
                    <div class="domain-kpi__label">نشطون</div>
                    <div class="domain-kpi__value">{{ $stats['active'] ?? 0 }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--warning">
                <span class="domain-kpi__icon"><i class="fe fe-pause-circle"></i></span>
                <div>
                    <div class="domain-kpi__label">غير نشطين</div>
                    <div class="domain-kpi__value">{{ $stats['inactive'] ?? 0 }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--info">
                <span class="domain-kpi__icon"><i class="fe fe-server"></i></span>
                <div>
                    <div class="domain-kpi__label">لديهم cPanel</div>
                    <div class="domain-kpi__value">{{ $stats['with_whm'] ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="domain-panel domain-search-panel mb-3">
            <div class="domain-panel__head">
                <span class="domain-panel__head-icon"><i class="fe fe-filter"></i></span>
                <h2 class="domain-panel__title">بحث وتصفية</h2>
            </div>
            <div class="domain-panel__body py-2">
                <form action="{{ route('users.index') }}" method="GET" id="users-filter-form" class="domain-filter-row">
                    <div class="domain-filter-field domain-filter-field--search">
                        <label class="domain-filter-field__label" for="users-q">بحث</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="fe fe-search"></i></span>
                            <input type="search" id="users-q" name="query" class="form-control"
                                value="{{ request('query') }}" placeholder="اسم، بريد، هاتف" autocomplete="off">
                        </div>
                    </div>
                    <div class="domain-filter-field domain-filter-field--compact">
                        <label class="domain-filter-field__label">التفعيل</label>
                        <select name="is_active" class="form-select form-select-sm">
                            <option value="">كل الحالات النشطة</option>
                            <option value="1" @selected(request('is_active') === '1')>نشط</option>
                            <option value="0" @selected(request('is_active') === '0')>غير نشط</option>
                        </select>
                    </div>
                    <div class="domain-filter-field domain-filter-field--compact">
                        <label class="domain-filter-field__label">الحالة</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">كل الحالات</option>
                            @foreach($statusLabels as $key => $label)
                            <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="domain-filter-field domain-filter-field--actions">
                        <label class="domain-filter-field__label d-none d-xl-block">&nbsp;</label>
                        <div class="domain-filter-inline-actions">
                            <button type="submit" class="btn btn-primary btn-sm">بحث</button>
                            <button type="button" id="users-filter-reset" class="btn btn-light btn-sm">مسح</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="domain-dns-panel users-list-panel">
            <div class="domain-dns-panel__head">
                <h2 class="domain-dns-panel__title">
                    <i class="fe fe-user text-primary"></i> قائمة المستخدمين
                </h2>
                <span class="domain-dns-count" id="users-count">{{ $users->total() }} مستخدم</span>
            </div>
            <div id="users-list-loading" class="users-list-loading" aria-hidden="true">
                <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                <span>جاري التحميل…</span>
            </div>
            <div id="users-list-body">
                @include('admin.pages.users.partials.list-results')
            </div>
        </div>
    </div>
</div>

@include('admin.partials.impersonate-client-modal')
@include('admin.pages.users.partials.toggle-switches-script')
@include('admin.pages.users.partials.ajax-filters-script')
@endsection
