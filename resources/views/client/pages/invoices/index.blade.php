@extends('client.layouts.master')

@section('page-title')
الفواتير
@stop

@section('content')
@php
    $invoiceList = $invoices instanceof \Illuminate\Pagination\LengthAwarePaginator
        ? $invoices->getCollection()
        : collect($invoices);
    $invoiceCount = $invoices instanceof \Illuminate\Pagination\LengthAwarePaginator
        ? $invoices->total()
        : $invoiceList->count();
    $unpaidCount = $invoiceList->where('status', 'Unpaid')->count();
    $paidCount = $invoiceList->where('status', 'Paid')->count();
@endphp
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="client-portal-hero d-md-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <nav class="client-portal-breadcrumb mb-2">
                    <a href="{{ route('client.dashboard') }}">الرئيسية</a>
                    <span class="text-muted mx-1">/</span>
                    <span>الفواتير</span>
                </nav>
                <h4 class="mb-1">فواتيري</h4>
                <p class="text-muted small mb-0">عرض الفواتير والسداد من مكان واحد.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if($hasCustomerProfile && $invoiceCount > 0)
                <span class="client-stat-pill text-primary">
                    <i class="fe fe-file-text"></i>{{ $invoiceCount }} فاتورة
                </span>
                <span class="client-stat-pill">
                    <i class="fe fe-check-circle text-success"></i>{{ $paidCount }} مدفوعة
                </span>
                <span class="client-stat-pill">
                    <i class="fe fe-alert-circle text-danger"></i>{{ $unpaidCount }} غير مدفوعة
                </span>
                @endif
            </div>
        </div>

        @if(!$hasCustomerProfile)
        <div class="client-portal-alert client-portal-alert--warn mb-3">
            <span class="client-portal-alert__icon"><i class="fe fe-alert-triangle"></i></span>
            <div>
                <strong>الحساب غير مربوط بعد</strong>
                <p class="mb-0 small">تواصل مع الدعم لربط حسابك بملف العميل وعرض الفواتير.</p>
            </div>
        </div>
        @endif

        <div class="client-services-shell">
            <div class="client-services-panel-head">
                <h2 class="client-services-panel-head__title">
                    <i class="fe fe-file-text"></i> قائمة الفواتير
                </h2>
                <span class="client-services-panel-head__meta">{{ $invoiceCount }} فاتورة</span>
            </div>

            @if($invoiceList->isEmpty())
                @include('client.partials.services-empty', [
                    'icon' => 'fe-file-text',
                    'message' => $hasCustomerProfile
                        ? 'لا توجد فواتير مسجّلة لحسابك.'
                        : 'لا تتوفر فواتير لعرضها.',
                ])
            @else
                <div class="table-responsive">
                    <table class="table client-services-table client-invoice-table mb-0">
                        <thead>
                            <tr>
                                <th>رقم الفاتورة</th>
                                <th>التاريخ</th>
                                <th>الاستحقاق</th>
                                <th>المبلغ</th>
                                <th>الحالة</th>
                                <th class="text-end">إجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoices as $inv)
                                <tr>
                                    <td>
                                        <span class="fw-semibold">{{ $inv->invoice_number }}</span>
                                    </td>
                                    <td class="text-muted">{{ $inv->date?->format('Y-m-d') ?? '—' }}</td>
                                    <td class="text-muted">{{ $inv->duedate?->format('Y-m-d') ?? '—' }}</td>
                                    <td>
                                        <span class="fw-semibold">{{ number_format($inv->total ?? 0, 2) }}</span>
                                        <span class="text-muted small">{{ $inv->currency }}</span>
                                    </td>
                                    <td>
                                        @if($inv->status === 'Paid')
                                            <span class="badge bg-success-transparent">مدفوعة</span>
                                        @elseif($inv->status === 'Unpaid')
                                            <span class="badge bg-danger-transparent">غير مدفوعة</span>
                                        @elseif($inv->status === 'Cancelled')
                                            <span class="badge bg-secondary-transparent">ملغاة</span>
                                        @else
                                            <span class="badge bg-warning-transparent">{{ $inv->status_name }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('client.invoices.show', $inv) }}" class="btn btn-primary btn-sm rounded-pill px-3">
                                            عرض
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if($hasCustomerProfile && $invoices instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $invoices->hasPages())
                <div class="client-portal-pagination">
                    {{ $invoices->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
