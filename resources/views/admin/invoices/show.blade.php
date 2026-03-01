@extends('admin.layouts.master')

@section('title', 'تفاصيل الفاتورة: ' . $invoice->invoicenum)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <!-- معلومات الفاتورة الأساسية -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">معلومات الفاتورة</h3>
                </div>
                <div class="card-body">
                    <p><strong>رقم الفاتورة:</strong> {{ $invoice->invoicenum }}</p>
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
                    <p><strong>معرف WHMCS:</strong> {{ $invoice->whmcs_id }}</p>
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
            <!-- أزرار التحكم -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">التحكم بالفاتورة</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.invoices.edit', $invoice->id) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> تعديل
                        </a>
                        @if ($invoice->status != 'Paid' && $invoice->status != 'Cancelled')
                            <form action="{{ route('admin.invoices.mark-paid', $invoice->id) }}" method="POST" style="display: inline-block;">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('هل أنت متأكد من رغبتك في تعليم هذه الفاتورة كمدفوعة؟')">
                                    <i class="fas fa-check"></i> تعليم كمدفوعة
                                </button>
                            </form>
                        @endif
                        <form action="{{ route('admin.invoices.sync.single', $invoice->id) }}" method="POST" style="display: inline-block;">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-sync"></i> مزامنة من WHMCS
                            </button>
                        </form>
                        <a href="{{ route('admin.invoices.index') }}" class="btn btn-default btn-sm">
                            <i class="fas fa-arrow-left"></i> العودة
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- معلومات العميل -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">معلومات العميل</h3>
                </div>
                <div class="card-body">
                    <p><strong>الاسم:</strong> {{ $invoice->customer->firstname }} {{ $invoice->customer->lastname }}</p>
                    <p><strong>البريد الإلكتروني:</strong> {{ $invoice->customer->email }}</p>
                    <p><strong>الشركة:</strong> {{ $invoice->customer->companyname ?? '-' }}</p>
                    <p><strong>رقم الهاتف:</strong> {{ $invoice->customer->phonenumber ?? '-' }}</p>
                    <p><strong>العنوان:</strong> {{ $invoice->customer->address1 ?? '-' }}, {{ $invoice->customer->city ?? '-' }}, {{ $invoice->customer->state ?? '-' }}, {{ $invoice->customer->country ?? '-' }}</p>
                    <div class="btn-group mt-2">
                        <a href="{{ route('admin.customers.show', $invoice->customer->id) }}" class="btn btn-info btn-sm">
                            <i class="fas fa-user"></i> عرض العميل
                        </a>
                    </div>
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
                                <th>رقم المعاملة</th>
                                <th>ملاحظات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($invoice->payments as $payment)
                                <tr>
                                    <td>{{ $payment->date }}</td>
                                    <td>{{ $payment->amount }} {{ $invoice->currency }}</td>
                                    <td>{{ $payment->gateway }}</td>
                                    <td>{{ $payment->transid ?? '-' }}</td>
                                    <td>{{ $payment->notes ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">لا توجد مدفوعات لهذه الفاتورة</td>
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
            <form action="{{ route('admin.invoices.add-payment', $invoice->id) }}" method="POST">
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
@endsection