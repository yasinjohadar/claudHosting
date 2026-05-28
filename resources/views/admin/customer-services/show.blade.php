@extends('admin.layouts.master')

@section('page-title')
{{ $record->name }}
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">{{ $record->name }}</h4>
                <p class="mb-0 text-muted">{{ $record->customer?->fullname }}</p>
            </div>
            <div class="ms-auto d-flex flex-wrap gap-2">
                @if(!$record->invoice_id)
                <form action="{{ route('admin.customer-services.create-invoice', $record) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success" onclick="return confirm('إنشاء فاتورة لهذه الخدمة؟');">
                        <i class="fe fe-file-text"></i> إنشاء فاتورة
                    </button>
                </form>
                @else
                <a href="{{ route('admin.invoices.show', $record->invoice_id) }}" class="btn btn-success">عرض الفاتورة</a>
                @endif
                <a href="{{ route('admin.customer-services.edit', $record) }}" class="btn btn-warning">تعديل</a>
                <a href="{{ route('admin.customer-services.index') }}" class="btn btn-light">القائمة</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if(session('info'))
            <div class="alert alert-info alert-dismissible fade show">{{ session('info') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <div class="row g-4">
            <div class="col-xl-8">
                <div class="card custom-card">
                    <div class="card-header"><div class="card-title">التفاصيل</div></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6"><strong>من الكتالوج:</strong> {{ $record->offeredService?->name }}</div>
                            <div class="col-md-6"><strong>النوع:</strong> {{ $record->offeredService?->serviceType?->name ?? '—' }}</div>
                            <div class="col-md-6"><strong>السعر:</strong> {{ $record->formatted_price }}</div>
                            <div class="col-md-6"><strong>المبلغ المستحق:</strong> {{ number_format($record->amount_due, 2) }} ر.س</div>
                            <div class="col-md-6"><strong>تاريخ الاشتراك:</strong> {{ $record->subscribed_at?->format('Y-m-d') ?? '—' }}</div>
                            <div class="col-md-6"><strong>تاريخ التجديد:</strong> {{ $record->renewal_at?->format('Y-m-d') ?? '—' }}</div>
                            <div class="col-md-6"><strong>مدة التنفيذ:</strong> {{ $record->execution_duration ?? '—' }}</div>
                            <div class="col-md-6"><strong>الحالة:</strong> <span class="badge bg-{{ $record->status_color }}-transparent">{{ $record->status_label }}</span></div>
                            @if($record->notes)
                            <div class="col-12"><strong>ملاحظات:</strong><br>{!! nl2br(e($record->notes)) !!}</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card custom-card">
                    <div class="card-header"><div class="card-title">العميل</div></div>
                    <div class="card-body">
                        <p class="mb-2">{{ $record->customer?->fullname }}</p>
                        <p class="mb-3 text-muted">{{ $record->customer?->email }}</p>
                        <a href="{{ route('admin.customers.show', $record->customer_id) }}" class="btn btn-sm btn-info-light w-100">ملف العميل</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
