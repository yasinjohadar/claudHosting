@extends('admin.layouts.master')

@section('page-title')
تفاصيل المنتج
@stop

@section('content')
<!-- Start::app-content -->
<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">تفاصيل المنتج: {{ $product->name }}</h4>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.products.index') }}">المنتجات</a></li>
                        <li class="breadcrumb-item active" aria-current="page">التفاصيل</li>
                    </ol>
                </nav>
            </div>
            <div class="ms-auto pageheader-btn">
                @if(!$product->hidden && $product->status === 'Active')
                <a href="{{ route('frontend.package-detail', $product->id) }}" target="_blank" class="btn btn-success">
                    <i class="fe fe-external-link"></i> عرض في الموقع
                </a>
                @endif
                <form action="{{ route('admin.products.sync', $product->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-info">
                        <i class="fe fe-refresh-cw"></i> مزامنة من WHMCS
                    </button>
                </form>
                <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-warning">
                    <i class="fe fe-edit"></i> تعديل
                </a>
                <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">
                    <i class="fe fe-arrow-left"></i> العودة
                </a>
            </div>
        </div>
        <!-- End Page Header -->

        <div class="row">
            <div class="col-xl-4">
                <!-- معلومات المنتج الأساسية -->
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">معلومات المنتج</div>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-3 d-flex justify-content-between">
                                <span class="fw-semibold">الاسم:</span>
                                <span class="text-muted">{{ $product->name }}</span>
                            </li>
                            <li class="mb-3 d-flex justify-content-between">
                                <span class="fw-semibold">النوع:</span>
                                <span class="text-muted">{{ $product->type_name ?? $product->type }}</span>
                            </li>
                            <li class="mb-3 d-flex justify-content-between">
                                <span class="fw-semibold">المجموعة:</span>
                                <span class="text-muted">{{ $product->group_name ?? $product->gid }}</span>
                            </li>
                            <li class="mb-3 d-flex justify-content-between">
                                <span class="fw-semibold">السعر:</span>
                                <span class="text-muted">{{ $product->price }} {{ $product->currency }}</span>
                            </li>
                            <li class="mb-3 d-flex justify-content-between">
                                <span class="fw-semibold">رسوم الإعداد:</span>
                                <span class="text-muted">{{ $product->setupfee ?? '0' }} {{ $product->currency }}</span>
                            </li>
                            <li class="mb-3 d-flex justify-content-between">
                                <span class="fw-semibold">طريقة الدفع:</span>
                                <span class="text-muted">{{ $product->pay_type_name ?? $product->paytype }}</span>
                            </li>
                            <li class="mb-3 d-flex justify-content-between">
                                <span class="fw-semibold">دورة الفوترة:</span>
                                <span>
                                    @if (!$product->billingcycle || $product->billingcycle == 'Free')
                                        <span class="badge bg-info-transparent">مجاني</span>
                                    @elseif ($product->billingcycle == 'Monthly')
                                        <span class="badge bg-success-transparent">شهري</span>
                                    @elseif ($product->billingcycle == 'Annually')
                                        <span class="badge bg-success-transparent">سنوي</span>
                                    @else
                                        <span class="badge bg-secondary-transparent">{{ $product->billingcycle ?? '-' }}</span>
                                    @endif
                                </span>
                            </li>
                            <li class="mb-3 d-flex justify-content-between">
                                <span class="fw-semibold">الحالة:</span>
                                @if ($product->status == 'Active')
                                    <span class="badge bg-success-transparent">نشط</span>
                                @elseif ($product->status == 'Inactive')
                                    <span class="badge bg-warning-transparent">غير نشط</span>
                                @else
                                    <span class="badge bg-secondary-transparent">{{ $product->status ?? '-' }}</span>
                                @endif
                            </li>
                            <li class="mb-3 d-flex justify-content-between">
                                <span class="fw-semibold">معرف WHMCS:</span>
                                <span class="text-muted">{{ $product->whmcs_id ?? '-' }}</span>
                            </li>
                            @php
                                $pricing = $product->pricing;
                                $firstCurrency = is_array($pricing) ? reset($pricing) : null;
                            @endphp
                            @if(is_array($firstCurrency) && !empty($firstCurrency))
                            <li class="mb-3">
                                <span class="fw-semibold d-block mb-2">تفاصيل التسعير:</span>
                                <ul class="list-unstyled small text-muted mb-0">
                                    @if(!empty($firstCurrency['monthly']) && $firstCurrency['monthly'] !== '-1.00')<li>شهري: {{ $firstCurrency['monthly'] }} $</li>@endif
                                    @if(!empty($firstCurrency['quarterly']) && $firstCurrency['quarterly'] !== '-1.00')<li>ربع سنوي: {{ $firstCurrency['quarterly'] }} $</li>@endif
                                    @if(!empty($firstCurrency['semiannually']) && $firstCurrency['semiannually'] !== '-1.00')<li>نصف سنوي: {{ $firstCurrency['semiannually'] }} $</li>@endif
                                    @if(!empty($firstCurrency['annually']) && $firstCurrency['annually'] !== '-1.00')<li>سنوي: {{ $firstCurrency['annually'] }} $</li>@endif
                                </ul>
                            </li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <!-- وصف المنتج -->
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">وصف المنتج</div>
                    </div>
                    <div class="card-body">
                        @if ($product->description)
                            <div class="product-description">{!! $product->description !!}</div>
                        @else
                            <p class="text-muted mb-0">لا يوجد وصف متاح لهذا المنتج.</p>
                        @endif
                    </div>
                </div>

                <!-- العملاء المشتركين في المنتج -->
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">العملاء المشتركين في المنتج</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered text-nowrap">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>العميل</th>
                                        <th>البريد الإلكتروني</th>
                                        <th>الحالة</th>
                                        <th>تاريخ البدء</th>
                                        <th>تاريخ الانتهاء</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($product->customers as $customer)
                                        <tr>
                                            <td>{{ $customer->id }}</td>
                                            <td>{{ $customer->firstname }} {{ $customer->lastname }}</td>
                                            <td>{{ $customer->email }}</td>
                                            <td>
                                                @if ($customer->pivot->status == 'Active')
                                                    <span class="badge bg-success-transparent">نشط</span>
                                                @elseif ($customer->pivot->status == 'Suspended')
                                                    <span class="badge bg-warning-transparent">معلق</span>
                                                @elseif ($customer->pivot->status == 'Terminated')
                                                    <span class="badge bg-danger-transparent">ملغى</span>
                                                @else
                                                    <span class="badge bg-secondary-transparent">{{ $customer->pivot->status ?? '-' }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $customer->pivot->regdate ? \Carbon\Carbon::parse($customer->pivot->regdate)->format('Y-m-d') : '-' }}</td>
                                            <td>{{ $customer->pivot->nextduedate ? \Carbon\Carbon::parse($customer->pivot->nextduedate)->format('Y-m-d') : '-' }}</td>
                                            <td>
                                                <a href="{{ route('admin.customers.show', $customer->id) }}" class="btn btn-icon btn-sm btn-info-transparent" title="عرض العميل">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">لا يوجد عملاء مشتركين في هذا المنتج</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- الفواتير المتعلقة بالمنتج -->
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">الفواتير المتعلقة بالمنتج</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered text-nowrap">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>رقم الفاتورة</th>
                                        <th>العميل</th>
                                        <th>التاريخ</th>
                                        <th>تاريخ الاستحقاق</th>
                                        <th>المبلغ</th>
                                        <th>الحالة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($product->invoices as $invoice)
                                        <tr>
                                            <td>{{ $invoice->id }}</td>
                                            <td>{{ $invoice->invoicenum ?? $invoice->id }}</td>
                                            <td>{{ $invoice->customer ? $invoice->customer->firstname . ' ' . $invoice->customer->lastname : '-' }}</td>
                                            <td>{{ $invoice->date ? $invoice->date->format('Y-m-d') : '-' }}</td>
                                            <td>{{ $invoice->duedate ? $invoice->duedate->format('Y-m-d') : '-' }}</td>
                                            <td>{{ number_format($invoice->total ?? 0, 2) }}</td>
                                            <td>
                                                @if ($invoice->status == 'Paid')
                                                    <span class="badge bg-success-transparent">مدفوعة</span>
                                                @elseif ($invoice->status == 'Unpaid')
                                                    <span class="badge bg-danger-transparent">غير مدفوعة</span>
                                                @elseif ($invoice->status == 'Cancelled')
                                                    <span class="badge bg-secondary-transparent">ملغاة</span>
                                                @else
                                                    <span class="badge bg-info-transparent">{{ $invoice->status ?? '-' }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.invoices.show', $invoice->id) }}" class="btn btn-icon btn-sm btn-info-transparent" title="عرض">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">لا توجد فواتير متعلقة بهذا المنتج</td>
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
<!-- End::app-content -->
@endsection
