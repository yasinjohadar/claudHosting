@php
    $record = $record ?? null;
    $catalogServices = $catalogServices ?? collect();
    $selectedCustomerId = old('customer_id', $record?->customer_id ?? $selectedCustomerId ?? null);
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
        <label class="domain-form-label">الخدمة من الكتالوج <span class="text-danger">*</span></label>
        <select name="offered_service_id" id="offered_service_id" class="form-select form-select-sm @error('offered_service_id') is-invalid @enderror" required>
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
        <label class="domain-form-label">اسم الخدمة للعميل</label>
        <input type="text" name="name" id="service_name" class="form-control form-control-sm" value="{{ old('name', $record?->name) }}" placeholder="يُملأ من الكتالوج إن تُرك فارغاً">
    </div>
    <div class="col-md-4">
        <label class="domain-form-label">الحالة <span class="text-danger">*</span></label>
        <select name="status" class="form-select form-select-sm" required>
            @foreach(\App\Models\CustomerService::statusOptions() as $val => $label)
            <option value="{{ $val }}" @selected(old('status', $record?->status ?? 'pending') === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="domain-form-label">السعر</label>
        <input type="number" name="price" id="service_price" step="0.01" min="0" class="form-control form-control-sm" value="{{ old('price', $record?->price) }}">
    </div>
    <div class="col-md-4">
        <label class="domain-form-label">المبلغ المستحق</label>
        <input type="number" name="amount_due" id="amount_due" step="0.01" min="0" class="form-control form-control-sm" value="{{ old('amount_due', $record?->amount_due) }}">
    </div>
    <div class="col-md-4">
        <label class="domain-form-label">العملة</label>
        <select name="currency" id="service_currency" class="form-select form-select-sm">
            <option value="SAR" @selected(old('currency', $record?->currency ?? 'SAR') === 'SAR')>SAR</option>
            <option value="USD" @selected(old('currency', $record?->currency) === 'USD')>USD</option>
        </select>
    </div>
    <div class="col-md-4">
        <label class="domain-form-label">تاريخ الاشتراك</label>
        <input type="date" name="subscribed_at" class="form-control form-control-sm" value="{{ old('subscribed_at', $record?->subscribed_at?->format('Y-m-d') ?? now()->format('Y-m-d')) }}">
    </div>
    <div class="col-md-4">
        <label class="domain-form-label">تاريخ التجديد</label>
        <input type="date" name="renewal_at" class="form-control form-control-sm" value="{{ old('renewal_at', $record?->renewal_at?->format('Y-m-d')) }}">
    </div>
    <div class="col-md-6">
        <label class="domain-form-label">مدة التنفيذ (نص)</label>
        <input type="text" name="execution_duration" id="execution_duration" class="form-control form-control-sm" value="{{ old('execution_duration', $record?->execution_duration) }}">
    </div>
    <div class="col-md-6">
        <label class="domain-form-label">مدة التنفيذ (أيام)</label>
        <input type="number" name="execution_days" id="execution_days" class="form-control form-control-sm" value="{{ old('execution_days', $record?->execution_days) }}" min="0">
    </div>
    <div class="col-12">
        <label class="domain-form-label">ملاحظات</label>
        <textarea name="notes" rows="3" class="form-control form-control-sm">{{ old('notes', $record?->notes) }}</textarea>
    </div>
</div>

@once
@push('scripts')
<script>
(function() {
    function initCustomerAjaxSelect() {
        const element = document.getElementById('customer_id_select');
        if (!element || element.dataset.customerAjaxReady === '1' || typeof Choices === 'undefined') {
            return;
        }

        element.dataset.customerAjaxReady = '1';
        const searchUrl = element.dataset.searchUrl;
        let debounceTimer = null;
        let activeController = null;

        const instance = new Choices(element, {
            allowHTML: false,
            searchEnabled: true,
            searchChoices: false,
            shouldSort: false,
            itemSelectText: '',
            placeholder: true,
            placeholderValue: 'ابحث بالاسم أو دومين البريد',
            searchPlaceholderValue: 'ابحث بالاسم أو دومين البريد…',
            noResultsText: 'لا توجد نتائج',
            noChoicesText: 'اكتب حرفين على الأقل للبحث',
            loadingText: 'جاري البحث…',
        });

        element.addEventListener('search', function(event) {
            const query = (event.detail.value || '').trim();
            clearTimeout(debounceTimer);

            if (query.length < 2) {
                instance.clearChoices();
                return;
            }

            debounceTimer = setTimeout(function() {
                if (activeController) {
                    activeController.abort();
                }
                activeController = new AbortController();

                const url = new URL(searchUrl, window.location.origin);
                url.searchParams.set('q', query);

                fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    signal: activeController.signal,
                })
                .then(function(response) {
                    if (!response.ok) {
                        throw new Error('Network');
                    }
                    return response.json();
                })
                .then(function(items) {
                    const selected = element.value;
                    const choices = (items || []).map(function(item) {
                        return {
                            value: String(item.value),
                            label: item.label,
                            selected: String(item.value) === String(selected),
                        };
                    });

                    instance.clearChoices();
                    instance.setChoices(choices, 'value', 'label', true);
                })
                .catch(function(err) {
                    if (err.name !== 'AbortError') {
                        instance.clearChoices();
                    }
                });
            }, 300);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCustomerAjaxSelect);
    } else {
        initCustomerAjaxSelect();
    }
})();
</script>
@endpush
@endonce
