@extends('client.layouts.master')

@section('page-title')
المدفوعات
@stop

@section('css')
@include('client.partials.portal-ui-styles')
@endsection

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="client-portal-hero d-md-flex align-items-center justify-content-between flex-wrap gap-3 my-4">
            <div>
                <h4 class="mb-1 fw-semibold">المدفوعات</h4>
                <nav>
                    <ol class="breadcrumb mb-0 small">
                        <li class="breadcrumb-item"><a href="{{ route('client.dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item active">المدفوعات</li>
                    </ol>
                </nav>
            </div>
            <a href="{{ route('client.invoices.index') }}" class="btn btn-light btn-sm rounded-pill">
                <i class="fe fe-file-text me-1"></i>الفواتير
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <div class="card custom-card">
            <div class="card-header"><div class="card-title">سجل المدفوعات</div></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>التاريخ</th>
                                <th>الفاتورة</th>
                                <th>المبلغ</th>
                                <th>الطريقة</th>
                                <th>الحالة</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $payment)
                                <tr>
                                    <td>{{ $payment->date?->format('Y-m-d H:i') ?? '—' }}</td>
                                    <td>
                                        @if($payment->invoice)
                                            <a href="{{ route('client.invoices.show', $payment->invoice_id) }}">{{ $payment->invoice->invoice_number }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ number_format($payment->amount, 2) }} ر.س</td>
                                    <td>{{ $payment->payment_method_name }}</td>
                                    <td><span class="badge bg-{{ $payment->status_color }}-transparent">{{ $payment->status_name }}</span></td>
                                    <td>
                                        @if($payment->invoice)
                                            <a href="{{ route('client.invoices.show', $payment->invoice_id) }}" class="btn btn-sm btn-light">عرض الفاتورة</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">لا توجد مدفوعات مسجّلة.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($payments instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $payments->hasPages())
                <div class="card-footer">{{ $payments->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
