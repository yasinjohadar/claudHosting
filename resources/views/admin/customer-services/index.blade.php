@extends('admin.layouts.master')

@section('page-title')
خدمات العملاء
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">خدمات العملاء</h4>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item active">خدمات العملاء</li>
                    </ol>
                </nav>
            </div>
            <div class="ms-auto">
                <a href="{{ route('admin.customer-services.create') }}" class="btn btn-primary">
                    <i class="fe fe-plus"></i> تسجيل خدمة لعميل
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <div class="card custom-card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">العميل</label>
                        <select name="customer_id" class="form-select">
                            <option value="">الكل</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" @selected(request('customer_id') == $c->id)>{{ $c->fullname }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">الخدمة</label>
                        <select name="offered_service_id" class="form-select">
                            <option value="">الكل</option>
                            @foreach($catalogServices as $s)
                                <option value="{{ $s->id }}" @selected(request('offered_service_id') == $s->id)>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">الحالة</label>
                        <select name="status" class="form-select">
                            <option value="">الكل</option>
                            @foreach(\App\Models\CustomerService::statusOptions() as $val => $label)
                                <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">بحث</label>
                        <input type="text" name="search" class="form-control" value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2"><button class="btn btn-primary w-100">تصفية</button></div>
                </form>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered text-nowrap w-100">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>العميل</th>
                                <th>الخدمة</th>
                                <th>السعر</th>
                                <th>المستحق</th>
                                <th>الاشتراك</th>
                                <th>التجديد</th>
                                <th>الحالة</th>
                                <th>فاتورة</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($records as $record)
                            <tr>
                                <td>{{ $record->id }}</td>
                                <td>{{ $record->customer?->fullname }}</td>
                                <td>{{ $record->name }}</td>
                                <td>{{ $record->formatted_price }}</td>
                                <td>{{ number_format($record->amount_due, 2) }} ر.س</td>
                                <td>{{ $record->subscribed_at?->format('Y-m-d') ?? '—' }}</td>
                                <td>{{ $record->renewal_at?->format('Y-m-d') ?? '—' }}</td>
                                <td><span class="badge bg-{{ $record->status_color }}-transparent">{{ $record->status_label }}</span></td>
                                <td>
                                    @if($record->invoice_id)
                                        <a href="{{ route('admin.invoices.show', $record->invoice_id) }}">#{{ $record->invoice?->invoice_number ?? $record->invoice_id }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    <div class="hstack gap-1">
                                        <a href="{{ route('admin.customer-services.show', $record) }}" class="btn btn-icon btn-sm btn-info-transparent rounded-pill"><i class="ri-eye-line"></i></a>
                                        <a href="{{ route('admin.customer-services.edit', $record) }}" class="btn btn-icon btn-sm btn-warning-transparent rounded-pill"><i class="ri-edit-line"></i></a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="10" class="text-center text-muted">لا توجد سجلات</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($records->hasPages())<div class="mt-3">{{ $records->links() }}</div>@endif
            </div>
        </div>
    </div>
</div>
@endsection
