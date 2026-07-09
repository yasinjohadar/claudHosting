@extends('admin.layouts.master')

@section('page-title')
تعديل فاتورة {{ $invoice->invoice_number }}
@stop

@push('styles')
@include('admin.partials.domain-ui-styles')
@endpush

@section('content')
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
                        <span>{{ $invoice->invoice_number }}</span>
                    </nav>
                    <h1 class="domain-page-hero__title">تعديل الفاتورة</h1>
                    <p class="text-muted small mb-0" dir="ltr">{{ $invoice->invoice_number }}</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.invoices.show', $invoice->id) }}" class="btn btn-light btn-sm">
                        <i class="fe fe-eye me-1"></i> عرض
                    </a>
                    <a href="{{ route('admin.invoices.index') }}" class="btn btn-light btn-sm">
                        <i class="fe fe-arrow-right me-1"></i> القائمة
                    </a>
                </div>
            </div>
        </div>

        @if(session('error'))
        <div class="alert alert-danger py-2">{{ session('error') }}</div>
        @endif
        @if($errors->any())
        <div class="alert alert-danger py-2">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('admin.invoices.update', $invoice->id) }}" method="POST" id="invoiceForm">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-xl-8">
                    <div class="domain-panel">
                        <div class="domain-panel__head">
                            <span class="domain-panel__head-icon"><i class="fe fe-file-text"></i></span>
                            <h2 class="domain-panel__title">بيانات الفاتورة</h2>
                        </div>
                        <div class="domain-panel__body">
                            @include('admin.invoices.partials.invoice-fields', [
                                'customers' => $customers,
                                'invoice' => $invoice,
                                'selectedCustomerId' => $selectedCustomerId ?? null,
                            ])
                            @include('admin.invoices.partials.invoice-items', ['invoice' => $invoice])
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="domain-panel mb-3">
                        <div class="domain-panel__head">
                            <span class="domain-panel__head-icon"><i class="fe fe-info"></i></span>
                            <h2 class="domain-panel__title">معلومات الفاتورة</h2>
                        </div>
                        <div class="domain-panel__body">
                            <ul class="list-unstyled mb-0 small">
                                <li class="mb-2 d-flex justify-content-between gap-2">
                                    <span class="text-muted">الرقم</span>
                                    <strong dir="ltr">{{ $invoice->invoice_number }}</strong>
                                </li>
                                <li class="d-flex justify-content-between gap-2 align-items-center">
                                    <span class="text-muted">الحالة</span>
                                    @php
                                        $statusClass = match($invoice->status) {
                                            'Paid' => 'active',
                                            'Cancelled' => 'expired',
                                            'Unpaid' => 'warning',
                                            default => 'info',
                                        };
                                        $statusLabel = match($invoice->status) {
                                            'Paid' => 'مدفوعة',
                                            'Unpaid' => 'غير مدفوعة',
                                            'Cancelled' => 'ملغاة',
                                            default => $invoice->status,
                                        };
                                    @endphp
                                    <span class="domain-status-badge domain-status-badge--{{ $statusClass }}">{{ $statusLabel }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    @include('admin.invoices.partials.quick-add-sidebar')
                </div>
            </div>
            <div class="domain-form-actions">
                <a href="{{ route('admin.invoices.show', $invoice->id) }}" class="btn btn-light btn-sm px-4">إلغاء</a>
                <button type="submit" class="btn btn-primary btn-sm px-4">
                    <i class="fe fe-save me-1"></i> تحديث الفاتورة
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@include('admin.invoices.partials.form-scripts')
