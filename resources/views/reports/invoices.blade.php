@extends('admin.layouts.master')

@section('page-title', 'تقرير الفواتير')

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 text-gray-800">
                    <i class="fas fa-file-invoice"></i> تقرير الفواتير
                </h1>
                <a href="{{ route('admin.reports.export.invoices', request()->query()) }}" class="btn btn-success">
                    <i class="fas fa-download"></i> تصدير إلى Excel
                </a>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white">
            <h6 class="m-0 font-weight-bold">الفلاتر</h6>
        </div>
        <div class="card-body">
            <form method="GET" class="form-inline">
                <div class="form-group mr-2 mb-2">
                    <label class="mr-2">من التاريخ:</label>
                    <input
                        type="date"
                        name="date_from"
                        class="form-control"
                        value="{{ $filters['date_from'] ?? '' }}"
                    >
                </div>
                <div class="form-group mr-2 mb-2">
                    <label class="mr-2">إلى التاريخ:</label>
                    <input
                        type="date"
                        name="date_to"
                        class="form-control"
                        value="{{ $filters['date_to'] ?? '' }}"
                    >
                </div>
                <div class="form-group mr-2 mb-2">
                    <select name="status" class="form-control">
                        <option value="">-- اختر الحالة --</option>
                        <option value="Paid" {{ ($filters['status'] ?? '') === 'Paid' ? 'selected' : '' }}>مدفوعة</option>
                        <option value="Unpaid" {{ ($filters['status'] ?? '') === 'Unpaid' ? 'selected' : '' }}>غير مدفوعة</option>
                        <option value="Overdue" {{ ($filters['status'] ?? '') === 'Overdue' ? 'selected' : '' }}>متأخرة</option>
                    </select>
                </div>
                <div class="form-group mr-2 mb-2">
                    <select name="payment_method" class="form-control">
                        <option value="">-- طريقة الدفع --</option>
                        @foreach($paymentMethods as $method)
                            <option value="{{ $method }}" {{ ($filters['payment_method'] ?? '') === $method ? 'selected' : '' }}>
                                {{ $method ?: 'بدون' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mb-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> بحث
                    </button>
                    <a href="{{ route('admin.reports.invoices') }}" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> إعادة تعيين
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Invoices Table -->
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>معرف WHMCS</th>
                            <th>رقم الفاتورة</th>
                            <th>العميل</th>
                            <th>التاريخ</th>
                            <th>الإجمالي</th>
                            <th>الحالة</th>
                            <th>طريقة الدفع</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            <tr>
                                <td>{{ $invoice->whmcs_id }}</td>
                                <td>{{ $invoice->invoice_number }}</td>
                                <td>{{ $invoice->customer->fullname ?? 'N/A' }}</td>
                                <td>{{ $invoice->date?->format('Y-m-d') ?? '-' }}</td>
                                <td class="text-right">${{ number_format($invoice->total, 2) }}</td>
                                <td>
                                    <span class="badge badge-{{ $invoice->status === 'Paid' ? 'success' : ($invoice->status === 'Overdue' ? 'danger' : 'warning') }}">
                                        {{ $invoice->status }}
                                    </span>
                                </td>
                                <td>{{ $invoice->payment_method ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">لا توجد فواتير</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center">
                {{ $invoices->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
