@extends('admin.layouts.master')

@section('page-title')
أنواع الخدمات
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
                        <span>أنواع الخدمات</span>
                    </nav>
                    <h1 class="domain-page-hero__title">أنواع الخدمات</h1>
                    <p class="text-muted small mb-0">تصنيف خدمات الكتالوج — ترتيب العرض والحالة.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.service-types.create') }}" class="btn btn-primary btn-sm">
                        <i class="fe fe-plus me-1"></i> إضافة نوع
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

        <div class="domain-kpi-grid domain-kpi-grid--3 mb-3">
            <div class="domain-kpi domain-kpi--primary">
                <span class="domain-kpi__icon"><i class="fe fe-tag"></i></span>
                <div>
                    <div class="domain-kpi__label">إجمالي الأنواع</div>
                    <div class="domain-kpi__value">{{ $stats['total'] ?? $serviceTypes->total() }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--success">
                <span class="domain-kpi__icon"><i class="fe fe-check-circle"></i></span>
                <div>
                    <div class="domain-kpi__label">نشطة</div>
                    <div class="domain-kpi__value">{{ $stats['active'] ?? 0 }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--info">
                <span class="domain-kpi__icon"><i class="fe fe-layers"></i></span>
                <div>
                    <div class="domain-kpi__label">مرتبطة بخدمات</div>
                    <div class="domain-kpi__value">{{ $stats['with_services'] ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="domain-dns-panel mb-4">
            <div class="domain-dns-panel__head">
                <h2 class="domain-dns-panel__title">
                    <i class="fe fe-list text-primary"></i> قائمة الأنواع
                </h2>
                <span class="domain-dns-count">{{ $serviceTypes->total() }} نوع</span>
            </div>
            <div class="table-responsive">
                <table class="domain-dns-table domain-list-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الاسم</th>
                            <th>Slug</th>
                            <th>عدد الخدمات</th>
                            <th>الترتيب</th>
                            <th>الحالة</th>
                            <th class="domain-list-table__action text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($serviceTypes as $type)
                            <tr>
                                <td>{{ $type->id }}</td>
                                <td><strong>{{ $type->name }}</strong></td>
                                <td dir="ltr" class="text-muted">{{ $type->slug ?: '—' }}</td>
                                <td>{{ $type->offered_services_count }}</td>
                                <td>{{ $type->sort_order }}</td>
                                <td>
                                    <span class="domain-status-badge domain-status-badge--{{ $type->is_active ? 'active' : 'info' }}">
                                        {{ $type->is_active ? 'نشط' : 'غير نشط' }}
                                    </span>
                                </td>
                                <td class="domain-list-table__action text-center">
                                    <div class="d-inline-flex gap-1 flex-wrap justify-content-center">
                                        <a href="{{ route('admin.service-types.edit', $type) }}" class="domain-action-btn" title="تعديل">
                                            <i class="fe fe-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.service-types.destroy', $type) }}" method="POST" class="d-inline"
                                            onsubmit="return confirm('حذف هذا النوع؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="domain-action-btn domain-action-btn--danger" title="حذف">
                                                <i class="fe fe-trash-2"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">لا توجد أنواع خدمات</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($serviceTypes->hasPages())
                <div class="p-3 border-top">{{ $serviceTypes->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
