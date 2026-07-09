@extends('admin.layouts.master')

@section('page-title')
{{ $service->name }}
@stop

@push('styles')
@include('admin.partials.domain-ui-styles')
@endpush

@section('content')
@php
    $executionLabel = $service->execution_duration
        ?? ($service->execution_days ? $service->execution_days.' يوم' : '—');
@endphp
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="domain-page-hero">
            <div class="d-md-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <nav class="domain-page-hero__breadcrumb mb-2">
                        <a href="{{ route('admin.dashboard') }}">لوحة التحكم</a>
                        <span class="text-muted mx-1">/</span>
                        <a href="{{ route('admin.offered-services.index') }}">كتالوج الخدمات</a>
                        <span class="text-muted mx-1">/</span>
                        <span>{{ $service->name }}</span>
                    </nav>
                    <h1 class="domain-page-hero__title">{{ $service->name }}</h1>
                    <div class="d-flex flex-wrap align-items-center gap-2 mt-1">
                        <span class="domain-status-badge domain-status-badge--{{ $service->is_active ? 'active' : 'expired' }}">
                            {{ $service->is_active ? 'نشط' : 'غير نشط' }}
                        </span>
                        @if($service->serviceType)
                        <span class="domain-mini-badge domain-mini-badge--yes">{{ $service->serviceType->name }}</span>
                        @endif
                        <span class="text-muted small">#{{ $service->id }}</span>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.offered-services.edit', $service) }}" class="btn btn-primary btn-sm">
                        <i class="fe fe-edit-2 me-1"></i> تعديل
                    </a>
                    <a href="{{ route('admin.customer-services.create', ['offered_service_id' => $service->id]) }}" class="btn btn-light btn-sm">
                        <i class="fe fe-user-plus me-1"></i> تسجيل لعميل
                    </a>
                    <a href="{{ route('admin.offered-services.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fe fe-arrow-right me-1"></i> القائمة
                    </a>
                </div>
            </div>
        </div>

        <div class="domain-kpi-grid mb-4">
            <div class="domain-kpi domain-kpi--purple">
                <span class="domain-kpi__icon"><i class="fe fe-dollar-sign"></i></span>
                <div>
                    <div class="domain-kpi__label">السعر</div>
                    <div class="domain-kpi__value domain-kpi__value--sm" dir="ltr">{{ $service->formatted_price }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--info">
                <span class="domain-kpi__icon"><i class="fe fe-clock"></i></span>
                <div>
                    <div class="domain-kpi__label">مدة التنفيذ</div>
                    <div class="domain-kpi__value domain-kpi__value--sm">{{ $executionLabel }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--success">
                <span class="domain-kpi__icon"><i class="fe fe-briefcase"></i></span>
                <div>
                    <div class="domain-kpi__label">خدمات العملاء</div>
                    <div class="domain-kpi__value">{{ $service->customer_services_count ?? 0 }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--primary">
                <span class="domain-kpi__icon"><i class="fe fe-hash"></i></span>
                <div>
                    <div class="domain-kpi__label">ترتيب العرض</div>
                    <div class="domain-kpi__value">{{ $service->sort_order ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-8">
                <div class="domain-panel h-100">
                    <div class="domain-panel__head">
                        <span class="domain-panel__head-icon"><i class="fe fe-file-text"></i></span>
                        <h2 class="domain-panel__title">التفاصيل</h2>
                    </div>
                    <div class="domain-panel__body">
                        @if($service->description)
                        <div class="mb-4 text-muted">{!! nl2br(e($service->description)) !!}</div>
                        @endif
                        <div class="domain-info-row">
                            <span class="domain-info-row__label">السعر</span>
                            <span class="domain-info-row__value" dir="ltr">{{ $service->formatted_price }}</span>
                        </div>
                        <div class="domain-info-row">
                            <span class="domain-info-row__label">مدة التنفيذ</span>
                            <span class="domain-info-row__value">{{ $executionLabel }}</span>
                        </div>
                        @if($service->execution_days)
                        <div class="domain-info-row">
                            <span class="domain-info-row__label">أيام التنفيذ</span>
                            <span class="domain-info-row__value">{{ $service->execution_days }} يوم</span>
                        </div>
                        @endif
                        <div class="domain-info-row">
                            <span class="domain-info-row__label">Slug</span>
                            <span class="domain-info-row__value" dir="ltr"><code>{{ $service->slug }}</code></span>
                        </div>
                        <div class="domain-info-row">
                            <span class="domain-info-row__label">الحالة</span>
                            <span class="domain-info-row__value">
                                <span class="domain-status-badge domain-status-badge--{{ $service->is_active ? 'active' : 'expired' }}">
                                    {{ $service->is_active ? 'نشط' : 'غير نشط' }}
                                </span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="domain-panel h-100">
                    <div class="domain-panel__head">
                        <span class="domain-panel__head-icon"><i class="fe fe-info"></i></span>
                        <h2 class="domain-panel__title">معلومات النظام</h2>
                    </div>
                    <div class="domain-panel__body">
                        <div class="domain-info-row">
                            <span class="domain-info-row__label">المعرّف</span>
                            <span class="domain-info-row__value">#{{ $service->id }}</span>
                        </div>
                        <div class="domain-info-row">
                            <span class="domain-info-row__label">تاريخ الإنشاء</span>
                            <span class="domain-info-row__value" dir="ltr">{{ $service->created_at?->format('Y-m-d H:i') ?? '—' }}</span>
                        </div>
                        <div class="domain-info-row">
                            <span class="domain-info-row__label">آخر تحديث</span>
                            <span class="domain-info-row__value" dir="ltr">{{ $service->updated_at?->format('Y-m-d H:i') ?? '—' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
