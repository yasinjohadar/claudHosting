@extends('admin.layouts.master')

@section('page-title')
دفعة #{{ $payment->id }}
@stop

@push('styles')
@include('admin.partials.domain-ui-styles')
@endpush

@section('content')
@php
    $currency = $payment->invoice?->currency ?? 'ر.س';
    $statusClass = match ($payment->status) {
        'Completed' => 'active',
        'Pending' => 'warning',
        'Failed' => 'expired',
        'Refunded', 'Cancelled' => 'info',
        default => 'info',
    };
    $isPending = $payment->status === 'Pending';
    $invoiceStatusClass = match ($payment->invoice?->status) {
        'Paid' => 'active',
        'Unpaid' => 'expired',
        'Cancelled' => 'info',
        'Refunded', 'Draft', 'Payment Pending' => 'warning',
        default => 'info',
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
                        <a href="{{ route('admin.payments.index') }}">المدفوعات</a>
                        <span class="text-muted mx-1">/</span>
                        <span>#{{ $payment->id }}</span>
                    </nav>
                    <h1 class="domain-page-hero__title">دفعة #{{ $payment->id }}</h1>
                    <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                        <span class="domain-status-badge domain-status-badge--{{ $statusClass }}">
                            {{ $payment->status_name }}
                        </span>
                        <span class="text-muted small">
                            {{ $payment->date?->format('Y-m-d H:i') ?? '—' }}
                            · {{ $payment->payment_method_name }}
                        </span>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.payments.index') }}" class="btn btn-light btn-sm">
                        <i class="fe fe-arrow-right me-1"></i> العودة
                    </a>
                    @if($payment->invoice)
                        <a href="{{ route('admin.invoices.show', $payment->invoice_id) }}" class="btn btn-info-light btn-sm">
                            <i class="fe fe-file-text me-1"></i> عرض الفاتورة
                        </a>
                    @endif
                    @if($isPending)
                        <form action="{{ route('admin.payments.confirm', $payment) }}" method="POST" class="d-inline"
                            onsubmit="return confirm('تأكيد هذه الدفعة؟');">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="fe fe-check me-1"></i> تأكيد الدفعة
                            </button>
                        </form>
                        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="fe fe-x me-1"></i> رفض
                        </button>
                    @endif
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
                <span class="domain-kpi__icon"><i class="fe fe-dollar-sign"></i></span>
                <div>
                    <div class="domain-kpi__label">المبلغ</div>
                    <div class="domain-kpi__value" dir="ltr">{{ number_format((float) $payment->amount, 2) }} <small class="fs-6">{{ $currency }}</small></div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--info">
                <span class="domain-kpi__icon"><i class="fe fe-percent"></i></span>
                <div>
                    <div class="domain-kpi__label">الرسوم</div>
                    <div class="domain-kpi__value" dir="ltr">{{ number_format((float) ($payment->fees ?? 0), 2) }} <small class="fs-6">{{ $currency }}</small></div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--{{ $payment->invoice && (float) $payment->invoice->balance > 0 ? 'warning' : 'success' }}">
                <span class="domain-kpi__icon"><i class="fe fe-file-text"></i></span>
                <div>
                    <div class="domain-kpi__label">متبقي الفاتورة</div>
                    <div class="domain-kpi__value" dir="ltr">
                        @if($payment->invoice)
                            {{ number_format((float) $payment->invoice->balance, 2) }} <small class="fs-6">{{ $currency }}</small>
                        @else
                            —
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-lg-7">
                <div class="domain-panel h-100">
                    <div class="domain-panel__head">
                        <span class="domain-panel__head-icon"><i class="fe fe-credit-card"></i></span>
                        <h2 class="domain-panel__title">تفاصيل الدفعة</h2>
                    </div>
                    <div class="domain-panel__body p-0">
                        <div class="domain-info-row">
                            <div class="domain-info-row__label">المبلغ</div>
                            <div class="domain-info-row__value" dir="ltr"><strong>{{ number_format((float) $payment->amount, 2) }} {{ $currency }}</strong></div>
                        </div>
                        <div class="domain-info-row">
                            <div class="domain-info-row__label">التاريخ</div>
                            <div class="domain-info-row__value">{{ $payment->date?->format('Y-m-d H:i') ?? '—' }}</div>
                        </div>
                        <div class="domain-info-row">
                            <div class="domain-info-row__label">طريقة الدفع</div>
                            <div class="domain-info-row__value">{{ $payment->payment_method_name }}</div>
                        </div>
                        <div class="domain-info-row">
                            <div class="domain-info-row__label">رقم المعاملة</div>
                            <div class="domain-info-row__value" dir="ltr">{{ $payment->transid ?: '—' }}</div>
                        </div>
                        <div class="domain-info-row">
                            <div class="domain-info-row__label">الحالة</div>
                            <div class="domain-info-row__value">
                                <span class="domain-status-badge domain-status-badge--{{ $statusClass }}">
                                    {{ $payment->status_name }}
                                </span>
                            </div>
                        </div>
                        <div class="domain-info-row">
                            <div class="domain-info-row__label">المصدر</div>
                            <div class="domain-info-row__value">{{ $payment->initiated_by_label }}</div>
                        </div>
                        <div class="domain-info-row">
                            <div class="domain-info-row__label">سجّلها</div>
                            <div class="domain-info-row__value">{{ $payment->recordedBy?->name ?? '—' }}</div>
                        </div>
                        @if($payment->proof_path)
                            <div class="domain-info-row">
                                <div class="domain-info-row__label">إثبات الدفع</div>
                                <div class="domain-info-row__value">
                                    <a href="{{ route('admin.payments.proof', $payment) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fe fe-download me-1"></i> تحميل الملف
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="domain-panel h-100">
                    <div class="domain-panel__head domain-panel__head--split">
                        <div class="domain-panel__head-main">
                            <span class="domain-panel__head-icon"><i class="fe fe-user"></i></span>
                            <h2 class="domain-panel__title">العميل والفاتورة</h2>
                        </div>
                        @if($payment->customer?->user_id)
                            <a href="{{ route('admin.customers.show', $payment->customer->user_id) }}" class="btn btn-sm btn-primary-light">
                                <i class="fe fe-external-link me-1"></i> عرض العميل
                            </a>
                        @endif
                    </div>
                    <div class="domain-panel__body p-0">
                        @if($payment->customer)
                            <div class="domain-info-row">
                                <div class="domain-info-row__label">العميل</div>
                                <div class="domain-info-row__value">{{ $payment->customer->full_name }}</div>
                            </div>
                            <div class="domain-info-row">
                                <div class="domain-info-row__label">البريد</div>
                                <div class="domain-info-row__value" dir="ltr">{{ $payment->customer->email }}</div>
                            </div>
                        @else
                            <div class="p-3 text-center text-muted">لا يوجد عميل مرتبط.</div>
                        @endif

                        @if($payment->invoice)
                            <div class="domain-info-row">
                                <div class="domain-info-row__label">الفاتورة</div>
                                <div class="domain-info-row__value">
                                    <a href="{{ route('admin.invoices.show', $payment->invoice_id) }}" dir="ltr">
                                        {{ $payment->invoice->invoice_number }}
                                    </a>
                                </div>
                            </div>
                            <div class="domain-info-row">
                                <div class="domain-info-row__label">إجمالي الفاتورة</div>
                                <div class="domain-info-row__value" dir="ltr">{{ number_format((float) $payment->invoice->total, 2) }} {{ $currency }}</div>
                            </div>
                            <div class="domain-info-row">
                                <div class="domain-info-row__label">المتبقي</div>
                                <div class="domain-info-row__value" dir="ltr">{{ number_format((float) $payment->invoice->balance, 2) }} {{ $currency }}</div>
                            </div>
                            <div class="domain-info-row">
                                <div class="domain-info-row__label">حالة الفاتورة</div>
                                <div class="domain-info-row__value">
                                    <span class="domain-status-badge domain-status-badge--{{ $invoiceStatusClass }}">
                                        {{ $payment->invoice->status_name }}
                                    </span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="domain-panel mb-4">
            <div class="domain-panel__head">
                <span class="domain-panel__head-icon"><i class="fe fe-file-text"></i></span>
                <h2 class="domain-panel__title">ملاحظات</h2>
            </div>
            <div class="domain-panel__body">
                @if($payment->notes)
                    <p class="mb-0" style="white-space: pre-wrap;">{{ $payment->notes }}</p>
                @else
                    <p class="text-muted mb-0">لا توجد ملاحظات لهذه الدفعة.</p>
                @endif
            </div>
        </div>
    </div>
</div>

@if($isPending)
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('admin.payments.reject', $payment) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalLabel">رفض الدفعة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label" for="rejectReason">سبب الرفض (اختياري)</label>
                    <textarea name="reason" id="rejectReason" class="form-control" rows="3"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger">رفض الدفعة</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
