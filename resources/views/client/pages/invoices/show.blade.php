@extends('client.layouts.master')

@section('page-title')
فاتورة {{ $invoice->invoice_number }}
@stop

@section('css')
@include('client.partials.portal-ui-styles')
@endsection

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="client-portal-hero d-md-flex align-items-center justify-content-between flex-wrap gap-3 my-4">
            <div>
                <h4 class="mb-1 fw-semibold">فاتورة {{ $invoice->invoice_number }}</h4>
                <nav>
                    <ol class="breadcrumb mb-0 small">
                        <li class="breadcrumb-item"><a href="{{ route('client.dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('client.invoices.index') }}">الفواتير</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $invoice->invoice_number }}</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if($invoice->balance > 0 && !in_array($invoice->status, ['Paid', 'Cancelled']))
                    <a href="{{ route('client.invoices.pay', $invoice) }}" class="btn btn-success btn-sm rounded-pill">
                        <i class="fe fe-credit-card me-1"></i>سداد الفاتورة
                    </a>
                @endif
                <a href="{{ route('client.invoices.index') }}" class="btn btn-light btn-sm rounded-pill">
                    <i class="fe fe-arrow-right me-1"></i>العودة للقائمة
                </a>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-4">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">ملخص الفاتورة</div>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2 d-flex justify-content-between">
                                <span class="text-muted">التاريخ</span>
                                <span>{{ $invoice->date?->format('Y-m-d') ?? '—' }}</span>
                            </li>
                            <li class="mb-2 d-flex justify-content-between">
                                <span class="text-muted">الاستحقاق</span>
                                <span>{{ $invoice->duedate?->format('Y-m-d') ?? '—' }}</span>
                            </li>
                            <li class="mb-2 d-flex justify-content-between">
                                <span class="text-muted">طريقة الدفع</span>
                                <span>{{ $invoice->payment_method_name ?? ($invoice->paymentmethod ?: '—') }}</span>
                            </li>
                            <li class="mb-2 d-flex justify-content-between align-items-center">
                                <span class="text-muted">الحالة</span>
                                @if($invoice->status === 'Paid')
                                    <span class="badge bg-success-transparent">مدفوعة</span>
                                @elseif($invoice->status === 'Unpaid')
                                    <span class="badge bg-danger-transparent">غير مدفوعة</span>
                                @else
                                    <span class="badge bg-warning-transparent">{{ $invoice->status_name }}</span>
                                @endif
                            </li>
                            <li class="mb-2 d-flex justify-content-between">
                                <span class="text-muted">الإجمالي</span>
                                <strong>{{ number_format($invoice->total ?? 0, 2) }} {{ $invoice->currency }}</strong>
                            </li>
                            <li class="d-flex justify-content-between">
                                <span class="text-muted">المتبقي</span>
                                <strong class="{{ $invoice->balance > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($invoice->balance, 2) }} {{ $invoice->currency }}
                                </strong>
                            </li>
                        </ul>
                    </div>
                </div>

                @if($invoice->notes)
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">ملاحظات</div>
                    </div>
                    <div class="card-body">
                        <p class="mb-0 small text-muted">{{ $invoice->notes }}</p>
                    </div>
                </div>
                @endif
            </div>

            <div class="col-xl-8">
                <div class="card custom-card mb-4">
                    <div class="card-header">
                        <div class="card-title">بنود الفاتورة</div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>الوصف</th>
                                        <th class="text-end" style="width:8rem">ضريبة</th>
                                        <th class="text-end" style="width:10rem">المبلغ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($invoice->items as $item)
                                        <tr>
                                            <td>{{ $item->description }}</td>
                                            <td class="text-end">{{ $item->taxed ? 'نعم' : 'لا' }}</td>
                                            <td class="text-end">{{ number_format($item->amount, 2) }} {{ $invoice->currency }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-3">لا توجد بنود.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="2" class="text-end fw-semibold">الإجمالي</td>
                                        <td class="text-end fw-bold">{{ number_format($invoice->total ?? 0, 2) }} {{ $invoice->currency }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">المدفوعات</div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>التاريخ</th>
                                        <th>المبلغ</th>
                                        <th>طريقة الدفع</th>
                                        <th>الحالة</th>
                                        <th>رقم المعاملة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($invoice->payments as $payment)
                                        <tr>
                                            <td>{{ $payment->date?->format('Y-m-d H:i') ?? '—' }}</td>
                                            <td>{{ number_format($payment->amount, 2) }} {{ $invoice->currency }}</td>
                                            <td>{{ $payment->payment_method_name ?? $payment->gateway }}</td>
                                            <td><span class="badge bg-{{ $payment->status_color }}-transparent">{{ $payment->status_name }}</span></td>
                                            <td dir="ltr" class="small">{{ $payment->transid ?? '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-3">لا توجد مدفوعات مسجّلة.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

