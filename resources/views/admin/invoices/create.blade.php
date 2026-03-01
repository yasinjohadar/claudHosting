@extends('admin.layouts.master')

@section('title', 'إنشاء فاتورة جديدة')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">إنشاء فاتورة جديدة</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.invoices.index') }}" class="btn btn-default btn-sm">
                            <i class="fas fa-arrow-left"></i> العودة
                        </a>
                    </div>
                </div>
                <form action="{{ route('admin.invoices.store') }}" method="POST" id="invoiceForm">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="userid">العميل <span class="text-danger">*</span></label>
                                    <select class="form-control @error('userid') is-invalid @enderror" id="userid" name="userid" required>
                                        <option value="">-- اختر العميل --</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}" {{ old('userid', $customer_id) == $customer->id ? 'selected' : '' }}>
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
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="date">تاريخ الفاتورة <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('date') is-invalid @enderror" id="date" name="date" value="{{ old('date', now()->format('Y-m-d')) }}" required>
                                    @error('date')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="duedate">تاريخ الاستحقاق <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('duedate') is-invalid @enderror" id="duedate" name="duedate" value="{{ old('duedate', now()->addDays(7)->format('Y-m-d')) }}" required>
                                    @error('duedate')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="notes">ملاحظات</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
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
                                    <tr class="item-row">
                                        <td>
                                            <input type="text" class="form-control item-description" name="items[0][description]" placeholder="وصف البند" required>
                                            <span class="invalid-feedback"></span>
                                        </td>
                                        <td>
                                            <div class="input-group">
                                                <input type="number" class="form-control item-amount" name="items[0][amount]" placeholder="0.00" step="0.01" min="0" required>
                                                <div class="input-group-append">
                                                    <span class="input-group-text currency">USD</span>
                                                </div>
                                            </div>
                                            <span class="invalid-feedback"></span>
                                        </td>
                                        <td>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input item-taxed" id="taxed0" name="items[0][taxed]" value="1">
                                                <label class="custom-control-label" for="taxed0"></label>
                                            </div>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-danger btn-sm remove-item">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
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
                                        <td><strong id="totalAmount">0.00 USD</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">حفظ الفاتورة</button>
                        <a href="{{ route('admin.invoices.index') }}" class="btn btn-default">إلغاء</a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">معلومات إضافية</h3>
                </div>
                <div class="card-body">
                    <p>سيتم إنشاء الفاتورة في نظام WHMCS وفي قاعدة البيانات المحلية.</p>
                    <p>يمكنك مزامنة بيانات الفاتورة لاحقاً من WHMCS.</p>
                    <div class="alert alert-info">
                        <h5><i class="icon fas fa-info"></i> ملاحظة</h5>
                        الحقول المطلوبة يجب ملؤها قبل حفظ الفاتورة.
                    </div>
                </div>
            </div>
            
            <div class="card card-warning">
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
        let itemCount = 1;
        const addItemBtn = document.getElementById('addItemBtn');
        const itemsTable = document.querySelector('#itemsTable tbody');
        const totalAmount = document.getElementById('totalAmount');
        const invoiceForm = document.getElementById('invoiceForm');
        
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
                            <span class="input-group-text currency">USD</span>
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
            totalAmount.textContent = total.toFixed(2) + ' USD';
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