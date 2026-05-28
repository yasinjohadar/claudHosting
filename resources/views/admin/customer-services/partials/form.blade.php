@php
    $record = $record ?? null;
    $catalogServices = $catalogServices ?? collect();
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">العميل <span class="text-danger">*</span></label>
        <select name="customer_id" class="form-select @error('customer_id') is-invalid @enderror" required>
            <option value="">— اختر العميل —</option>
            @foreach($customers as $customer)
                <option value="{{ $customer->id }}" @selected(old('customer_id', $record?->customer_id ?? $selectedCustomerId ?? null) == $customer->id)>
                    {{ $customer->fullname }} ({{ $customer->email }})
                </option>
            @endforeach
        </select>
        @error('customer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">الخدمة من الكتالوج <span class="text-danger">*</span></label>
        <select name="offered_service_id" id="offered_service_id" class="form-select @error('offered_service_id') is-invalid @enderror" required>
            <option value="">— اختر الخدمة —</option>
            @foreach($catalogServices as $catalog)
                <option value="{{ $catalog->id }}"
                    data-name="{{ $catalog->name }}"
                    data-price="{{ $catalog->price }}"
                    data-currency="{{ $catalog->currency }}"
                    data-duration="{{ $catalog->execution_duration }}"
                    data-days="{{ $catalog->execution_days }}"
                    @selected(old('offered_service_id', $record?->offered_service_id ?? $selectedOfferedServiceId ?? null) == $catalog->id)>
                    {{ $catalog->name }} — {{ $catalog->formatted_price }}
                    @if($catalog->serviceType) ({{ $catalog->serviceType->name }}) @endif
                </option>
            @endforeach
        </select>
        @error('offered_service_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-8">
        <label class="form-label">اسم الخدمة للعميل</label>
        <input type="text" name="name" id="service_name" class="form-control" value="{{ old('name', $record?->name) }}" placeholder="يُملأ من الكتالوج إن تُرك فارغاً">
    </div>
    <div class="col-md-4">
        <label class="form-label">الحالة <span class="text-danger">*</span></label>
        <select name="status" class="form-select" required>
            @foreach(\App\Models\CustomerService::statusOptions() as $val => $label)
                <option value="{{ $val }}" @selected(old('status', $record?->status ?? 'pending') === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">السعر</label>
        <input type="number" name="price" id="service_price" step="0.01" min="0" class="form-control" value="{{ old('price', $record?->price) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">المبلغ المستحق</label>
        <input type="number" name="amount_due" id="amount_due" step="0.01" min="0" class="form-control" value="{{ old('amount_due', $record?->amount_due) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">العملة</label>
        <select name="currency" id="service_currency" class="form-select">
            <option value="SAR" @selected(old('currency', $record?->currency ?? 'SAR') === 'SAR')>SAR</option>
            <option value="USD" @selected(old('currency', $record?->currency) === 'USD')>USD</option>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">تاريخ الاشتراك</label>
        <input type="date" name="subscribed_at" class="form-control" value="{{ old('subscribed_at', $record?->subscribed_at?->format('Y-m-d') ?? now()->format('Y-m-d')) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">تاريخ التجديد</label>
        <input type="date" name="renewal_at" class="form-control" value="{{ old('renewal_at', $record?->renewal_at?->format('Y-m-d')) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">مدة التنفيذ (نص)</label>
        <input type="text" name="execution_duration" id="execution_duration" class="form-control" value="{{ old('execution_duration', $record?->execution_duration) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">مدة التنفيذ (أيام)</label>
        <input type="number" name="execution_days" id="execution_days" class="form-control" value="{{ old('execution_days', $record?->execution_days) }}" min="0">
    </div>
    <div class="col-12">
        <label class="form-label">ملاحظات</label>
        <textarea name="notes" rows="3" class="form-control">{{ old('notes', $record?->notes) }}</textarea>
    </div>
</div>
