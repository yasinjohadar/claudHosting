@extends('admin.layouts.master')

@section('page-title')
تفاصيل الفاتورة: {{ $invoice->invoice_number }}
@stop

@push('styles')
@include('admin.partials.domain-ui-styles')
@endpush

@section('content')
@php
    $currency = $invoice->currency;
    $paidAmount = max(0, (float) $invoice->total - (float) $invoice->balance);
    $statusClass = match ($invoice->status) {
        'Paid' => 'active',
        'Unpaid' => 'expired',
        'Cancelled' => 'info',
        'Refunded', 'Draft', 'Payment Pending' => 'warning',
        default => 'info',
    };
    $canPay = ! in_array($invoice->status, ['Paid', 'Cancelled'], true);
@endphp
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="domain-page-hero">
            <div class="d-md-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <nav class="domain-page-hero__breadcrumb mb-2">
                        <a href="{{ route('admin.dashboard') }}">لوحة التحكم</a>
                        <span class="text-muted mx-1">/</span>
                        <a href="{{ route('admin.invoices.index') }}">الفواتير</a>
                        <span class="text-muted mx-1">/</span>
                        <span dir="ltr">{{ $invoice->invoice_number }}</span>
                    </nav>
                    <h1 class="domain-page-hero__title">
                        فاتورة <span dir="ltr">{{ $invoice->invoice_number }}</span>
                    </h1>
                    <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                        <span class="domain-status-badge domain-status-badge--{{ $statusClass }}">
                            {{ $invoice->status_name }}
                        </span>
                        <span class="text-muted small">
                            {{ $invoice->date?->format('Y-m-d') ?: '—' }}
                            @if($invoice->duedate)
                                · استحقاق {{ $invoice->duedate->format('Y-m-d') }}
                            @endif
                        </span>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.invoices.index') }}" class="btn btn-light btn-sm">
                        <i class="fe fe-arrow-right me-1"></i> العودة
                    </a>
                    <a href="{{ route('admin.invoices.edit', $invoice->id) }}" class="btn btn-warning btn-sm">
                        <i class="fe fe-edit me-1"></i> تعديل
                    </a>
                    @if($canPay)
                        <form action="{{ route('admin.invoices.markPaid', $invoice->id) }}" method="POST" class="d-inline"
                            onsubmit="return confirm('هل أنت متأكد من تعليم هذه الفاتورة كمدفوعة؟')">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="fe fe-check me-1"></i> تعليم كمدفوعة
                            </button>
                        </form>
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
        @if(session('info'))
            <div class="alert alert-info py-2">{{ session('info') }}</div>
        @endif

        <div class="domain-kpi-grid domain-kpi-grid--3 mb-3">
            <div class="domain-kpi domain-kpi--primary">
                <span class="domain-kpi__icon"><i class="fe fe-file-text"></i></span>
                <div>
                    <div class="domain-kpi__label">الإجمالي</div>
                    <div class="domain-kpi__value" dir="ltr">{{ number_format((float) $invoice->total, 2) }} <small class="fs-6">{{ $currency }}</small></div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--success">
                <span class="domain-kpi__icon"><i class="fe fe-check-circle"></i></span>
                <div>
                    <div class="domain-kpi__label">المدفوع</div>
                    <div class="domain-kpi__value" dir="ltr">{{ number_format($paidAmount, 2) }} <small class="fs-6">{{ $currency }}</small></div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--warning">
                <span class="domain-kpi__icon"><i class="fe fe-alert-circle"></i></span>
                <div>
                    <div class="domain-kpi__label">المتبقي</div>
                    <div class="domain-kpi__value" dir="ltr">{{ number_format((float) $invoice->balance, 2) }} <small class="fs-6">{{ $currency }}</small></div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-lg-4">
                <div class="domain-panel h-100">
                    <div class="domain-panel__head">
                        <span class="domain-panel__head-icon"><i class="fe fe-info"></i></span>
                        <h2 class="domain-panel__title">معلومات الفاتورة</h2>
                    </div>
                    <div class="domain-panel__body p-0">
                        <div class="domain-info-row">
                            <div class="domain-info-row__label">الرقم</div>
                            <div class="domain-info-row__value" dir="ltr">{{ $invoice->invoice_number }}</div>
                        </div>
                        <div class="domain-info-row">
                            <div class="domain-info-row__label">التاريخ</div>
                            <div class="domain-info-row__value">{{ $invoice->date?->format('Y-m-d') ?: '—' }}</div>
                        </div>
                        <div class="domain-info-row">
                            <div class="domain-info-row__label">الاستحقاق</div>
                            <div class="domain-info-row__value">{{ $invoice->duedate?->format('Y-m-d') ?: '—' }}</div>
                        </div>
                        <div class="domain-info-row">
                            <div class="domain-info-row__label">العملة</div>
                            <div class="domain-info-row__value">{{ $currency }}</div>
                        </div>
                        <div class="domain-info-row">
                            <div class="domain-info-row__label">طريقة الدفع</div>
                            <div class="domain-info-row__value">{{ $invoice->payment_method_name ?: '—' }}</div>
                        </div>
                        <div class="domain-info-row">
                            <div class="domain-info-row__label">الحالة</div>
                            <div class="domain-info-row__value">
                                <span class="domain-status-badge domain-status-badge--{{ $statusClass }}">
                                    {{ $invoice->status_name }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="domain-panel h-100">
                    <div class="domain-panel__head domain-panel__head--split">
                        <div class="domain-panel__head-main">
                            <span class="domain-panel__head-icon"><i class="fe fe-user"></i></span>
                            <h2 class="domain-panel__title">معلومات العميل</h2>
                        </div>
                        @if($invoice->customer?->user_id)
                            <a href="{{ route('admin.customers.show', $invoice->customer->user_id) }}" class="btn btn-sm btn-primary-light">
                                <i class="fe fe-external-link me-1"></i> عرض العميل
                            </a>
                        @endif
                    </div>
                    <div class="domain-panel__body p-0">
                        @if($invoice->customer)
                            <div class="domain-info-row">
                                <div class="domain-info-row__label">الاسم</div>
                                <div class="domain-info-row__value">{{ $invoice->customer->full_name }}</div>
                            </div>
                            <div class="domain-info-row">
                                <div class="domain-info-row__label">البريد</div>
                                <div class="domain-info-row__value" dir="ltr">{{ $invoice->customer->email }}</div>
                            </div>
                            <div class="domain-info-row">
                                <div class="domain-info-row__label">الشركة</div>
                                <div class="domain-info-row__value">{{ $invoice->customer->companyname ?: '—' }}</div>
                            </div>
                            <div class="domain-info-row">
                                <div class="domain-info-row__label">الهاتف</div>
                                <div class="domain-info-row__value" dir="ltr">{{ $invoice->customer->phonenumber ?: '—' }}</div>
                            </div>
                        @else
                            <div class="p-4 text-center text-muted">لم يُربط عميل بهذه الفاتورة.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="domain-dns-panel">
            <div class="domain-dns-panel__head">
                <h2 class="domain-dns-panel__title">
                    <i class="fe fe-list text-primary"></i> بنود الفاتورة
                </h2>
                <span class="domain-dns-count">{{ $invoice->items->count() }} بند</span>
            </div>
            <div class="table-responsive">
                <table class="domain-dns-table domain-list-table invoice-items-table">
                    <thead>
                        <tr>
                            <th>الوصف</th>
                            <th>السعر</th>
                            <th>الضريبة</th>
                            <th>الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($invoice->items as $item)
                            <tr>
                                <td>{{ $item->description }}</td>
                                <td dir="ltr">{{ number_format((float) $item->amount, 2) }} {{ $currency }}</td>
                                <td>
                                    <span class="domain-mini-badge {{ $item->taxed ? 'domain-mini-badge--yes' : 'domain-mini-badge--no' }}">
                                        {{ $item->taxed ? 'نعم' : 'لا' }}
                                    </span>
                                </td>
                                <td dir="ltr"><strong>{{ number_format((float) $item->amount, 2) }} {{ $currency }}</strong></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">لا توجد بنود لهذه الفاتورة</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($invoice->items->isNotEmpty())
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end fw-bold">الإجمالي</td>
                                <td dir="ltr" class="fw-bold">{{ number_format((float) $invoice->total, 2) }} {{ $currency }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        <div class="domain-dns-panel">
            <div class="domain-dns-panel__head">
                <h2 class="domain-dns-panel__title">
                    <i class="fe fe-credit-card text-primary"></i> المدفوعات
                </h2>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="domain-dns-count">{{ $invoice->payments->count() }} دفعة</span>
                    @if($canPay)
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                            <i class="fe fe-plus me-1"></i> إضافة دفعة
                        </button>
                    @endif
                </div>
            </div>
            <div class="table-responsive">
                <table class="domain-dns-table domain-list-table">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>المبلغ</th>
                            <th>طريقة الدفع</th>
                            <th>الحالة</th>
                            <th>رقم المعاملة</th>
                            <th>ملاحظات</th>
                            <th class="domain-list-table__action text-center">إجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($invoice->payments as $payment)
                            <tr>
                                <td>{{ $payment->date?->format('Y-m-d') ?? $payment->date }}</td>
                                <td dir="ltr">{{ number_format((float) $payment->amount, 2) }} {{ $currency }}</td>
                                <td>{{ $payment->payment_method_name }}</td>
                                <td>
                                    <span class="badge bg-{{ $payment->status_color }}-transparent">{{ $payment->status_name }}</span>
                                </td>
                                <td dir="ltr">{{ $payment->transid ?: '—' }}</td>
                                <td>{{ $payment->notes ?: '—' }}</td>
                                <td class="domain-list-table__action text-center">
                                    <a href="{{ route('admin.payments.show', $payment) }}" class="domain-action-btn">
                                        <i class="fe fe-eye"></i> عرض
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">لا توجد مدفوعات لهذه الفاتورة</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="domain-panel mb-4">
            <div class="domain-panel__head">
                <span class="domain-panel__head-icon"><i class="fe fe-file-text"></i></span>
                <h2 class="domain-panel__title">ملاحظات الفاتورة</h2>
            </div>
            <div class="domain-panel__body">
                @if($invoice->notes)
                    <p class="mb-0" style="white-space: pre-wrap;">{{ $invoice->notes }}</p>
                @else
                    <p class="text-muted mb-0">لا توجد ملاحظات لهذه الفاتورة.</p>
                @endif
            </div>
        </div>
    </div>
</div>

@if($canPay)
<div class="modal fade" id="addPaymentModal" tabindex="-1" aria-labelledby="addPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('admin.invoices.addPayment', $invoice->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="addPaymentModalLabel">إضافة دفعة جديدة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="amount" class="form-label">المبلغ <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount"
                            value="{{ old('amount', $invoice->balance) }}" step="0.01" min="0" max="{{ $invoice->balance }}" required>
                        @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">المتبقي: {{ number_format((float) $invoice->balance, 2) }} {{ $currency }}</div>
                    </div>
                    <div class="mb-3">
                        <label for="paymentmethod" class="form-label">طريقة الدفع <span class="text-danger">*</span></label>
                        <select class="form-select @error('paymentmethod') is-invalid @enderror" id="paymentmethod" name="paymentmethod" required>
                            <option value="">-- اختر طريقة الدفع --</option>
                            <option value="paypal" @selected(old('paymentmethod') === 'paypal')>PayPal</option>
                            <option value="banktransfer" @selected(old('paymentmethod') === 'banktransfer')>تحويل بنكي</option>
                            <option value="creditcard" @selected(old('paymentmethod') === 'creditcard')>بطاقة ائتمان</option>
                            <option value="cash" @selected(old('paymentmethod') === 'cash')>نقدي</option>
                            <option value="other" @selected(old('paymentmethod') === 'other')>آخر</option>
                        </select>
                        @error('paymentmethod')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="transid" class="form-label">رقم المعاملة</label>
                        <input type="text" class="form-control @error('transid') is-invalid @enderror" id="transid" name="transid" value="{{ old('transid') }}" dir="ltr">
                        @error('transid')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-0">
                        <label for="notes" class="form-label">ملاحظات</label>
                        <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ الدفعة</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($errors->has('amount') || $errors->has('paymentmethod') || $errors->has('transid') || $errors->has('notes'))
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('addPaymentModal');
    if (modal && window.bootstrap) {
        new bootstrap.Modal(modal).show();
    }
});
</script>
@endpush
@endif
@endif
@endsection
