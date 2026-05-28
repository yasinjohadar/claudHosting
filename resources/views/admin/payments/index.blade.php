@extends('admin.layouts.master')

@section('page-title')
المدفوعات
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">المدفوعات</h4>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item active">المدفوعات</li>
                    </ol>
                </nav>
            </div>
            <div class="ms-auto">
                <a href="{{ route('admin.payments.index', ['status' => 'Pending']) }}" class="btn btn-warning">
                    <i class="fe fe-clock"></i> مدفوعات معلّقة ({{ $stats['pending_count'] }})
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card custom-card">
                    <div class="card-body">
                        <p class="text-muted mb-1 small">إجمالي المدفوع (مكتمل)</p>
                        <h4 class="mb-0 text-success">{{ number_format($stats['total_completed'], 2) }} ر.س</h4>
                        <small class="text-muted">{{ $stats['completed_count'] }} عملية</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card custom-card">
                    <div class="card-body">
                        <p class="text-muted mb-1 small">معلّق للمراجعة</p>
                        <h4 class="mb-0 text-warning">{{ number_format($stats['pending_amount'], 2) }} ر.س</h4>
                        <small class="text-muted">{{ $stats['pending_count'] }} عملية</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card custom-card">
                    <div class="card-body">
                        <p class="text-muted mb-1 small">مدفوع هذا الشهر</p>
                        <h4 class="mb-0">{{ number_format($stats['month_completed'], 2) }} ر.س</h4>
                        <small class="text-muted">{{ $stats['month_count'] }} عملية</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card custom-card">
                    <div class="card-body">
                        <p class="text-muted mb-1 small">مدفوع اليوم</p>
                        <h4 class="mb-0">{{ number_format($stats['today_completed'], 2) }} ر.س</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label">الحالة</label>
                        <select name="status" class="form-select">
                            <option value="">الكل</option>
                            <option value="Completed" @selected(request('status') === 'Completed')>مكتمل</option>
                            <option value="Pending" @selected(request('status') === 'Pending')>قيد الانتظار</option>
                            <option value="Cancelled" @selected(request('status') === 'Cancelled')>ملغى</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">العميل</label>
                        <select name="customer_id" class="form-select">
                            <option value="">الكل</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" @selected(request('customer_id') == $c->id)>{{ $c->fullname }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">المصدر</label>
                        <select name="initiated_by" class="form-select">
                            <option value="">الكل</option>
                            <option value="client" @selected(request('initiated_by') === 'client')>العميل</option>
                            <option value="admin" @selected(request('initiated_by') === 'admin')>الإدارة</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">من تاريخ</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">إلى تاريخ</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">بحث</label>
                        <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="رقم معاملة / فاتورة">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">تصفية</button>
                        <a href="{{ route('admin.payments.index') }}" class="btn btn-light">إعادة ضبط</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header"><div class="card-title">قائمة المدفوعات</div></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>التاريخ</th>
                                <th>العميل</th>
                                <th>الفاتورة</th>
                                <th>المبلغ</th>
                                <th>الطريقة</th>
                                <th>المصدر</th>
                                <th>الحالة</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $payment)
                                <tr>
                                    <td>{{ $payment->id }}</td>
                                    <td>{{ $payment->date?->format('Y-m-d H:i') ?? '—' }}</td>
                                    <td>{{ $payment->customer?->fullname ?? '—' }}</td>
                                    <td>
                                        @if($payment->invoice)
                                            <a href="{{ route('admin.invoices.show', $payment->invoice_id) }}">{{ $payment->invoice->invoice_number }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ number_format($payment->amount, 2) }} ر.س</td>
                                    <td>{{ $payment->payment_method_name }}</td>
                                    <td>{{ $payment->initiated_by_label }}</td>
                                    <td><span class="badge bg-{{ $payment->status_color }}-transparent">{{ $payment->status_name }}</span></td>
                                    <td>
                                        <a href="{{ route('admin.payments.show', $payment) }}" class="btn btn-icon btn-sm btn-info-transparent rounded-pill"><i class="ri-eye-line"></i></a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center text-muted py-4">لا توجد مدفوعات.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($payments->hasPages())
                <div class="card-footer">{{ $payments->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
