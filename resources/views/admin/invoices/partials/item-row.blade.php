@php
    $index = $index ?? 0;
    $item = $item ?? null;
@endphp
<tr class="item-row">
    <td class="domain-list-table__action">
        <button type="button" class="domain-action-btn domain-action-btn--danger remove-item" title="حذف">
            <i class="fe fe-trash-2"></i>
        </button>
    </td>
    <td>
        <input type="hidden" name="items[{{ $index }}][offered_service_id]" class="item-offered-service-id" value="{{ old('items.'.$index.'.offered_service_id', $item->offered_service_id ?? '') }}">
        <input type="hidden" name="items[{{ $index }}][customer_service_id]" class="item-customer-service-id" value="{{ old('items.'.$index.'.customer_service_id', $item->customer_service_id ?? '') }}">
        <input type="text" class="form-control form-control-sm item-description" name="items[{{ $index }}][description]"
            value="{{ old('items.'.$index.'.description', $item->description ?? '') }}" placeholder="وصف البند" required>
    </td>
    <td class="text-center">
        <div class="form-check d-flex justify-content-center mb-0">
            <input type="checkbox" class="form-check-input item-taxed" id="taxed{{ $index }}" name="items[{{ $index }}][taxed]" value="1"
                {{ old('items.'.$index.'.taxed', $item->taxed ?? false) ? 'checked' : '' }}>
        </div>
    </td>
    <td>
        <div class="input-group input-group-sm">
            <input type="number" class="form-control item-amount" name="items[{{ $index }}][amount]"
                value="{{ old('items.'.$index.'.amount', $item->amount ?? '') }}" placeholder="0.00" step="0.01" min="0" required>
            <span class="input-group-text">ر.س</span>
        </div>
    </td>
</tr>
