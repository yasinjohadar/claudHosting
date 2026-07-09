@extends('admin.layouts.master')

@section('page-title')
{{ $record->name }}
@stop

@push('styles')
@include('admin.partials.domain-ui-styles')
@endpush

@section('content')
@php
    $statusClass = match($record->status) {
        \App\Models\CustomerService::STATUS_ACTIVE => 'active',
        \App\Models\CustomerService::STATUS_COMPLETED => 'info',
        \App\Models\CustomerService::STATUS_CANCELLED => 'expired',
        \App\Models\CustomerService::STATUS_OVERDUE => 'expired',
        default => 'warning',
    };
@endphp
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="domain-page-hero">
            <div class="d-md-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <nav class="domain-page-hero__breadcrumb mb-2">
                        <a href="{{ route('admin.dashboard') }}">لوحة التحكم</a>
                        <span class="text-muted mx-1">/</span>
                        <a href="{{ route('admin.customer-services.index') }}">خدمات العملاء</a>
                        <span class="text-muted mx-1">/</span>
                        <span>{{ $record->name }}</span>
                    </nav>
                    <h1 class="domain-page-hero__title">{{ $record->name }}</h1>
                    <div class="d-flex flex-wrap align-items-center gap-2 mt-1">
                        <span class="domain-status-badge domain-status-badge--{{ $statusClass }}">
                            {{ $record->status_label }}
                        </span>
                        @if($record->offeredService?->serviceType)
                        <span class="domain-mini-badge domain-mini-badge--yes">{{ $record->offeredService->serviceType->name }}</span>
                        @endif
                        <span class="text-muted small">#{{ $record->id }}</span>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @if(!$record->invoice_id)
                    <form action="{{ route('admin.customer-services.create-invoice', $record) }}" method="POST" class="d-inline"
                        onsubmit="return confirm('إنشاء فاتورة لهذه الخدمة؟');">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="fe fe-file-text me-1"></i> إنشاء فاتورة
                        </button>
                    </form>
                    @else
                    <a href="{{ route('admin.invoices.show', $record->invoice_id) }}" class="btn btn-success btn-sm">
                        <i class="fe fe-file-text me-1"></i> عرض الفاتورة
                    </a>
                    @endif
                    <a href="{{ route('admin.customer-services.edit', $record) }}" class="btn btn-primary btn-sm">
                        <i class="fe fe-edit-2 me-1"></i> تعديل
                    </a>
                    <a href="{{ route('admin.customer-services.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fe fe-arrow-right me-1"></i> القائمة
                    </a>
                </div>
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
        @endif
        @if(session('info'))
        <div class="alert alert-info py-2">{{ session('info') }}</div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger py-2">{{ session('error') }}</div>
        @endif

        <div class="domain-kpi-grid mb-4">
            <div class="domain-kpi domain-kpi--purple">
                <span class="domain-kpi__icon"><i class="fe fe-dollar-sign"></i></span>
                <div>
                    <div class="domain-kpi__label">السعر</div>
                    <div class="domain-kpi__value domain-kpi__value--sm" dir="ltr">{{ $record->formatted_price }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--warning">
                <span class="domain-kpi__icon"><i class="fe fe-alert-circle"></i></span>
                <div>
                    <div class="domain-kpi__label">المبلغ المستحق</div>
                    <div class="domain-kpi__value domain-kpi__value--sm" dir="ltr">{{ number_format((float) $record->amount_due, 2) }} {{ $record->currency === 'SAR' ? 'ر.س' : $record->currency }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--info">
                <span class="domain-kpi__icon"><i class="fe fe-calendar"></i></span>
                <div>
                    <div class="domain-kpi__label">تاريخ الاشتراك</div>
                    <div class="domain-kpi__value domain-kpi__value--sm" dir="ltr">{{ $record->subscribed_at?->format('Y-m-d') ?? '—' }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--success">
                <span class="domain-kpi__icon"><i class="fe fe-refresh-cw"></i></span>
                <div>
                    <div class="domain-kpi__label">تاريخ التجديد</div>
                    <div class="domain-kpi__value domain-kpi__value--sm" dir="ltr">{{ $record->renewal_at?->format('Y-m-d') ?? '—' }}</div>
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
                        <div class="domain-info-row">
                            <span class="domain-info-row__label">من الكتالوج</span>
                            <span class="domain-info-row__value">{{ $record->offeredService?->name ?? '—' }}</span>
                        </div>
                        <div class="domain-info-row">
                            <span class="domain-info-row__label">النوع</span>
                            <span class="domain-info-row__value">{{ $record->offeredService?->serviceType?->name ?? '—' }}</span>
                        </div>
                        <div class="domain-info-row">
                            <span class="domain-info-row__label">مدة التنفيذ</span>
                            <span class="domain-info-row__value">{{ $record->execution_duration ?? ($record->execution_days ? $record->execution_days.' يوم' : '—') }}</span>
                        </div>
                        <div class="domain-info-row">
                            <span class="domain-info-row__label">الحالة</span>
                            <span class="domain-info-row__value">
                                <span class="domain-status-badge domain-status-badge--{{ $statusClass }}">{{ $record->status_label }}</span>
                            </span>
                        </div>
                        @if($record->notes)
                        <div class="domain-info-row">
                            <span class="domain-info-row__label">ملاحظات</span>
                            <span class="domain-info-row__value">{!! nl2br(e($record->notes)) !!}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="domain-panel h-100">
                    <div class="domain-panel__head">
                        <span class="domain-panel__head-icon"><i class="fe fe-user"></i></span>
                        <h2 class="domain-panel__title">العميل</h2>
                    </div>
                    <div class="domain-panel__body">
                        <div class="domain-info-row">
                            <span class="domain-info-row__label">الاسم</span>
                            <span class="domain-info-row__value">{{ $record->customer?->fullname ?? '—' }}</span>
                        </div>
                        <div class="domain-info-row">
                            <span class="domain-info-row__label">البريد</span>
                            <span class="domain-info-row__value" dir="ltr">{{ $record->customer?->email ?? '—' }}</span>
                        </div>
                        @if($record->customer_id)
                        <a href="{{ route('admin.customers.show', $record->customer_id) }}" class="btn btn-primary btn-sm w-100 mt-3">
                            <i class="fe fe-user me-1"></i> ملف العميل
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
