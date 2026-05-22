@php
    $icon = old($inputPrefix.'.'.$index.'.icon', $row['icon'] ?? 'check');
    $text = old($inputPrefix.'.'.$index.'.text', $row['text'] ?? '');
    $hasName = is_numeric($index);
    $nameIcon = $hasName ? "{$inputPrefix}[{$index}][icon]" : '';
    $nameText = $hasName ? "{$inputPrefix}[{$index}][text]" : '';
@endphp
<div class="package-feature-row border rounded p-2 bg-light" data-index="{{ $index }}">
    <div class="row g-2 align-items-center">
        <div class="col-md-4">
            <label class="form-label small mb-1">الأيقونة</label>
            <select class="form-select form-select-sm" data-field="icon" @if($nameIcon) name="{{ $nameIcon }}" @endif>
                @foreach($featureIcons as $key => $meta)
                    <option value="{{ $key }}" @selected($icon === $key)>
                        {{ $meta['label'] ?? $key }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-7">
            <label class="form-label small mb-1">نص الميزة</label>
            <input type="text" class="form-control form-control-sm" data-field="text" @if($nameText) name="{{ $nameText }}" @endif
                value="{{ $text }}" placeholder="مثال: مساحة تخزين SSD 10 GB" maxlength="500">
        </div>
        <div class="col-md-1 text-end">
            <label class="form-label small mb-1 d-block">&nbsp;</label>
            <button type="button" class="btn btn-sm btn-outline-danger package-feature-remove" title="حذف">
                <i class="fe fe-trash-2"></i>
            </button>
        </div>
        <div class="col-12">
            <span class="text-muted small" dir="ltr">
                <i class="{{ !empty($featureIcons[$icon]['brand']) ? 'fab' : 'fas' }} {{ $featureIcons[$icon]['class'] ?? 'fa-check' }} me-1"></i>
                معاينة
            </span>
        </div>
    </div>
</div>
