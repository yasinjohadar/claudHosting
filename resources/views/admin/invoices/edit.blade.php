@extends('admin.layouts.master')

@section('title', 'تعديل الفاتورة: ' . $invoice->invoicenum)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">تعديل الفاتورة: {{ $invoice->invoicenum }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.invoices.show', $invoice->id) }}" class="btn btn-info btn-sm">
                            <i class="fas fa-eye"></i> عرض
                        </a>
                        <a href="{{ route('admin.invoices.index') }}" class="btn btn-default btn-sm">
                            <i class="fas fa-arrow-left"></i> العودة
                        </a>
                    </div>
                </div>
                <form action="{{ route('admin.invoices.update', $invoice->id) }}" method="POST" id="invoiceForm">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="userid">العميل <span class="text-danger">*</span></label>
                                    <select class="form-control @error('userid') is-invalid @enderror" id="userid" name="userid" required>
                                        <option value="">-- اختر العميل --</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}" {{ old('userid', $invoice->userid) == $customer->id ? 'selected' : '' }}>
                                                {{ $customer->firstname }} {{ $customer->lastname }} ({{ $customer->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('userid')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="paymentmethod">طريقة الدفع</label>
                                    <select class="form-control @error('paymentmethod') is-invalid @enderror" id="paymentmethod" name="paymentmethod">
                                        <option value="">-- اختر طريقة الدفع --</option>
                                        <option value="paypal" {{ old('paymentmethod', $invoice->paymentmethod) == 'paypal' ? 'selected' : '' }}>PayPal</option>
                                        <option value="banktransfer" {{ old('paymentmethod', $invoice->paymentmethod) == 'banktransfer' ? 'selected' : '' }}>تحويل بنكي</option>
                                        <option value="creditcard" {{ old('paymentmethod', $invoice->paymentmethod) == 'creditcard' ? 'selected' : '' }}>بطاقة ائتمان</option>
                                        <option value="cash" {{ old('paymentmethod', $invoice->paymentmethod) == 'cash' ? 'selected' : '' }}>نقدي</option>
                                        <option value="other" {{ old('paymentmethod', $invoice->paymentmethod) == 'other' ? 'selected' : '' }}>آخر</option>
                                    </select>
                                    @error('paymentmethod')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="date">تاريخ الفاتورة <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('date') is-invalid @enderror" id="date" name="date" value="{{ old('date', $invoice->date) }}" required>
                                    @error('date')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="duedate">تاريخ الاستحقاق <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('duedate') is-invalid @enderror" id="duedate" name="duedate" value="{{ old('duedate', $invoice->duedate) }}" required>
                                    @error('duedate')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="notes">ملاحظات</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes', $invoice->notes) }}</textarea>
                            @error('notes')
                                <span class="invalid-feedback" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        
                        <h5 class="mt-4 mb-3">بنود الفاتورة</h5>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered" id="itemsTable">
                                <thead>
                                    <tr>
                                        <th>الوصف</th>
                                        <th>السعر</th>
                                        <th>ضريبة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($invoice->items as $index => $item)
                                        <tr class="item-row">
                                            <td>
                                                <input type="text" class="form-control item-description" name="items[{{ $index }}][description]" value="{{ old('items.'.$index.'.description', $item->description) }}" placeholder="وصف البند" required>
                                                <span class="invalid-feedback"></span>
                                            </td>
                                            <td>
                                                <div class="input-group">
                                                    <input type="number" class="form-control item-amount" name="items[{{ $index }}][amount]" value="{{ old('items.'.$index.'.amount', $item->amount) }}" placeholder="0.00" step="0.01" min="0" required>
                                                    <div class="input-group-append">
                                                        <span class="input-group-text currency">{{ $invoice->currency }}</span>
                                                    </div>
                                                </div>
                                                <span class="invalid-feedback"></span>
                                            </td>
                                            <td>
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input item-taxed" id="taxed{{ $index }}" name="items[{{ $index }}][taxed]" value="1" {{ old('items.'.$index.'.taxed', $item->taxed) ? 'checked' : '' }}>
                                                    <label class="custom-control-label" for="taxed{{ $index }}"></label>
                                                </div>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-danger btn-sm remove-item">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4">
                                            <button type="button" class="btn btn-info btn-sm" id="addItemBtn">
                                                <i class="fas fa-plus"></i> إضافة بند
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-right"><strong>الإجمالي</strong></td>
                                        <td><strong id="totalAmount">{{ $invoice->total }} {{ $invoice->currency }}</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">تحديث الفاتورة</button>
                        <a href="{{ route('admin.invoices.show', $invoice->id) }}" class="btn btn-default">إلغاء</a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">معلومات الفاتورة</h3>
                </div>
                <div class="card-body">
                    <p><strong>رقم الفاتورة:</strong> {{ $invoice->invoicenum }}</p>
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
            
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title">ملاحظات</h3>
                </div>
                <div class="card-body">
                    <p>سيتم تحديث بيانات الفاتورة في نظام WHMCS وفي قاعدة البيانات المحلية.</p>
                    <div class="alert alert-warning">
                        <h5><i class="icon fas fa-exclamation-triangle"></i> تحذير</h5>
                        الحقول المطلوبة يجب ملؤها قبل تحديث الفاتورة.
                    </div>
                </div>
            </div>
            
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">المنتجات المتاحة</h3>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        @foreach ($products as $product)
                            <a href="#" class="list-group-item list-group-item-action add-product-item" data-product-name="{{ $product->name }}" data-product-price="{{ $product->price }}">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">{{ $product->name }}</h6>
                                    <small>{{ $product->price }} {{ $product->currency }}</small>
                                </div>
                                <small class="text-muted">{{ $product->type }}</small>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        let itemCount = {{ count($invoice->items) }};
        const addItemBtn = document.getElementById('addItemBtn');
        const itemsTable = document.querySelector('#itemsTable tbody');
        const totalAmount = document.getElementById('totalAmount');
        const invoiceForm = document.getElementById('invoiceForm');
        const currency = '{{ $invoice->currency }}';
        
        // إضافة بند جديد
        addItemBtn.addEventListener('click', function() {
            const newRow = document.createElement('tr');
            newRow.className = 'item-row';
            newRow.innerHTML = `
                <td>
                    <input type="text" class="form-control item-description" name="items[${itemCount}][description]" placeholder="وصف البند" required>
                    <span class="invalid-feedback"></span>
                </td>
                <td>
                    <div class="input-group">
                        <input type="number" class="form-control item-amount" name="items[${itemCount}][amount]" placeholder="0.00" step="0.01" min="0" required>
                        <div class="input-group-append">
                            <span class="input-group-text currency">${currency}</span>
                        </div>
                    </div>
                    <span class="invalid-feedback"></span>
                </td>
                <td>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input item-taxed" id="taxed${itemCount}" name="items[${itemCount}][taxed]" value="1">
                        <label class="custom-control-label" for="taxed${itemCount}"></label>
                    </div>
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-item">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            itemsTable.appendChild(newRow);
            itemCount++;
            
            // إضافة مستمعي الأحداث للعناصر الجديدة
            addEventListeners();
        });
        
        // حذف بند
        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-item')) {
                e.target.closest('.item-row').remove();
                calculateTotal();
            }
        });
        
        // إضافة منتج من القائمة
        document.querySelectorAll('.add-product-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const productName = this.getAttribute('data-product-name');
                const productPrice = this.getAttribute('data-product-price');
                
                // الحصول على آخر صف فارغ أو إنشاء صف جديد
                const lastRow = document.querySelector('#itemsTable tbody tr:last-child');
                const descriptionInput = lastRow.querySelector('.item-description');
                const amountInput = lastRow.querySelector('.item-amount');
                
                // إذا كان الصف الأخير يحتوي على بيانات، قم بإنشاء صف جديد
                if (descriptionInput.value.trim() !== '' || amountInput.value !== '0.00') {
                    addItemBtn.click();
                    const newLastRow = document.querySelector('#itemsTable tbody tr:last-child');
                    descriptionInput = newLastRow.querySelector('.item-description');
                    amountInput = newLastRow.querySelector('.item-amount');
                }
                
                descriptionInput.value = productName;
                amountInput.value = productPrice;
                calculateTotal();
            });
        });
        
        // حساب الإجمالي
        function calculateTotal() {
            let total = 0;
            document.querySelectorAll('.item-amount').forEach(input => {
                const value = parseFloat(input.value) || 0;
                total += value;
            });
            totalAmount.textContent = total.toFixed(2) + ' ' + currency;
        }
        
        // إضافة مستمعي الأحداث
        function addEventListeners() {
            document.querySelectorAll('.item-amount').forEach(input => {
                input.removeEventListener('input', calculateTotal);
                input.addEventListener('input', calculateTotal);
            });
        }
        
        // إضافة مستمعي الأحداث عند التحميل
        addEventListeners();
        
        // التحقق من صحة النموذج قبل الإرسال
        invoiceForm.addEventListener('submit', function(e) {
            const items = document.querySelectorAll('.item-row');
            if (items.length === 0) {
                e.preventDefault();
                alert('يجب إضافة عنصر واحد على الأقل للفاتورة');
                return;
            }
            
            let hasValidItem = false;
            items.forEach(item => {
                const description = item.querySelector('.item-description').value.trim();
                const amount = parseFloat(item.querySelector('.item-amount').value) || 0;
                
                if (description !== '' && amount > 0) {
                    hasValidItem = true;
                }
            });
            
            if (!hasValidItem) {
                e.preventDefault();
                alert('يجب إضافة عنصر واحد صالح على الأقل للفاتورة');
            }
        });
    });
</script>
@endsection