@extends('admin.layouts.master')

@section('page-title')
تفاصيل الفاتورة: {{ $invoice->invoice_number }}
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">فاتورة {{ $invoice->invoice_number }}</h4>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.invoices.index') }}">الفواتير</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $invoice->invoice_number }}</li>
                    </ol>
                </nav>
            </div>
            <div class="ms-auto pageheader-btn d-flex flex-wrap gap-2">
                <a href="{{ route('admin.invoices.edit', $invoice->id) }}" class="btn btn-warning">
                    <i class="fe fe-edit"></i> تعديل
                </a>
                @if ($invoice->status != 'Paid' && $invoice->status != 'Cancelled')
                    <form action="{{ route('admin.invoices.markPaid', $invoice->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success" onclick="return confirm('هل أنت متأكد من تعليم هذه الفاتورة كمدفوعة؟')">
                            <i class="fe fe-check"></i> تعليم كمدفوعة
                        </button>
                    </form>
                @endif
                <a href="{{ route('admin.invoices.index') }}" class="btn btn-light">
                    <i class="fe fe-arrow-right"></i> العودة
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if(session('info'))
            <div class="alert alert-info alert-dismissible fade show">{{ session('info') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <div class="row">
        <div class="col-md-3">
            <!-- معلومات الفاتورة الأساسية -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">معلومات الفاتورة</h3>
                </div>
                <div class="card-body">
                    <p><strong>رقم الفاتورة:</strong> {{ $invoice->invoice_number }}</p>
                    <p><strong>التاريخ:</strong> {{ $invoice->date }}</p>
                    <p><strong>تاريخ الاستحقاق:</strong> {{ $invoice->duedate }}</p>
                    <p><strong>العملة:</strong> {{ $invoice->currency }}</p>
                    <p><strong>طريقة الدفع:</strong> {{ $invoice->paymentmethod ?? '-' }}</p>
                    <p><strong>الحالة:</strong>
                        @if ($invoice->status == 'Paid')
                            <span class="badge badge-success">مدفوعة</span>
                        @elseif ($invoice->status == 'Unpaid')
                            <span class="badge badge-danger">غير مدفوعة</span>
                        @elseif ($invoice->status == 'Cancelled')
                            <span class="badge badge-secondary">ملغاة</span>
                        @elseif ($invoice->status == 'Refunded')
                            <span class="badge badge-warning">مستردة</span>
                        @elseif ($invoice->status == 'Collections')
                            <span class="badge badge-info">تحصيل</span>
                        @elseif ($invoice->status == 'Draft')
                            <span class="badge badge-secondary">مسودة</span>
                        @else
                            <span class="badge badge-info">{{ $invoice->status }}</span>
                        @endif
                    </p>
                </div>
            </div>
            
            <!-- ملخص المبالغ -->
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">ملخص المبالغ</h3>
                </div>
                <div class="card-body">
                    <p><strong>الإجمالي:</strong> {{ $invoice->total }} {{ $invoice->currency }}</p>
                    <p><strong>المدفوع:</strong> {{ $invoice->credit }} {{ $invoice->currency }}</p>
                    <p><strong>المتبقي:</strong> {{ $invoice->balance }} {{ $invoice->currency }}</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <!-- معلومات العميل -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">معلومات العميل</h3>
                </div>
                <div class="card-body">
                    @if($invoice->customer)
                    <p><strong>الاسم:</strong> {{ $invoice->customer->full_name }}</p>
                    <p><strong>البريد الإلكتروني:</strong> {{ $invoice->customer->email }}</p>
                    <p><strong>الشركة:</strong> {{ $invoice->customer->companyname ?? '—' }}</p>
                    <p><strong>رقم الهاتف:</strong> {{ $invoice->customer->phonenumber ?? '—' }}</p>
                    <div class="btn-group mt-2">
                        <a href="{{ route('admin.customers.show', $invoice->customer->id) }}" class="btn btn-info btn-sm">
                            <i class="fas fa-user"></i> عرض العميل
                        </a>
                    </div>
                    @else
                    <p class="text-muted">لم يُربط عميل بهذه الفاتورة.</p>
                    @endif
                </div>
            </div>
            
            <!-- بنود الفاتورة -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">بنود الفاتورة</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>الوصف</th>
                                <th>السعر</th>
                                <th>الضريبة</th>
                                <th>الإجمالي</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($invoice->items as $item)
                                <tr>
                                    <td>{{ $item->description }}</td>
                                    <td>{{ $item->amount }} {{ $invoice->currency }}</td>
                                    <td>{{ $item->taxed ? 'نعم' : 'لا' }}</td>
                                    <td>{{ $item->amount }} {{ $invoice->currency }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">لا توجد بنود لهذه الفاتورة</td>
                                </tr>
                            @endforelse
                            <tr>
                                <td colspan="3" class="text-right"><strong>الإجمالي</strong></td>
                                <td><strong>{{ $invoice->total }} {{ $invoice->currency }}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- المدفوعات -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">المدفوعات</h3>
                    @if ($invoice->status != 'Paid' && $invoice->status != 'Cancelled')
                        <div class="card-tools">
                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addPaymentModal">
                                <i class="fas fa-plus"></i> إضافة دفعة
                            </button>
                        </div>
                    @endif
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>التاريخ</th>
                                <th>المبلغ</th>
                                <th>طريقة الدفع</th>
                                <th>الحالة</th>
                                <th>رقم المعاملة</th>
                                <th>ملاحظات</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($invoice->payments as $payment)
                                <tr>
                                    <td>{{ $payment->date }}</td>
                                    <td>{{ $payment->amount }} {{ $invoice->currency }}</td>
                                    <td>{{ $payment->payment_method_name }}</td>
                                    <td><span class="badge bg-{{ $payment->status_color }}-transparent">{{ $payment->status_name }}</span></td>
                                    <td>{{ $payment->transid ?? '-' }}</td>
                                    <td>{{ $payment->notes ?? '-' }}</td>
                                    <td><a href="{{ route('admin.payments.show', $payment) }}" class="btn btn-xs btn-info">عرض</a></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">لا توجد مدفوعات لهذه الفاتورة</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- ملاحظات الفاتورة -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">ملاحظات الفاتورة</h3>
                </div>
                <div class="card-body">
                    @if ($invoice->notes)
                        <p>{{ $invoice->notes }}</p>
                    @else
                        <p>لا توجد ملاحظات لهذه الفاتورة.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    </div>

<!-- Modal إضافة دفعة -->
<div class="modal fade" id="addPaymentModal" tabindex="-1" role="dialog" aria-labelledby="addPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPaymentModalLabel">إضافة دفعة جديدة</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.invoices.addPayment', $invoice->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="amount">المبلغ <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" value="{{ old('amount', $invoice->balance) }}" step="0.01" min="0" max="{{ $invoice->balance }}" required>
                        @error('amount')
                            <span class="invalid-feedback" role="alert">{{ $message }}</span>
                        @enderror
                        <small class="form-text text-muted">المبلغ المتبقي للفاتورة: {{ $invoice->balance }} {{ $invoice->currency }}</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="paymentmethod">طريقة الدفع <span class="text-danger">*</span></label>
                        <select class="form-control @error('paymentmethod') is-invalid @enderror" id="paymentmethod" name="paymentmethod" required>
                            <option value="">-- اختر طريقة الدفع --</option>
                            <option value="paypal">PayPal</option>
                            <option value="banktransfer">تحويل بنكي</option>
                            <option value="creditcard">بطاقة ائتمان</option>
                            <option value="cash">نقدي</option>
                            <option value="other">آخر</option>
                        </select>
                        @error('paymentmethod')
                            <span class="invalid-feedback" role="alert">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="transid">رقم المعاملة</label>
                        <input type="text" class="form-control @error('transid') is-invalid @enderror" id="transid" name="transid" value="{{ old('transid') }}">
                        @error('transid')
                            <span class="invalid-feedback" role="alert">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">ملاحظات</label>
                        <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                        @error('notes')
                            <span class="invalid-feedback" role="alert">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ الدفعة</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
@endsection