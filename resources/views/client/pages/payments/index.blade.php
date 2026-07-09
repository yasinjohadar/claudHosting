@extends('client.layouts.master')

@section('page-title')
المدفوعات
@stop

@section('content')
@php
    $paymentCount = $payments instanceof \Illuminate\Pagination\LengthAwarePaginator
        ? $payments->total()
        : collect($payments)->count();
@endphp
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="client-portal-hero d-md-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <nav class="client-portal-breadcrumb mb-2">
                    <a href="{{ route('client.dashboard') }}">الرئيسية</a>
                    <span class="text-muted mx-1">/</span>
                    <span>المدفوعات</span>
                </nav>
                <h4 class="mb-1">المدفوعات</h4>
                <p class="text-muted small mb-0">سجل الدفعات المرتبطة بحسابك.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if($paymentCount > 0)
                <span class="client-stat-pill text-primary">
                    <i class="fe fe-credit-card"></i>{{ $paymentCount }} عملية
                </span>
                @endif
                <a href="{{ route('client.invoices.index') }}" class="btn btn-light btn-sm rounded-pill">
                    <i class="fe fe-file-text me-1"></i>الفواتير
                </a>
            </div>
        </div>

        @if(session('success'))
        <div class="client-portal-alert client-portal-alert--success mb-3">
            <span class="client-portal-alert__icon"><i class="fe fe-check-circle"></i></span>
            <div>{{ session('success') }}</div>
        </div>
        @endif
        @if(session('error'))
        <div class="client-portal-alert client-portal-alert--danger mb-3">
            <span class="client-portal-alert__icon"><i class="fe fe-x-circle"></i></span>
            <div>{{ session('error') }}</div>
        </div>
        @endif

        <div class="client-services-shell">
            <div class="client-services-panel-head">
                <h2 class="client-services-panel-head__title">
                    <i class="fe fe-credit-card"></i> سجل المدفوعات
                </h2>
                <span class="client-services-panel-head__meta">{{ $paymentCount }} عملية</span>
            </div>

            @if(collect($payments)->isEmpty())
                @include('client.partials.services-empty', [
                    'icon' => 'fe-credit-card',
                    'message' => 'لا توجد مدفوعات مسجّلة.',
                ])
            @else
                <div class="table-responsive">
                    <table class="table client-services-table mb-0">
                        <thead>
                            <tr>
                                <th>التاريخ</th>
                                <th>الفاتورة</th>
                                <th>المبلغ</th>
                                <th>الطريقة</th>
                                <th>الحالة</th>
                                <th class="text-end">إجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payments as $payment)
                                <tr>
                                    <td class="text-muted">{{ $payment->date?->format('Y-m-d H:i') ?? '—' }}</td>
                                    <td>
                                        @if($payment->invoice)
                                            <a href="{{ route('client.invoices.show', $payment->invoice_id) }}" class="client-services-link">
                                                {{ $payment->invoice->invoice_number }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="fw-semibold">{{ number_format($payment->amount, 2) }} ر.س</td>
                                    <td>{{ $payment->payment_method_name }}</td>
                                    <td>
                                        <span class="badge bg-{{ $payment->status_color }}-transparent">{{ $payment->status_name }}</span>
                                    </td>
                                    <td class="text-end">
                                        @if($payment->invoice)
                                            <a href="{{ route('client.invoices.show', $payment->invoice_id) }}" class="btn btn-light btn-sm rounded-pill px-3">
                                                عرض الفاتورة
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if($payments instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $payments->hasPages())
                <div class="client-portal-pagination">
                    {{ $payments->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
