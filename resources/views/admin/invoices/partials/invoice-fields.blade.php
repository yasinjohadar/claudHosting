@php
    $invoiceModel = $invoice ?? null;
    $selectedCustomerId = old('customer_id', $selectedCustomerId ?? $invoiceModel?->customer_id);
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="domain-form-label" for="customer_id_select">العميل <span class="text-danger">*</span></label>
        <select name="customer_id" id="customer_id_select" class="form-select form-select-sm customer-ajax-select @error('customer_id') is-invalid @enderror" required
            data-search-url="{{ route('admin.customer-services.search-customers') }}">
            <option value="">— ابحث بالاسم أو دومين البريد —</option>
            @foreach($customers as $customer)
            <option value="{{ $customer->id }}" @selected((string) $selectedCustomerId === (string) $customer->id)>
                {{ $customer->fullname }} ({{ $customer->email }})
            </option>
            @endforeach
        </select>
        <div class="form-text">مثال: اسم العميل أو <span dir="ltr">gmail.com</span></div>
        @error('customer_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="domain-form-label" for="paymentmethod">طريقة الدفع</label>
        <select class="form-select form-select-sm @error('paymentmethod') is-invalid @enderror" id="paymentmethod" name="paymentmethod">
            <option value="">— اختر —</option>
            <option value="banktransfer" @selected(old('paymentmethod', $invoiceModel?->paymentmethod) === 'banktransfer')>تحويل بنكي</option>
            <option value="creditcard" @selected(old('paymentmethod', $invoiceModel?->paymentmethod) === 'creditcard')>بطاقة ائتمان</option>
            <option value="paypal" @selected(old('paymentmethod', $invoiceModel?->paymentmethod) === 'paypal')>PayPal</option>
            <option value="cash" @selected(old('paymentmethod', $invoiceModel?->paymentmethod) === 'cash')>نقدي</option>
            <option value="other" @selected(old('paymentmethod', $invoiceModel?->paymentmethod) === 'other')>أخرى</option>
        </select>
        @error('paymentmethod')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="domain-form-label" for="date">تاريخ الفاتورة <span class="text-danger">*</span></label>
        <input type="date" class="form-control form-control-sm @error('date') is-invalid @enderror" id="date" name="date"
            value="{{ old('date', $invoiceModel?->date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
        @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="domain-form-label" for="duedate">تاريخ الاستحقاق <span class="text-danger">*</span></label>
        <input type="date" class="form-control form-control-sm @error('duedate') is-invalid @enderror" id="duedate" name="duedate"
            value="{{ old('duedate', $invoiceModel?->duedate?->format('Y-m-d') ?? now()->addDays(7)->format('Y-m-d')) }}" required>
        @error('duedate')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label class="domain-form-label" for="notes">ملاحظات</label>
        <textarea class="form-control form-control-sm @error('notes') is-invalid @enderror" id="notes" name="notes" rows="2">{{ old('notes', $invoiceModel?->notes) }}</textarea>
        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

@include('admin.invoices.partials.customer-ajax-script')
