<div class="hero-repeater-row border rounded p-3 mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-2">
            <label class="form-label">الرقم</label>
            <input type="number" name="content[stats][{{ $index }}][value]" class="form-control" min="0" value="{{ $stat['value'] ?? 0 }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">لاحقة</label>
            <input type="text" name="content[stats][{{ $index }}][suffix]" class="form-control" value="{{ $stat['suffix'] ?? '+' }}">
        </div>
        <div class="col-md-5">
            <label class="form-label">التسمية</label>
            <input type="text" name="content[stats][{{ $index }}][label]" class="form-control" value="{{ $stat['label'] ?? '' }}">
        </div>
        <div class="col-md-2">
            <div class="form-check">
                <input type="hidden" name="content[stats][{{ $index }}][enabled]" value="0">
                <input class="form-check-input" type="checkbox" name="content[stats][{{ $index }}][enabled]" value="1" @checked($stat['enabled'] ?? true)>
                <label class="form-check-label small">تفعيل</label>
            </div>
        </div>
        <div class="col-md-1 text-end">
            <button type="button" class="btn btn-sm btn-outline-danger hero-remove-row" title="حذف"><i class="fas fa-trash"></i></button>
        </div>
    </div>
</div>
