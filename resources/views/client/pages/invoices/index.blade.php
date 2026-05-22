@extends('client.layouts.master')

@section('page-title')
الفواتير
@stop

@section('css')
@include('client.partials.portal-ui-styles')
@endsection

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="client-portal-hero d-md-flex align-items-center justify-content-between flex-wrap gap-3 my-4">
            <div>
                <h4 class="mb-1 fw-semibold">فواتيري</h4>
                <nav>
                    <ol class="breadcrumb mb-0 small">
                        <li class="breadcrumb-item"><a href="{{ route('client.dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item active" aria-current="page">الفواتير</li>
                    </ol>
                </nav>
            </div>
            @if($hasCustomerProfile && ($invoices instanceof \Illuminate\Pagination\LengthAwarePaginator ? $invoices->total() : $invoices->count()) > 0)
                <span class="client-stat-pill">
                    <i class="fe fe-file-text text-info"></i>
                    {{ $invoices instanceof \Illuminate\Pagination\LengthAwarePaginator ? $invoices->total() : $invoices->count() }} فاتورة
                </span>
            @endif
        </div>

        @if(!$hasCustomerProfile)
            <div class="alert alert-warning border-0 shadow-sm d-flex align-items-start gap-2">
                <i class="fe fe-alert-triangle fs-18 mt-1"></i>
                <div>
                    <strong>الحساب غير مربوط بعد</strong>
                    <p class="mb-0 small">تواصل مع الدعم لربط حسابك بملف العميل وعرض الفواتير.</p>
                </div>
            </div>
        @endif

        <div class="card custom-card client-service-panel border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 client-invoice-table">
                        <thead class="table-light">
                            <tr>
                                <th>رقم الفاتورة</th>
                                <th>التاريخ</th>
                                <th>الاستحقاق</th>
                                <th>المبلغ</th>
                                <th>الحالة</th>
                                <th class="text-center">إجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoices as $inv)
                                <tr>
                                    <td>
                                        <span class="fw-semibold">{{ $inv->invoice_number }}</span>
                                    </td>
                                    <td class="text-muted">{{ $inv->date?->format('Y-m-d') ?? '—' }}</td>
                                    <td class="text-muted">{{ $inv->duedate?->format('Y-m-d') ?? '—' }}</td>
                                    <td>
                                        <span class="fw-medium">{{ number_format($inv->total ?? 0, 2) }}</span>
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
                                    <td class="text-center">
                                        <a href="{{ route('client.invoices.show', $inv) }}" class="btn btn-sm btn-primary-light rounded-pill px-3">
                                            عرض
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="client-empty-state py-5">
                                            <i class="fe fe-file-text"></i>
                                            <p class="mb-0">
                                                @if($hasCustomerProfile)
                                                    لا توجد فواتير مسجّلة لحسابك.
                                                @else
                                                    لا تتوفر فواتير لعرضها.
                                                @endif
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($hasCustomerProfile && $invoices instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $invoices->hasPages())
                <div class="card-footer bg-transparent border-top">
                    {{ $invoices->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
