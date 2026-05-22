@php
    $index = $index ?? 0;
    $item = $item ?? null;
@endphp
<tr class="item-row">
    <td class="text-center" style="width:4rem">
        <button type="button" class="btn btn-icon btn-sm btn-danger-transparent rounded-pill remove-item" title="حذف">
            <i class="ri-delete-bin-line"></i>
        </button>
    </td>
    <td style="min-width:12rem;width:35%">
        <input type="text" class="form-control form-control-sm item-description" name="items[{{ $index }}][description]"
            value="{{ old('items.'.$index.'.description', $item->description ?? '') }}" placeholder="وصف البند" required>
    </td>
    <td style="width:10rem" class="text-center">
        <div class="form-check d-flex justify-content-center mb-0">
            <input type="checkbox" class="form-check-input item-taxed" id="taxed{{ $index }}" name="items[{{ $index }}][taxed]" value="1"
                {{ old('items.'.$index.'.taxed', $item->taxed ?? false) ? 'checked' : '' }}>
        </div>
    </td>
    <td style="width:14rem">
        <div class="input-group input-group-sm">
            <input type="number" class="form-control item-amount" name="items[{{ $index }}][amount]"
                value="{{ old('items.'.$index.'.amount', $item->amount ?? '') }}" placeholder="0.00" step="0.01" min="0" required>
            <span class="input-group-text">ر.س</span>
        </div>
    </td>
</tr>
