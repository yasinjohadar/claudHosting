<div class="hero-repeater-row border rounded p-3 mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label">النص</label>
            <input type="text" name="content[buttons][{{ $index }}][label]" class="form-control" value="{{ $btn['label'] ?? '' }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">الرابط</label>
            <input type="text" name="content[buttons][{{ $index }}][url]" class="form-control" value="{{ $btn['url'] ?? '' }}" placeholder="/packages">
        </div>
        <div class="col-md-2">
            <label class="form-label">النمط</label>
            <select name="content[buttons][{{ $index }}][style]" class="form-select">
                <option value="primary" @selected(($btn['style'] ?? 'primary') === 'primary')>أساسي</option>
                <option value="outline" @selected(($btn['style'] ?? '') === 'outline')>حدود</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">أيقونة FA</label>
            <input type="text" name="content[buttons][{{ $index }}][icon]" class="form-control" value="{{ $btn['icon'] ?? 'fas fa-link' }}">
        </div>
        <div class="col-md-1">
            <div class="form-check">
                <input type="hidden" name="content[buttons][{{ $index }}][enabled]" value="0">
                <input class="form-check-input" type="checkbox" name="content[buttons][{{ $index }}][enabled]" value="1" @checked($btn['enabled'] ?? true)>
                <label class="form-check-label small">تفعيل</label>
            </div>
        </div>
        <div class="col-md-1 text-end">
            <button type="button" class="btn btn-sm btn-outline-danger hero-remove-row" title="حذف"><i class="fas fa-trash"></i></button>
        </div>
    </div>
</div>
