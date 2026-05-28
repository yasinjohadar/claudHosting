@extends('admin.layouts.master')

@section('page-title')
دفعة #{{ $payment->id }}
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">دفعة #{{ $payment->id }}</h4>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.payments.index') }}">المدفوعات</a></li>
                        <li class="breadcrumb-item active">#{{ $payment->id }}</li>
                    </ol>
                </nav>
            </div>
            <div class="ms-auto d-flex flex-wrap gap-2">
                @if($payment->status === 'Pending')
                    <form action="{{ route('admin.payments.confirm', $payment) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success" onclick="return confirm('تأكيد هذه الدفعة؟');">
                            <i class="fe fe-check"></i> تأكيد الدفعة
                        </button>
                    </form>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                        <i class="fe fe-x"></i> رفض
                    </button>
                @endif
                @if($payment->invoice)
                    <a href="{{ route('admin.invoices.show', $payment->invoice_id) }}" class="btn btn-info-light">عرض الفاتورة</a>
                @endif
                <a href="{{ route('admin.payments.index') }}" class="btn btn-light">القائمة</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <div class="row g-4">
            <div class="col-xl-6">
                <div class="card custom-card">
                    <div class="card-header"><div class="card-title">تفاصيل الدفعة</div></div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2 d-flex justify-content-between"><span class="text-muted">المبلغ</span><strong>{{ number_format($payment->amount, 2) }} ر.س</strong></li>
                            <li class="mb-2 d-flex justify-content-between"><span class="text-muted">التاريخ</span><span>{{ $payment->date?->format('Y-m-d H:i') ?? '—' }}</span></li>
                            <li class="mb-2 d-flex justify-content-between"><span class="text-muted">طريقة الدفع</span><span>{{ $payment->payment_method_name }}</span></li>
                            <li class="mb-2 d-flex justify-content-between"><span class="text-muted">رقم المعاملة</span><span dir="ltr">{{ $payment->transid ?? '—' }}</span></li>
                            <li class="mb-2 d-flex justify-content-between align-items-center">
                                <span class="text-muted">الحالة</span>
                                <span class="badge bg-{{ $payment->status_color }}-transparent">{{ $payment->status_name }}</span>
                            </li>
                            <li class="mb-2 d-flex justify-content-between"><span class="text-muted">المصدر</span><span>{{ $payment->initiated_by_label }}</span></li>
                            <li class="mb-2 d-flex justify-content-between"><span class="text-muted">سجّلها</span><span>{{ $payment->recordedBy?->name ?? '—' }}</span></li>
                            @if($payment->proof_path)
                                <li class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted">إثبات الدفع</span>
                                    <a href="{{ route('admin.payments.proof', $payment) }}" class="btn btn-sm btn-outline-primary">تحميل الملف</a>
                                </li>
                            @endif
                        </ul>
                        @if($payment->notes)
                            <hr>
                            <p class="text-muted small mb-1">ملاحظات</p>
                            <p class="mb-0">{{ $payment->notes }}</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card custom-card">
                    <div class="card-header"><div class="card-title">العميل والفاتورة</div></div>
                    <div class="card-body">
                        @if($payment->customer)
                            <p><strong>العميل:</strong> {{ $payment->customer->fullname }}</p>
                            <p><strong>البريد:</strong> {{ $payment->customer->email }}</p>
                            <a href="{{ route('admin.customers.show', $payment->customer_id) }}" class="btn btn-sm btn-info-light">عرض العميل</a>
                        @else
                            <p class="text-muted">لا يوجد عميل مرتبط.</p>
                        @endif
                        @if($payment->invoice)
                            <hr>
                            <p><strong>الفاتورة:</strong> {{ $payment->invoice->invoice_number }}</p>
                            <p><strong>إجمالي الفاتورة:</strong> {{ number_format($payment->invoice->total, 2) }} {{ $payment->invoice->currency }}</p>
                            <p><strong>المتبقي:</strong> {{ number_format($payment->invoice->balance, 2) }} {{ $payment->invoice->currency }}</p>
                            <p><strong>حالة الفاتورة:</strong> {{ $payment->invoice->status_name }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($payment->status === 'Pending')
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.payments.reject', $payment) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">رفض الدفعة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">سبب الرفض (اختياري)</label>
                    <textarea name="reason" class="form-control" rows="3"></textarea>
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
