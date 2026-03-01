@extends('admin.layouts.master')

@section('page-title')
تفاصيل العميل
@stop

@section('content')
<!-- Start::app-content -->
<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">تفاصيل العميل: {{ $customer->fullname }}</h4>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.customers.index') }}">العملاء</a></li>
                        <li class="breadcrumb-item active" aria-current="page">التفاصيل</li>
                    </ol>
                </nav>
            </div>
            <div class="ms-auto pageheader-btn">
                @if($customer->whmcs_id)
                <form action="{{ route('admin.customers.syncFull', $customer->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-info">
                        <i class="fe fe-refresh-cw"></i> مزامنة كاملة
                    </button>
                </form>
                @endif
                <a href="{{ route('admin.customers.edit', $customer->id) }}" class="btn btn-warning">
                    <i class="fe fe-edit"></i> تعديل
                </a>
                <a href="{{ route('admin.customers.index') }}" class="btn btn-secondary">
                    <i class="fe fe-arrow-left"></i> العودة
                </a>
            </div>
        </div>
        <!-- End Page Header -->

        <!-- Tabs Card -->
        <div class="card custom-card">
            <div class="card-header p-0 border-0">
                <ul class="nav nav-tabs border-0 px-3 pt-2" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" href="#customer-info" data-bs-toggle="tab" data-bs-target="#customer-info" role="tab" aria-selected="true">
                            <i class="fe fe-user me-1"></i> المعلومات
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" href="#customer-invoices" data-bs-toggle="tab" data-bs-target="#customer-invoices" role="tab" aria-selected="false">
                            <i class="fe fe-file-text me-1"></i> الفواتير
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" href="#customer-tickets" data-bs-toggle="tab" data-bs-target="#customer-tickets" role="tab" aria-selected="false">
                            <i class="fe fe-message-circle me-1"></i> التذاكر
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" href="#customer-products" data-bs-toggle="tab" data-bs-target="#customer-products" role="tab" aria-selected="false">
                            <i class="fe fe-package me-1"></i> المنتجات والخدمات
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <!-- تاب: المعلومات -->
                    <div class="tab-pane fade show active" id="customer-info" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-4 col-xl-3 text-center mb-4 mb-lg-0">
                                <span class="avatar avatar-xxl bg-primary-transparent">
                                    {{ strtoupper(substr($customer->firstname, 0, 1)) }}{{ strtoupper(substr($customer->lastname, 0, 1)) }}
                                </span>
                                <h5 class="mt-3 mb-1">{{ $customer->fullname }}</h5>
                                <p class="text-muted mb-0">{{ $customer->email }}</p>
                            </div>
                            <div class="col-lg-4 col-xl-5">
                                <h6 class="fw-semibold mb-3">معلومات العميل</h6>
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2 d-flex justify-content-between"><span class="fw-semibold">الشركة:</span><span class="text-muted">{{ $customer->companyname ?: '-' }}</span></li>
                                    <li class="mb-2 d-flex justify-content-between"><span class="fw-semibold">رقم الهاتف:</span><span class="text-muted">{{ $customer->phonenumber ?: '-' }}</span></li>
                                    <li class="mb-2 d-flex justify-content-between"><span class="fw-semibold">الحالة:</span>
                                        @if($customer->status == 'Active')
                                            <span class="badge bg-success-transparent">نشط</span>
                                        @elseif($customer->status == 'Inactive')
                                            <span class="badge bg-warning-transparent">غير نشط</span>
                                        @else
                                            <span class="badge bg-danger-transparent">مغلق</span>
                                        @endif
                                    </li>
                                    <li class="mb-2 d-flex justify-content-between"><span class="fw-semibold">معرف WHMCS:</span><span class="text-muted">{{ $customer->whmcs_id ?: 'غير متزامن' }}</span></li>
                                    <li class="mb-2 d-flex justify-content-between"><span class="fw-semibold">تاريخ التسجيل:</span><span class="text-muted">{{ $customer->datecreated ? $customer->datecreated->format('Y-m-d') : '-' }}</span></li>
                                </ul>
                            </div>
                            <div class="col-lg-4 col-xl-4">
                                <h6 class="fw-semibold mb-3">العنوان</h6>
                                <ul class="list-unstyled mb-0 small">
                                    <li class="mb-1"><span class="fw-semibold">العنوان 1:</span> <span class="text-muted">{{ $customer->address1 ?: '-' }}</span></li>
                                    <li class="mb-1"><span class="fw-semibold">العنوان 2:</span> <span class="text-muted">{{ $customer->address2 ?: '-' }}</span></li>
                                    <li class="mb-1"><span class="fw-semibold">المدينة:</span> <span class="text-muted">{{ $customer->city ?: '-' }}</span></li>
                                    <li class="mb-1"><span class="fw-semibold">المنطقة:</span> <span class="text-muted">{{ $customer->state ?: '-' }}</span></li>
                                    <li class="mb-1"><span class="fw-semibold">الرمز البريدي:</span> <span class="text-muted">{{ $customer->postcode ?: '-' }}</span></li>
                                    <li class="mb-1"><span class="fw-semibold">الدولة:</span> <span class="text-muted">{{ $customer->country_name }}</span></li>
                                </ul>
                                <h6 class="fw-semibold mb-2 mt-4">جهات الاتصال</h6>
                                @if($customer->whmcs_id)
                                <form action="{{ route('admin.customers.syncContacts', $customer->id) }}" method="POST" class="d-inline mb-2">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary">مزامنة</button>
                                </form>
                                @endif
                                <ul class="list-unstyled mb-0">
                                    @forelse($customer->contacts as $contact)
                                    <li class="mb-2 pb-2 border-bottom">
                                        <span class="fw-semibold d-block">{{ $contact->full_name }}</span>
                                        @if($contact->email)<span class="text-muted small">{{ $contact->email }}</span>@endif
                                        @if($contact->phonenumber)<span class="text-muted small d-block">{{ $contact->phonenumber }}</span>@endif
                                    </li>
                                    @empty
                                    <li class="text-muted small">لا توجد جهات اتصال. قم بالمزامنة من WHMCS.</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- تاب: الفواتير -->
                    <div class="tab-pane fade" id="customer-invoices" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">الفواتير</h6>
                            <a href="{{ route('admin.invoices.create') }}?customer_id={{ $customer->id }}" class="btn btn-sm btn-primary">
                                <i class="fe fe-plus"></i> إنشاء فاتورة
                            </a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered text-nowrap">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>رقم الفاتورة</th>
                                        <th>التاريخ</th>
                                        <th>المبلغ</th>
                                        <th>الحالة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($customer->invoices as $invoice)
                                    <tr>
                                        <td>{{ $invoice->id }}</td>
                                        <td>{{ $invoice->invoice_number }}</td>
                                        <td>{{ $invoice->date ? $invoice->date->format('Y-m-d') : '-' }}</td>
                                        <td>{{ number_format($invoice->total, 2) }}</td>
                                        <td>
                                            @if($invoice->status == 'Paid')
                                                <span class="badge bg-success-transparent">مدفوعة</span>
                                            @elseif($invoice->status == 'Unpaid')
                                                <span class="badge bg-danger-transparent">غير مدفوعة</span>
                                            @else
                                                <span class="badge bg-secondary-transparent">{{ $invoice->status }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.invoices.show', $invoice->id) }}" class="btn btn-icon btn-sm btn-info-transparent">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="text-center">لا توجد فواتير</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- تاب: التذاكر -->
                    <div class="tab-pane fade" id="customer-tickets" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">التذاكر</h6>
                            <a href="{{ route('admin.tickets.create') }}?customer_id={{ $customer->id }}" class="btn btn-sm btn-primary">
                                <i class="fe fe-plus"></i> إنشاء تذكرة
                            </a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered text-nowrap">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>الموضوع</th>
                                        <th>القسم</th>
                                        <th>الأولوية</th>
                                        <th>الحالة</th>
                                        <th>التاريخ</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($customer->tickets as $ticket)
                                    <tr>
                                        <td>{{ $ticket->id }}</td>
                                        <td>{{ Str::limit($ticket->subject, 30) }}</td>
                                        <td>{{ $ticket->department }}</td>
                                        <td>
                                            @if($ticket->priority == 'High' || $ticket->priority == 'Urgent')
                                                <span class="badge bg-danger-transparent">{{ $ticket->priority }}</span>
                                            @elseif($ticket->priority == 'Medium')
                                                <span class="badge bg-warning-transparent">{{ $ticket->priority }}</span>
                                            @else
                                                <span class="badge bg-info-transparent">{{ $ticket->priority }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($ticket->status == 'Open')
                                                <span class="badge bg-success-transparent">مفتوحة</span>
                                            @elseif($ticket->status == 'Closed')
                                                <span class="badge bg-secondary-transparent">مغلقة</span>
                                            @else
                                                <span class="badge bg-info-transparent">{{ $ticket->status }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $ticket->date ? $ticket->date->format('Y-m-d') : '-' }}</td>
                                        <td>
                                            <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="btn btn-icon btn-sm btn-info-transparent">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center">لا توجد تذاكر</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- تاب: المنتجات والخدمات -->
                    <div class="tab-pane fade" id="customer-products" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">المنتجات والخدمات</h6>
                            @if($customer->whmcs_id)
                            <form action="{{ route('admin.customers.syncProducts', $customer->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                    <i class="fe fe-refresh-cw"></i> مزامنة المنتجات
                                </button>
                            </form>
                            @endif
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered text-nowrap">
                                <thead>
                                    <tr>
                                        <th>المنتج</th>
                                        <th>الدومين</th>
                                        <th>الحالة</th>
                                        <th>تاريخ الاستحقاق</th>
                                        <th>المبلغ</th>
                                        <th>دورة الفوترة</th>
                                        <th>cPanel</th>
                                        <th>تحكم الحساب</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($customer->products as $product)
                                    @php $status = $product->pivot->domainstatus ?? $product->pivot->status ?? ''; $sid = $product->pivot->whmcs_service_id ?? null; @endphp
                                    <tr>
                                        <td>{{ $product->name }}</td>
                                        <td>{{ $product->pivot->domain ?? '-' }}</td>
                                        <td>
                                            @if($status == 'Active')
                                                <span class="badge bg-success-transparent">نشط</span>
                                            @elseif($status == 'Suspended')
                                                <span class="badge bg-warning-transparent">موقوف</span>
                                            @else
                                                <span class="badge bg-secondary-transparent">{{ $status ?: '-' }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $product->pivot->nextduedate ? \Carbon\Carbon::parse($product->pivot->nextduedate)->format('Y-m-d') : '-' }}</td>
                                        <td>{{ number_format($product->pivot->amount ?? 0, 2) }}</td>
                                        <td>{{ $product->pivot->billingcycle ?? '-' }}</td>
                                        <td>
                                            @if(config('services.cpanel.url'))
                                                @php
                                                    $cpanelUrl = config('services.cpanel.url');
                                                    if (!empty($product->pivot->username)) {
                                                        $cpanelUrl = str_replace(':username', $product->pivot->username, $cpanelUrl);
                                                    }
                                                @endphp
                                                @if(!empty($product->pivot->username) || !str_contains(config('services.cpanel.url'), ':username'))
                                                    <a href="{{ $cpanelUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-primary-transparent" title="فتح cPanel">
                                                        <i class="fe fe-external-link"></i> دخول cPanel
                                                    </a>
                                                @else
                                                    <span class="text-muted small">-</span>
                                                @endif
                                            @else
                                                <span class="text-muted small" title="أضف CPANEL_BASE_URL في ملف .env">غير مُعد</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($customer->whmcs_id && $sid)
                                                @if($status == 'Active')
                                                    <form action="{{ route('admin.customers.productSuspend', [$customer->id, $sid]) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <input type="text" name="reason" class="form-control form-control-sm d-inline-block w-auto me-1" placeholder="سبب التعليق (اختياري)" style="max-width:120px">
                                                        <button type="submit" class="btn btn-sm btn-warning">تعليق</button>
                                                    </form>
                                                @elseif($status == 'Suspended')
                                                    <form action="{{ route('admin.customers.productUnsuspend', [$customer->id, $sid]) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success">إلغاء التعليق</button>
                                                    </form>
                                                @endif
                                                @if($status != 'Terminated' && $status != 'Cancelled')
                                                    <form action="{{ route('admin.customers.productTerminate', [$customer->id, $sid]) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من إنهاء الخدمة نهائياً؟');">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-danger">إنهاء</button>
                                                    </form>
                                                @endif
                                                @if(!in_array($status, ['Active','Suspended','Terminated','Cancelled']))
                                                    <span class="text-muted small">-</span>
                                                @endif
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="8" class="text-center">لا توجد منتجات</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Tabs Card -->
    </div>
</div>
<!-- End::app-content -->
@endsection
