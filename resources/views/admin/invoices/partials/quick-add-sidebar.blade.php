@if(($offeredServices ?? collect())->isNotEmpty())
<div class="domain-panel mb-3">
    <div class="domain-panel__head">
        <span class="domain-panel__head-icon"><i class="fe fe-layers"></i></span>
        <h2 class="domain-panel__title">كتالوج الخدمات</h2>
    </div>
    <div class="domain-panel__body py-2">
        <div class="domain-pick-list">
            @foreach($offeredServices as $svc)
            <button type="button" class="domain-pick-item add-offered-service-item"
                data-service-id="{{ $svc->id }}"
                data-service-name="{{ $svc->name }}"
                data-service-price="{{ $svc->price }}">
                <span>
                    <span class="domain-pick-item__title">{{ $svc->name }}</span>
                    @if($svc->serviceType)
                    <span class="domain-pick-item__sub">{{ $svc->serviceType->name }}</span>
                    @endif
                </span>
                <span class="domain-pick-item__badge">{{ $svc->formatted_price }}</span>
            </button>
            @endforeach
        </div>
    </div>
</div>
@endif

@if(($customerServices ?? collect())->isNotEmpty())
<div class="domain-panel mb-3">
    <div class="domain-panel__head">
        <span class="domain-panel__head-icon"><i class="fe fe-briefcase"></i></span>
        <h2 class="domain-panel__title">خدمات بدون فاتورة</h2>
    </div>
    <div class="domain-panel__body py-2">
        <div class="domain-pick-list">
            @foreach($customerServices as $cs)
            <button type="button" class="domain-pick-item add-customer-service-item"
                data-customer-service-id="{{ $cs->id }}"
                data-customer-id="{{ $cs->customer_id }}"
                data-customer-label="{{ trim($cs->customer?->fullname ?? '') }} ({{ $cs->customer?->email }})"
                data-service-name="{{ $cs->name }}"
                data-service-price="{{ $cs->amount_due > 0 ? $cs->amount_due : $cs->price }}"
                data-offered-service-id="{{ $cs->offered_service_id }}">
                <span>
                    <span class="domain-pick-item__title">{{ $cs->name }}</span>
                    <span class="domain-pick-item__sub">{{ $cs->customer?->fullname }}</span>
                </span>
                <span class="domain-pick-item__badge domain-pick-item__badge--warning">{{ number_format($cs->amount_due > 0 ? $cs->amount_due : $cs->price, 2) }} ر.س</span>
            </button>
            @endforeach
        </div>
    </div>
</div>
@endif

@if(($products ?? collect())->isNotEmpty())
<div class="domain-panel mb-3">
    <div class="domain-panel__head">
        <span class="domain-panel__head-icon"><i class="fe fe-package"></i></span>
        <h2 class="domain-panel__title">المنتجات</h2>
    </div>
    <div class="domain-panel__body py-2">
        <div class="domain-pick-list">
            @foreach($products as $product)
            <button type="button" class="domain-pick-item add-product-item"
                data-product-name="{{ $product->name }}"
                data-product-price="{{ $product->price }}">
                <span>
                    <span class="domain-pick-item__title">{{ $product->name }}</span>
                    @if($product->type)
                    <span class="domain-pick-item__sub">{{ $product->type }}</span>
                    @endif
                </span>
                <span class="domain-pick-item__badge">{{ number_format($product->price, 2) }} ر.س</span>
            </button>
            @endforeach
        </div>
    </div>
</div>
@endif
