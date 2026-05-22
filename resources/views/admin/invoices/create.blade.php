@extends('admin.layouts.master')

@section('page-title')
إنشاء فاتورة
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">إنشاء فاتورة جديدة</h4>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.invoices.index') }}">الفواتير</a></li>
                        <li class="breadcrumb-item active" aria-current="page">إنشاء</li>
                    </ol>
                </nav>
            </div>
            <div class="ms-auto pageheader-btn">
                <a href="{{ route('admin.invoices.index') }}" class="btn btn-light">
                    <i class="fe fe-arrow-right"></i> العودة للقائمة
                </a>
            </div>
        </div>

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row g-4">
            <div class="col-xl-8">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">بيانات الفاتورة</div>
                    </div>
                    <form action="{{ route('admin.invoices.store') }}" method="POST" id="invoiceForm">
                        @csrf
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="customer_id" class="form-label">العميل <span class="text-danger">*</span></label>
                                    <select class="form-select @error('customer_id') is-invalid @enderror" id="customer_id" name="customer_id" required>
                                        <option value="">— اختر العميل —</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}" {{ old('customer_id', $customer_id ?? null) == $customer->id ? 'selected' : '' }}>
                                                {{ $customer->full_name }} ({{ $customer->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('customer_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="paymentmethod" class="form-label">طريقة الدفع</label>
                                    <select class="form-select @error('paymentmethod') is-invalid @enderror" id="paymentmethod" name="paymentmethod">
                                        <option value="">— اختر —</option>
                                        <option value="banktransfer" @selected(old('paymentmethod') === 'banktransfer')>تحويل بنكي</option>
                                        <option value="creditcard" @selected(old('paymentmethod') === 'creditcard')>بطاقة ائتمان</option>
                                        <option value="paypal" @selected(old('paymentmethod') === 'paypal')>PayPal</option>
                                        <option value="cash" @selected(old('paymentmethod') === 'cash')>نقدي</option>
                                        <option value="other" @selected(old('paymentmethod') === 'other')>أخرى</option>
                                    </select>
                                    @error('paymentmethod')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="date" class="form-label">تاريخ الفاتورة <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('date') is-invalid @enderror" id="date" name="date"
                                        value="{{ old('date', now()->format('Y-m-d')) }}" required>
                                    @error('date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="duedate" class="form-label">تاريخ الاستحقاق <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('duedate') is-invalid @enderror" id="duedate" name="duedate"
                                        value="{{ old('duedate', now()->addDays(7)->format('Y-m-d')) }}" required>
                                    @error('duedate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label for="notes" class="form-label">ملاحظات</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="2">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h6 class="mb-0">بنود الفاتورة</h6>
                                <button type="button" class="btn btn-sm btn-primary-light" id="addItemBtn">
                                    <i class="fe fe-plus"></i> إضافة بند
                                </button>
                            </div>

                            <div class="table-responsive border rounded">
                                <table class="table table-bordered table-hover mb-0 align-middle" id="itemsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-center" style="width:4rem">حذف</th>
                                            <th>الوصف</th>
                                            <th class="text-center" style="width:6rem">ضريبة</th>
                                            <th style="width:14rem">المبلغ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @include('admin.invoices.partials.item-row', ['index' => 0])
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <td colspan="3" class="text-end fw-semibold">الإجمالي</td>
                                            <td class="fw-bold" id="totalAmount">0.00 ر.س</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fe fe-save"></i> حفظ الفاتورة
                            </button>
                            <a href="{{ route('admin.invoices.index') }}" class="btn btn-light">إلغاء</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">معلومات</div>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3 small">تُحفظ الفاتورة محلياً في النظام ويمكن إدارتها من لوحة التحكم.</p>
                        <div class="alert alert-primary-transparent mb-0">
                            <i class="fe fe-info me-1"></i>
                            الحقول المطلوبة يجب ملؤها قبل حفظ الفاتورة.
                        </div>
                    </div>
                </div>

                @if(($products ?? collect())->isNotEmpty())
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">إضافة من المنتجات</div>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            @foreach ($products as $product)
                                <button type="button" class="list-group-item list-group-item-action add-product-item text-start"
                                    data-product-name="{{ $product->name }}"
                                    data-product-price="{{ $product->price }}">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-medium">{{ $product->name }}</span>
                                        <span class="badge bg-primary-transparent">{{ number_format($product->price, 2) }} ر.س</span>
                                    </div>
                                    @if($product->type)
                                        <small class="text-muted">{{ $product->type }}</small>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@include('admin.invoices.partials.form-scripts')
